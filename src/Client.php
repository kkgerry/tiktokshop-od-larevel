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

use Kkgerry\TiktokShop\Resources\AffiliateCreator;
use Kkgerry\TiktokShop\Resources\AffiliateSeller;
use Kkgerry\TiktokShop\Resources\Analytics;
use Kkgerry\TiktokShop\Resources\CustomerService;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Client as GuzzleHttpClient;
use GuzzleHttp\Middleware;
use GuzzleHttp\RequestOptions;
use Kkgerry\TiktokShop\Errors\TiktokShopException;
use Kkgerry\TiktokShop\Resources\Event;
use Kkgerry\TiktokShop\Resources\Finance;
use Kkgerry\TiktokShop\Resources\Fulfillment;
use Kkgerry\TiktokShop\Resources\GlobalProduct;
use Kkgerry\TiktokShop\Resources\Logistic;
use Kkgerry\TiktokShop\Resources\Order;
use Kkgerry\TiktokShop\Resources\Product;
use Kkgerry\TiktokShop\Resources\Promotion;
use Kkgerry\TiktokShop\Resources\ReturnRefund;
use Kkgerry\TiktokShop\Resources\Seller;
use Kkgerry\TiktokShop\Resources\Authorization;
use Kkgerry\TiktokShop\Resources\Supplychain;
use Psr\Http\Message\RequestInterface;

/**
 * @property-read Authorization $Authorization
 * @property-read Seller $Seller
 * @property-read Product $Product
 * @property-read Order $Order
 * @property-read Fulfillment $Fulfillment
 * @property-read Logistic $Logistic
 * @property-read Finance $Finance
 * @property-read GlobalProduct $GlobalProduct
 * @property-read Promotion $Promotion
 * @property-read Supplychain $Supplychain
 * @property-read Event $Event
 * @property-read ReturnRefund $ReturnRefund
 * @property-read AffiliateSeller $AffiliateSeller
 * @property-read AffiliateCreator $AffiliateCreator
 */
class Client
{
    public CONST DEFAULT_VERSION = '202309';
    protected $app_key;
    protected $app_secret;
    protected $access_token;

    /**
     * required for calling cross-border shop
     */
    protected $shop_cipher;
    protected $version;

    /**
     * custom guzzle client options
     *
     * @var array
     * @see https://docs.guzzlephp.org/en/stable/request-options.html
     */
    protected $options;

    public const resources = [
        Authorization::class,
        Seller::class,
        Product::class,
        Order::class,
        Fulfillment::class,
        Logistic::class,
        Finance::class,
        GlobalProduct::class,
        Promotion::class,
        Supplychain::class,
        Event::class,
        ReturnRefund::class,
        CustomerService::class,
        AffiliateSeller::class,
        AffiliateCreator::class,
        Analytics::class,
    ];

    public function __construct($app_key, $app_secret, $options = [])
    {
        $this->app_key = $app_key;
        $this->app_secret = $app_secret;
        $this->version = static::DEFAULT_VERSION;
        $this->options = $options;
    }

    public function useSandboxMode()
    {
        trigger_deprecation('ecomphp/tiktokshop-php', '2.0.0', 'useSandboxMode() will be deprecated: Since API version 202309, Tiktokshop API sandbox is no longer worked, please use production environment.');
    }

    public function getAppKey()
    {
        return $this->app_key;
    }

    public function getAppSecret()
    {
        return $this->app_secret;
    }

    public function setAccessToken($access_token)
    {
        $this->access_token = $access_token;
    }

    public function setShopCipher($shop_cipher)
    {
        $this->shop_cipher = $shop_cipher;
    }

    public function auth()
    {
        return new Auth($this);
    }

    public function webhook()
    {
        $webhook = new Webhook($this);
        $webhook->verify();
        $webhook->capture();

        return $webhook;
    }

