<?php

namespace WP2StaticClodui;

use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use Ramsey\Uuid\Uuid;
use Aws\S3\S3Client;
use Exception;
use Symfony\Component\Dotenv\Dotenv;

class Deployer {

    const DEFAULT_NAMESPACE = 'wp2static-addon-clodui/default';

    private $api;
    private $auth;

    // prepare deploy, if modifies URL structure, should be an action
    // $this->prepareDeploy();

    // options - load from addon's static methods

    public function __construct() {
        $dotenv = new Dotenv();
        $dotenv->load(dirname(__DIR__).'/.env');

        $this->api = new Api($_ENV['CLODUI_API']);

        $client_id = $_ENV['CLODUI_CLIENT_ID'];
        $user_pool_id = $_ENV['CLODUI_USER_POOL_ID'];
        $identity_pool_id = $_ENV['CLODUI_IDENTITY_POOL_ID'];

        Logger::debug('Config Client ID '. $client_id. ', User pool id '. $user_pool_id. ', Identity pool id '. $identity_pool_id);
        $this->auth = new Auth($client_id, $user_pool_id, $identity_pool_id);
    }


    public function upload_files( string $processed_site_path ) : void {
        // check if dir exists
        if ( ! is_dir( $processed_site_path ) ) {
            return;
        }

        $username = Controller::getValue('username');
        $token_id = Controller::getValue('tokenID');
        $token = \WP2Static\CoreOptions::encrypt_decrypt('decrypt', Controller::getValue( 'token' ));


        try {
            $this->auth->login($username, $token_id, $token);
            $credentials = $this->auth->get_logged_in_user_credentials();
            $id_token = $this->auth->get_session()['IdToken'];

            // instantiate S3 client
            $client_options = [
                'version' => '2006-03-01',
                'region' => 'us-east-1',
                'credentials' => $credentials
            ];
    
            $s3 = new S3Client($client_options);

            // iterate each file in ProcessedSite
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator(
                    $processed_site_path,
                    RecursiveDirectoryIterator::SKIP_DOTS
                )
            );

            $put_data = [
                'Bucket' => $_ENV['CLODUI_USER_BUCKET']
            ];

            $uuid_v4 = Uuid::uuid4()->toString();
            $source_prefix = $this->auth->get_identity_id() .'/'. $uuid_v4;
            $key_prefix = 'private/'. $source_prefix .'/';

        
            foreach ( $iterator as $filename => $file_object ) {
                $base_name = basename( $filename );
                if ( $base_name != '.' && $base_name != '..' ) {
                    $real_filepath = realpath( $filename );

                    // TODO: do filepaths differ when running from WP-CLI (non-chroot)?
                    $cache_key = str_replace( $processed_site_path, '', $filename );

                    if ( ! $real_filepath ) {
                        $err = 'Trying to deploy unknown file to S3: ' . $filename;
                        Logger::error($err);
                        continue;
                    }

                    // Standardize all paths to use / (Windows support)
                    $filename = str_replace( '\\', '/', $filename );

                    if ( ! is_string( $filename ) ) {
                        continue;
                    }

                    $s3_key = $key_prefix . ltrim( $cache_key, '/' );

                    $mime_type = MimeTypes::GuessMimeType( $filename );
                    if ( "text/" === substr( $mime_type, 0, 5 ) ) {
                        $mime_type = $mime_type . '; charset=UTF-8';
                    }

                    $put_data['Key'] = $s3_key;
                    $put_data['ContentType'] = $mime_type;
                    $put_data['Body'] = file_get_contents( $filename );

                    $s3->putObject( $put_data );
                    Logger::debug('Uploaded file '. $filename);
                }
            }

            $deployment_result = $this->api->start_deployment($source_prefix, $id_token);
            $deployment_id = $deployment_result["id"];

            $this->wait_for_deployment_to_finish($deployment_id, $id_token);

        }catch(Exception $e) {
            Logger::error($e->getMessage());
        }
    }


    private function wait_for_deployment_to_finish(string $deployment_id, string $id_token) {

        $max_wait_sec = 30 * 60; // 30 minutes
        $wait_sec = 0;
        $sleep_sec = 30;
        $website_id = Controller::getValue( 'websiteID' );

        do {
            sleep($sleep_sec);
            $wait_sec += $sleep_sec;
            $status = $this->api->get_deployment_status($website_id, $deployment_id, $id_token);

            if ($status == "DEPLOYED" || $status == "DEPLOY_FAILED") {
                Logger::info('Changes deployed successfully ('. $deployment_id. ')');
                return;
            }

            // Waited for 15 minutes, change interval
            if( $wait_sec >= 15 * 60 ) {
                $sleep_sec = 60;
            }

        }   while($wait_sec < $max_wait_sec);

        Logger::warn('Deployment did not complete with in waiting period. Please check status in https://app.clodui.com/website/'. $website_id .'/deploy');

    }

}

