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

class Analytics extends Resource
{
    CONST DEFAULT_VERSION = '202409';
    CONST DEFAULT_VERSION_05 = '202405';

    protected $category = 'analytics';

    public function getAnalyticsVideoPerformance($videoId='',$query = [])
    {
        ///analytics/202409/shop_videos/{video_id}/products/performance
        return $this->useVersion(self::DEFAULT_VERSION)->call('GET', 'shop_videos/'.$videoId.'/products/performance',[
            RequestOptions::QUERY => $query
        ]);
    }
    public function getAnalyticsProductPerformance($productId='',$query=[])
    {
        //analytics/202405/shop_products/{product_id}/performance
        return $this->useVersion(self::DEFAULT_VERSION_05)->call('GET', 'shop_products/'.$productId.'/performance',[
            RequestOptions::QUERY => $query
        ]);
    }

}
