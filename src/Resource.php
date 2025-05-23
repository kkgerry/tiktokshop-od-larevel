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

use DateTimeInterface;
use Kkgerry\TiktokShop\Errors\TiktokShopException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Kkgerry\TiktokShop\Client as TiktokShopClient;
use Kkgerry\TiktokShop\Errors\ResponseException;
use Kkgerry\TiktokShop\Errors\TokenException;

abstract class Resource
{
    use TikTokForwardRequestTrait;

    /** @var Client */
    protected $httpClient;
    protected $options;

    protected $category = '';

    protected $version = TiktokShopClient::DEFAULT_VERSION;

    protected $last_message = null;

    protected $last_request_id = null;

    public function useVersion($version)
    {
        if (intval($version) < 202309) {
            throw new TiktokShopException('API version 202309 is the minimum requirement');
        }

        $this->version = $version;

        return $this;
    }

    public function useHttpClient(Client $client)
    {
        $this->httpClient = $client;

        return $this;
    }

    public function useOption($option)
    {
        $this->options = $option;
    }

    /**
     * @throws \Kkgerry\TiktokShop\Errors\TiktokShopException
     */
    public function call($method, $action, $params = [])
    {
        $uri = trim($this->category.'/'.$this->version.'/'.$action, '/');
        /*print_r($method);
        echo "<br>";
        print_r($uri);
        echo "<br>";*/
        //print_r($this->httpClient->getConfig());
        //print_r($this->options);
        //print_r($params);
        //die();

        //$options = $this->httpClient->getClientOptions();

        $requestData = [
            'url' => $uri,
            'method' => $method,
            'params' => ($params) ? json_encode($params) : [],
            'options' => $this->options,
        ];
        //print_r($requestData);
        //die();
        try {
            
            $newClient = new Client(['verify'=>false]);
            $fwUrl = $this->getForwardUrl();

            $response = $newClient->post($fwUrl,[
                'form_params' => $requestData
            ]);

            $resultData = json_decode((string) $response->getBody(), true);
            //print_r($resultData);die();
            if(!isset($resultData['error']) || $resultData['error']){
                throw new ResponseException($resultData['msg'] ?? '请求中转失败');
            }
            //$response = $this->httpClient->request($method, $uri, $params);
        } catch (GuzzleException $e) {
            throw new ResponseException($e->getMessage(), $e->getCode(), $e);
        }

        //$json = json_decode((string) $response->getBody(), true);
        $json = $resultData['data'];

        //print_r($json);
        //die();
        if ($json === null) {
            throw new ResponseException('Unable to parse response string as JSON');
        }

        $this->last_message = $json['message'] ?? null;
        $this->last_request_id = $json['request_id'] ?? null;

        $code = $json['code'] ?? -1;
        if ($code !== 0) {
            $this->handleErrorResponse($code, $json['message']);
        }

        return $json['data'] ?? [];
    }

    public function getLastMessage()
    {
        return $this->last_message;
    }

    public function getLastRequestId()
    {
        return $this->last_request_id;
    }

    /**
     * @throws ResponseException
     * @throws TokenException
     */
    protected function handleErrorResponse($code, $message)
    {
        // get 3 first digit as the error code group
        // more detail: https://partner.tiktokshop.com/doc/page/234136
        $errorGroup = substr(strval($code), 0, 3);

        switch ($errorGroup) {
            case '105':
            case '360':
                throw new TokenException($message, $code);
            default:
                throw new ResponseException($message, $code);
        }
    }

    static public function extractParams($array, &$query, &$body)
    {
        $body = $array;
        unset($body['page_size'], $body['sort_order'], $body['page_token'], $body['sort_field']);

        $query = array_filter([
            'page_size' => $array['page_size'] ?? 20,
            'sort_order' => $array['sort_order'] ?? null,
            'page_token' => $array['page_token'] ?? null,
            'sort_field' => $array['sort_field'] ?? null,
        ]);
    }

    static public function dataTypeCast($type, $data)
    {
        switch ($type) {
            case 'image':
            case 'file':
                if (is_resource($data)) {
                    return $data;
                }

                return fopen($data, 'r');
            case 'int':
            case 'integer':
                return intval($data);
            case 'array':
                return implode(',', is_array($data) ? $data : [$data]);
            case 'bool':
            case 'boolean':
                return boolval($data);
            case 'timestamp':
                $timestamp = $data;

                if (!$timestamp) {
                    return time();
                }

                if ($timestamp instanceof DateTimeInterface) {
                    return $timestamp->getTimestamp();
                }

                if (is_string($timestamp)) {
                    $timestamp = strtotime($timestamp) ?: time();
                }

                return $timestamp;
            case 'string':
            default:
                return strval($data);
        }
    }
}
