<?php
/**
 * 转发请求
 * Created by PhpStorm.
 * User: Administrator
 * Time: 2025-04-10 14:53
 */
namespace Kkgerry\TiktokShop;


trait TikTokForwardRequestTrait
{

    protected function getForwardUrl($op='')
    {
        $tiktokApiForwardeHost = getenv('TK_API_FW_URI');
        //$tiktokApiForwardeHost = 'http://local.tk.api.us.me';
        if($op == 'auth'){
            $tiktokApiForwardeHost .= '/api/tk/auth';
        }else{
            $tiktokApiForwardeHost .= '/api/tk/request';
        }

        $forwardToken = getenv('TK_API_FW_TOKEN');
        $forwardSecret = getenv('TK_API_FW_SECRET');

        $sign = md5($forwardSecret.$tiktokApiForwardeHost.$forwardSecret.$forwardToken);

        return $tiktokApiForwardeHost.'?fw_sign='.$sign;

    }
    
}