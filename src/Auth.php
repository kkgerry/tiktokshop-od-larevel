<?php
/*
 * This file is part of tiktok-shop.
 *
 * (c) Jin <j@sax.vn>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Kkgerry\TiktokShop;

use GuzzleHttp\Client as GuzzleHttpClient;
use GuzzleHttp\RequestOptions;
use Kkgerry\TiktokShop\Errors\AuthorizationException;

class Auth
{
    use TikTokForwardRequestTrait;

    protected $client;

    protected $httpClient;

    protected $authHost;

    public function __construct(Client $client)
    {
        $this->client = $client;
        $this->httpClient = new GuzzleHttpClient();

        $this->authHost = 'https://auth.tiktok-shops.com';
    }

    public function createAuthRequest($state = null, $returnAuthUrl = false)
    {
        $params = [
            'app_key' => $this->client->getAppKey(),
            'state' => $state ?? rand(10000, 99999),
        ];

        $authUrl = $this->authHost . '/oauth/authorize?' . http_build_query($params);

        if ($returnAuthUrl) {
            return $authUrl;
        }

        header('Location: '.$authUrl);
        exit;
    }

    public function getToken($code)
    {

        $newClient = new \GuzzleHttp\Client(['verify'=>false]);
        $url = $this->getForwardUrl('auth');
        $params = [
            'app_key' => $this->client->getAppKey(),
            'app_secret' => $this->client->getAppSecret(),
            'auth_code' => $code,
            'grant_type' => 'authorized_code',
        ];
        $requestData = [
            'url' => $this->authHost . '/api/v2/token/get',
            'method' => 'GET',
            'params' => ($params) ? json_encode($params) : [],
        ];

        $response = $newClient->post($url,[
            'form_params' => $requestData
        ]);

        $resultData = json_decode((string) $response->getBody(), true);
        if($resultData['error']){
            throw new AuthorizationException($resultData['msg']);
        }
        $json = $resultData['data'];

       /* $response = $this->httpClient->get($this->authHost . '/api/v2/token/get', [
            RequestOptions::QUERY => [
                'app_key' => $this->client->getAppKey(),
                'app_secret' => $this->client->getAppSecret(),
                'auth_code' => $code,
                'grant_type' => 'authorized_code',
            ],
        ]);

        $json = json_decode($response->getBody(), true);*/
        if ($json['code'] !== 0) {
            throw new AuthorizationException($json['message'], $json['code']);
        }

        return $json['data'];
    }

    public function refreshNewToken($refresh_token)
    {

        $newClient = new \GuzzleHttp\Client(['verify'=>false]);
        $url = $this->getForwardUrl('auth');
        $params = [
            'app_key' => $this->client->getAppKey(),
            'app_secret' => $this->client->getAppSecret(),
            'refresh_token' => $refresh_token,
            'grant_type' => 'refresh_token',
        ];
        $requestData = [
            'url' => $this->authHost . '/api/v2/token/refresh',
            'method' => 'GET',
            'params' => ($params) ? json_encode($params) : [],
        ];

        $response = $newClient->post($url,[
            'form_params' => $requestData
        ]);

        $resultData = json_decode((string) $response->getBody(), true);
        if($resultData['error']){
            throw new AuthorizationException($resultData['msg']);
        }
        $json = $resultData['data'];

        /*$response = $this->httpClient->get($this->authHost . '/api/v2/token/refresh', [
            RequestOptions::QUERY => [
                'app_key' => $this->client->getAppKey(),
                'app_secret' => $this->client->getAppSecret(),
                'refresh_token' => $refresh_token,
                'grant_type' => 'refresh_token',
            ],
        ]);

        $json = json_decode($response->getBody(), true);
        */
        if ($json['code'] !== 0) {
            throw new AuthorizationException($json['message'], $json['code']);
        }

        return $json['data'];
    }
}
