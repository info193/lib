<?php
/**
 * Created by vim.
 * User: huguopeng
 * Date: 2022/10/28
 * Time: 09:09:02
 * By: Route.php
 */
namespace lumenFrame\Library;

use common\Errno;
use frame\Exceptions\BaseException;

class Route
{
    public static function getCurrentRoute($route)
    {
        if (!$route) {
            // 路由不存在
            throw new BaseException(Errno::SERVER_REQUEST_ROUTING_EXISTS_FAIL);
        }
        list($class, $method) = explode('@', $route);
        $route = [
            'controller' => str_replace('Controller', '', substr(strrchr($class, '\\'), 1)),
            'action' => $method
        ];
        return $route;
    }
}