    /**
     * append app_key, timestamp, version, shop_id, access_token, sign to request
     *
     * @param RequestInterface $request
     * @return RequestInterface
     */
    protected function modifyRequestBeforeSend(RequestInterface $request)
    {
        $uri = $request->getUri();
        parse_str($uri->getQuery(), $query);
        $query['app_key'] = $this->getAppKey();
        $query['timestamp'] = time();

        if ($this->access_token && !isset($query['x-tts-access-token'])) {
            $request = $request->withHeader('x-tts-access-token', $this->access_token);
        }

        if ($this->shop_cipher && !isset($query['shop_cipher'])) {
            $query['shop_cipher'] = $this->shop_cipher;
        }

        // shop_cipher is not allowed in some api
        if (preg_match('/^\/product\/(\d{6})\/(global_products|files\/upload|images\/upload)/', $uri->getPath())
            || preg_match('/^\/(authorization|seller|affiliate_creator)\/(\d{6})\//', $uri->getPath())) {
            unset($query['shop_cipher']);
        }

        $this->prepareSignature($request, $query);

        $uri = $uri->withQuery(http_build_query($query));

        // set default content-type to application/json
        if (!$request->getHeaderLine('content-type')) {
            $request = $request->withHeader('content-type', 'application/json');
        }
        return $request->withUri($uri);
    }

    protected function httpClient()
    {
        $stack = HandlerStack::create();

        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            return $this->modifyRequestBeforeSend($request);
        }));
        $options = array_merge([
            RequestOptions::HTTP_ERRORS => false, // disable throw exception on http 4xx, manual handle it
            'handler' => $stack,
            'base_uri' => 'https://open-api.tiktokglobalshop.com/',
        ], $this->options ?? []);
        return new GuzzleHttpClient($options);
    }

    protected function httpClientOptions()
    {
        $returnData = [
            'app_key' => $this->getAppKey(),
            'app_secret' => $this->getAppSecret(),
            'base_uri' => 'https://open-api.tiktokglobalshop.com/',
            'option' => $this->options ?? [],
            'access_token' => $this->access_token
        ];
        if ($this->shop_cipher && !isset($query['shop_cipher'])) {
            $returnData['shop_cipher'] = $this->shop_cipher;
        }
        
        return $returnData;
    }

    /**
     * tiktokshop api signature algorithm
     * @see https://partner.tiktokshop.com/doc/page/274638
     *
     * @param RequestInterface $request
     * @param $params
     * @return void
     */
    protected function prepareSignature($request, &$params)
    {
        $paramsToBeSigned = $params;
        $stringToBeSigned = '';

        // 1. Extract all query param EXCEPT ' sign ', ' access_token ', reorder the params based on alphabetical order.
        unset($paramsToBeSigned['sign'], $paramsToBeSigned['access_token'], $paramsToBeSigned['x-tts-access-token']);
        ksort($paramsToBeSigned);

        // 2. Concat all the param in the format of {key}{value}
        foreach ($paramsToBeSigned as $k => $v) {
            if (!is_array($v)) {
                $stringToBeSigned .= "$k$v";
            }
        }

        // 3. Append the request path to the beginning
        $stringToBeSigned = $request->getUri()->getPath() . $stringToBeSigned;

        // 4. If the request header content_type is not multipart/form-data, append body to the end
        if ($request->getMethod() !== 'GET' && strpos($request->getHeaderLine('content-type'), 'multipart/form-data') === false) {
            $stringToBeSigned .= (string) $request->getBody();
        }

        // 5. Wrap string generated in step 3 with app_secret.
        $stringToBeSigned = $this->getAppSecret() . $stringToBeSigned . $this->getAppSecret();

        // Encode the digest byte stream in hexadecimal and use sha256 to generate sign with salt(secret).
        $params['sign'] = hash_hmac('sha256', $stringToBeSigned, $this->getAppSecret());
    }

    /**
     * Magic call resource
     *
     * @param $resourceName
     * @throws TiktokShopException
     * @return mixed
     */
    public function __get($resourceName)
    {
        $resourceClassName = __NAMESPACE__."\\Resources\\".$resourceName;
        if (!in_array($resourceClassName, self::resources)) {
            throw new TiktokShopException("Invalid resource ".$resourceName);
        }

        //Initiate the resource object
        $resource = new $resourceClassName();
        if (!$resource instanceof Resource) {
            throw new TiktokShopException("Invalid resource class ".$resourceClassName);
        }

        $resource->useVersion($this->version);
        //$resource->useHttpClient($this->httpClient());
        $resource->useOption($this->httpClientOptions());

        return $resource;
    }

}
