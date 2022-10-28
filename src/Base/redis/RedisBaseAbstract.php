<?php
/**
 * Created by vim.
 * User: huguopeng
 * Date: 2022/10/28
 * Time: 09:09:02
 * By: RedisBaseAbstract.php
 */

namespace lumenFrame\Base\redis;

class RedisBaseAbstract extends RedisBase
{
    public static function primaryKey()
    {
        return [];
    }

    public function redisSave()
    {
    }

}
