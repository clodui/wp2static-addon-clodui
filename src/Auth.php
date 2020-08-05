<?php

namespace WP2StaticClodui;

use Aws\CognitoIdentityProvider\CognitoIdentityProviderClient;
use Aws\CognitoIdentity\CognitoIdentityClient;
use Aws\Credentials\Credentials;
use Exception;

class Auth {

    private $region;
    private $cognito_client_id;
    private $cognito_user_pool_id;
    private $cognito_identity_pool_id;
    private $client;
    private $auth_result;

    private $identity_client;
    private $cognito_id;
    private $user_credentials;

    public function __construct(string $client_id, 
                                string $user_pool_id,
                                string $identity_pool_id) {

        $this->cognito_client_id = $client_id;
        $this->cognito_user_pool_id = $user_pool_id;
        $this->cognito_identity_pool_id = $identity_pool_id;
        $this->region = 'us-east-1';

        $config = [
            'region' => $this->region,
            'version' => '2016-04-18',
            'credentials' => false
        ];

        $this->client = new CognitoIdentityProviderClient($config);
        $this->identity_client = new CognitoIdentityClient([
            'region' => $this->region,
            'version' => '2014-06-30',
            'credentials' => false
        ]);
    }


    public function login(string $username, string $token_id, string $token) {

        Logger::info('Authenticating user '. $username);

        try {
            $initiate_auth_result = $this->client->initiateAuth([
                'AuthFlow' => 'CUSTOM_AUTH',
                'ClientId' => $this->cognito_client_id,
                'UserPoolId' => $this->cognito_user_pool_id,
                'AuthParameters' => [
                    'USERNAME' => $username
                ]
            ]);

            if(is_null($initiate_auth_result)) {
                throw new Exception('InitiateAuth error');
            }

            $challenge_result = $this->client->respondToAuthChallenge([
                'ChallengeName' => 'CUSTOM_CHALLENGE',
                'ChallengeResponses' => [
                    'USERNAME' => $username,
                    'ANSWER' => $token
                ],
                'ClientId' => $this->cognito_client_id,
                'Session' => $initiate_auth_result->get("Session"),
                'ClientMetadata' => [
                    'id' => $token_id
                ]
            ]);

            if(is_null($challenge_result)) {
                throw new Exception('RespondToAuthChallenge error');
            }

            $this->auth_result = $challenge_result['AuthenticationResult'];

            Logger::info('Authentication successful. '. $username);
        }catch(\Exception $e) {
            $err = 'Authentication failed: '. $e->getMessage();
            throw new Exception( $err );
        }
    }

    public function get_session() {
        Logger::debug('Getting logged in user session...');
        if(!isset($this->auth_result)) {
            throw new Exception('User not logged in');
        }
        return $this->auth_result;
    }


    public function get_identity_id(): string {
        Logger::debug('Getting user identity id');
        if(!isset($this->cognito_id)) {
            $session = $this->get_session();

            $provider_key = 'cognito-idp.'. $this->region .'.amazonaws.com/'. $this->cognito_user_pool_id;

            $this->cognito_id = $this->identity_client->getId([
                'IdentityPoolId' => $this->cognito_identity_pool_id,
                'Logins' => [
                    $provider_key => $session['IdToken']
                ]
            ]);
        }

        Logger::debug('Found user identity id');
        return $this->cognito_id->get('IdentityId');
    }

    public function get_logged_in_user_credentials() : Credentials {

        Logger::debug('Getting logged in user credentials');
        if(!isset($this->user_credentials)) {
            $session = $this->get_session();

            $provider_key = 'cognito-idp.'. $this->region .'.amazonaws.com/'. $this->cognito_user_pool_id;

            $result = $this->identity_client->getCredentialsForIdentity([
                'IdentityId' => $this->get_identity_id(),
                'Logins' => [
                    $provider_key => $session['IdToken']
                ]
            ]);

            if(is_null($result)) {
                throw new Exception("GetCredentialsForIdentity error");
            }

            $this->user_credentials = new Credentials(
                $result['Credentials']['AccessKeyId'], 
                $result['Credentials']['SecretKey'], 
                $result['Credentials']['SessionToken'], 
                $result['Credentials']["Expiration"]);
        }

        Logger::debug('Found logged in user credentials');
        return $this->user_credentials;
    }
}