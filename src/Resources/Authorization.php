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

use Kkgerry\TiktokShop\Resource;

class Authorization extends Resource
{
    protected $category = 'authorization';

    public function getAuthorizedShop()
    {
        return $this->call('GET', 'shops');
    }
}
