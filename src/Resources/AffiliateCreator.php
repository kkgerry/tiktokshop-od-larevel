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

class AffiliateCreator extends Resource
{
    CONST DEFAULT_VERSION = '202405';

    protected $category = 'affiliate_creator';

    public function getProfiles()
    {
        return $this->useVersion(self::DEFAULT_VERSION)->call('GET', 'profiles');
    }

}
