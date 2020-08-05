<?php

namespace WP2StaticClodui;

use Exception;

class Api {

    private $endpoint;

    public function __construct(string $endpoint) {
        $this->endpoint = $endpoint;
    }


    private function graphql_query($query, $variables = [], $id_token)
    {
        $headers = ['Content-Type: application/json', 'User-Agent: WP2Static Clodui Plugin'];
        $headers[] = "Authorization: $id_token";

        if (false === $data = @file_get_contents($this->endpoint, false, stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => $headers,
                    'content' => json_encode(['query' => $query, 'variables' => $variables]),
                ]
            ]))) {
            $error = error_get_last();
            throw new Exception( 'Clodui Api failed: '. $error['message']. ' type: '. $error['type'] );
        }

        $result = json_decode($data, true);

        if(isset($result["errors"])) {
            $error = $result["errors"][0];
            throw new Exception( 'Clodui Api failed: '. $error['message']. ' type: '. $error['errorType'] );
        }

        return $result['data'];
    }

    public function start_deployment(string $source_path, string $id_token) {

        Logger::debug('Invoking start deployment endpoint. '. $source_path);

        $website_id = Controller::getValue('websiteID');

        $query = <<<'GRAPHQL'
            mutation DeployWebsite($input: DeployWebsiteInput!) {
                deployWebsite(input: $input) {
                    id
                    status
                    website_id
                    created_on
                    security
                    optimization
                    plan
                    spa
                    website {
                        name
                        active_deployment
                    }
                }
            }
            GRAPHQL;
        
        $variables = [
                'input' => [
                    'id' => $website_id,
                    'source_directory' => $source_path,
                    'publish' => true
                ]
            ];
        
        $result = $this->graphql_query($query, $variables, $id_token);
        $data = $result["deployWebsite"];
        $deployment_id = $data["id"];
        
        Logger::info('Deployment started ('. $deployment_id. ')');
        return $data;
    }

    public function get_deployment_status(string $website_id, string $deployment_id, string $id_token) {

        Logger::debug('Getting deployment status ('. $deployment_id. ')');

        $query = <<<'GRAPHQL'
            query GetWebsiteDeployment($id: ID!, $website_id: String!) {
                getWebsiteDeployment(id: $id, website_id: $website_id) {
                    id
                    website_id
                    status
                }
            }
            GRAPHQL;
        
        $variables = [
                'id' => $deployment_id,
                'website_id' => $website_id,
            ];
        
        $result = $this->graphql_query($query, $variables, $id_token);
        $status = $result["getWebsiteDeployment"]["status"];

        Logger::info('Deployment status ' . $deployment_id . ' ('. $status . ')');

        return $status;
    }
}