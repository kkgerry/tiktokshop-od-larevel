<?php
/*
 * This file is part of tiktokshop-client.
 *
 * (c) Jin <j@sax.vn>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Kkgerry\TiktokShop\Tests\Resources;

use Kkgerry\TiktokShop\Tests\TestResource;

/**
 * @property-read \Kkgerry\TiktokShop\Resources\Order $caller
 */
class OrderTest extends TestResource
{
    public function testGetOrderDetail()
    {
        $this->caller->getOrderDetail('sample order id');
        $this->assertPreviousRequest('get', 'order/'.TestResource::TEST_API_VERSION.'/orders');
    }

    public function testGetOrderList()
    {
        $this->caller->getOrderList();
        $this->assertPreviousRequest('post', 'order/'.TestResource::TEST_API_VERSION.'/orders/search');
    }
}
