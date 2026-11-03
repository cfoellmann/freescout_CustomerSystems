<?php

namespace Modules\CustomerSystems\Services;

use RuntimeException;

class Shopware6Transport
{
    private $config;
    private $request;
    private $token;

    public function __construct(array $config, ?callable $request = null)
    {
        $this->config = $config;
        // Injection permits testing OAuth and request headers without live credentials.
        $this->request = $request ?: [new JsonHttpClient(), 'post'];
    }

    public function __invoke($path, array $body)
    {
        $url = rtrim((string) ($this->config['url'] ?? ''), '/');
        if (!ConnectionSettings::validUrl($url) || empty($this->config['username']) || empty($this->config['api_key'])) {
            throw new RuntimeException('not_configured');
        }
        if (!$this->token) {
            $oauth = call_user_func($this->request, $url.'/oauth/token', [
                'grant_type' => 'client_credentials', 'client_id' => $this->config['username'],
                'client_secret' => $this->config['api_key'],
            ], []);
            if (!is_array($oauth) || !is_string($oauth['access_token'] ?? null)
                || !preg_match('/^[A-Za-z0-9._~+\/-]+=*$/D', $oauth['access_token'])
                || strcasecmp($oauth['token_type'] ?? '', 'Bearer') !== 0) {
                throw new RuntimeException('invalid_response');
            }
            $this->token = $oauth['access_token'];
        }
        return call_user_func($this->request, $url.'/'.$path, $body, ['Authorization: Bearer '.$this->token]);
    }
}
