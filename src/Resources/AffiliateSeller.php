<?php
/*
 * This file is part of tiktok-shop.
 *
 * (c) Jin <j@sax.vn>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Kkgerry\TiktokShop\Resources;

use GuzzleHttp\RequestOptions;
use Kkgerry\TiktokShop\Resource;


class AffiliateSeller extends Resource
{
    CONST DEFAULT_VERSION = '202406';
    CONST DEFAULT_VERSION_10 = '202410';

    protected $category = 'affiliate_seller';

    public function getMarketplaceCreatorsSearch($query = [], $body = null)
    {
        if ($body === null) {
            static::extractParams($query, $query, $body);
        }

        return $this->useVersion(self::DEFAULT_VERSION)->call('POST', 'marketplace_creators/search',[
            RequestOptions::QUERY => $query,
            is_array($body) ? RequestOptions::JSON : RequestOptions::FORM_PARAMS => $body,
        ]);
    }

    public function getMarketplaceCreators($creator_user_id)
    {
        return $this->useVersion(self::DEFAULT_VERSION)->call('GET', 'marketplace_creators/'.$creator_user_id);
    }
    
    public function getSellerAffiliateOrders($query = [], $body = null)
    {
        if ($body === null) {
            static::extractParams($query, $query, $body);
        }

        return $this->useVersion(self::DEFAULT_VERSION_10)->call('POST', 'orders/search',[
            RequestOptions::QUERY => $query,
            is_array($body) ? RequestOptions::JSON : RequestOptions::FORM_PARAMS => $body,
        ]);
    }
}
