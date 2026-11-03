<?php

namespace Modules\CustomerSystems\Services;

class BueroPlusClient
{
    private $settings;
    private $http;

    public function __construct(array $settings, $http = null)
    {
        $this->settings = BueroPlusSettings::prepare($settings, BueroPlusSettings::defaults(), true);
        $this->http = $http ?: new JsonHttpClient();
    }

    public function testConnection()
    {
        $base = $this->settings['url'];
        $token = $this->http->postForm($base.'/token', ['grant_type' => 'client_credentials', 'scope' => 'graphql'],
            ['Authorization: Basic '.base64_encode($this->settings['client_id'].':'.$this->settings['client_secret'])]);
        if (!isset($token['access_token'], $token['token_type']) || !is_string($token['access_token'])
            || !is_string($token['token_type']) || strcasecmp($token['token_type'], 'Bearer') !== 0
            || !preg_match('/^[A-Za-z0-9\-._~+\/]+=*$/D', $token['access_token'])) {
            throw new ApiException('invalid_response', 200, 0, 'oauth');
        }
        // Standard GraphQL meta field: no ERP table names, customer data or mutations.
        $data = $this->http->post($base.'/graphql/v1', ['query' => 'query FreeScoutConnectionTest { __typename }'],
            ['Authorization: Bearer '.$token['access_token']]);
        if (!empty($data['errors'])) { throw new ApiException('graphql_failed', 200, 0, 'api'); }
        if (!isset($data['data']['__typename']) || !is_string($data['data']['__typename']) || $data['data']['__typename'] === '') {
            throw new ApiException('invalid_response', 200, 0, 'api');
        }
        // Tokens live only for this test; no persistent token cache or background requests.
        return true;
    }
}
