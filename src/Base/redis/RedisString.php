<?php
/**
 * Created by vim.
 * User: huguopeng
 * Date: 2022/10/28
 * Time: 09:09:02
 * By: RedisString.php
 */

namespace lumenFrame\Base\redis;

/**
 * 使用示例：
 * Demo::deleteOne(string|array); 删除缓存
 * Demo::findOne(string|array);  // 查询缓存
 */
abstract class RedisString extends RedisBase
{
    public function redisSave()
    {
        static::getDb()->set($this->genRedisKey(), $this->toJson());
    }

    public static function deleteOne($condition)
    {
        static::getDb()->del(static::genRedisKeyByCondition($condition));
    }

}
