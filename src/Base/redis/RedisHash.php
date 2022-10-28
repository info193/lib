<?php
/**
 * Created by vim.
 * User: huguopeng
 * Date: 2022/10/28
 * Time: 09:09:02
 * By: RedisHash.php
 */

namespace lumenFrame\Base\redis;

/**
 * redis hash封装
 * 该类使用 primaryKey中的数组字段作为缓存key的一部分，并使用attributes作为hash的field部分，
 * 使用示例：
 *
 * 使用示例：
 * Demo::deleteOne(string|array,""|array); 删除缓存
 * Demo::findOne(string|array);  // 查询缓存
 */
abstract class RedisHash extends RedisBase
{
    /**
     * 指定hash中的field 组成
     *
     * @return array
     */
    public static function findOne($condition, $field = '')
    {
        if (!$field) {
            $data = static::getDb()->hgetall(self::genRedisKeyByCondition($condition));
            return static::arrayReversalObject($data);
        }
        if (!is_array($field) && $field) {
            $data = static::getDb()->hget(self::genRedisKeyByCondition($condition), $field);
            return static::arrayReversalObject([$field => $data]);
        }
        if (is_array($field) && count($field) >= 1) {
            $data = static::getDb()->hmget(self::genRedisKeyByCondition($condition), $field);
            return static::arrayReversalObject($data);
        }

    }

    public function redisSave()
    {
        //redis4.0之后官方弃用hmset方法  phpredis5.3.7最新扩展不支持 hset多字段，官方扩展包更新后可改为hset
        static::getDb()->hmset($this->genRedisKey(), $this->genRedisField());
    }

    public static function deleteOne($condition, $field = '')
    {
        if (!$field) {
            static::getDb()->del(static::genRedisKeyByCondition($condition));
        }
        if (!is_array($field) && $field) {
            static::getDb()->hdel(static::genRedisKeyByCondition($condition), $field);
        }
        if (is_array($field) && count($field) >= 1) {
            foreach ($field as $value) {
                static::getDb()->hdel(static::genRedisKeyByCondition($condition), $value);
            }
        }
        return true;
    }

    /**
     * 生成hash field
     *
     * @return string
     */
    protected function genRedisField()
    {
        $fieldKeys = [];
        foreach ($this->attributes() as $k) {
            if (isset($this->$k)) {
                $fieldKeys[$k] = $this->$k;
            }
        }
        return $fieldKeys;
    }

}
