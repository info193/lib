<?php
/**
 * Created by vim.
 * User: huguopeng
 * Date: 2022/10/28
 * Time: 09:09:02
 * By: RedisBase.php
 */

namespace lumenFrame\Base\redis;

use common\Errno;
use frame\Exceptions\BaseException;
use Illuminate\Support\Facades\Cache;

abstract class RedisBase
{
    /**
     * 指定缓存key的组成
     *
     * @return array
     */
    abstract public static function primaryKey();

    /**
     * redis 保存数据的方式
     */
    abstract public function redisSave();

    /**
     * @return object|null
     */
    public static function getDb()
    {
        return Cache::store('redis');
    }

    /**
     * 必须配置属性
     *
     * @inheritDoc
     */
    public function attributes()
    {
        throw new BaseException(Errno::SERVER_INTERNAL_FAIL, 'The attributes() method of redis ActiveRecord has to be implemented by child classes.');

    }

    public static function keyPrefix()
    {
        return mb_strtolower(static::basename(get_called_class()), 'UTF-8');
    }

    public function beforeSave()
    {
    }

    public function afterSave()
    {
    }

    public static function findOne($condition)
    {
        $data = static::getDb()->get(self::genRedisKeyByCondition($condition));
        return static::stringReversalObject($data);
    }

    private function _validate()
    {
        foreach ($this->rules() as $rules) {
            foreach ($rules[0] as $rule) {
                if (!isset($this->$rule)) {
                    throw new BaseException(Errno::SERVER_INTERNAL_FAIL, 'The attributes() The parameter ' . $rule . ' key empty.');
                }
                if ($rules[1] == 'integer' && !is_integer($this->$rule)) {
                    throw new BaseException(Errno::SERVER_INTERNAL_FAIL, 'The attributes() The parameter value must be an integer.');
                }
                if ($rules[1] == 'float' && !is_float($this->$rule)) {
                    throw new BaseException(Errno::SERVER_INTERNAL_FAIL, 'The attributes() The parameter value must be an float.');
                }
                if ($rules[1] == 'string' && !is_string($this->$rule)) {
                    throw new BaseException(Errno::SERVER_INTERNAL_FAIL, 'The attributes() The parameter value must be an string.');
                }
                if ($rules[1] == 'date') {
                    $info = date_parse_from_format('Y-m-d', $this->$rule);
                    if (!(0 == $info['warning_count'] && 0 == $info['error_count'])) {
                        throw new BaseException(Errno::SERVER_INTERNAL_FAIL, 'The attributes() The parameter value must be an date.');
                    }
                }
                if ($rules[1] == 'datetime') {
                    $info = date_parse_from_format('Y-m-d H:i:s', $this->$rule);
                    if (!(0 == $info['warning_count'] && 0 == $info['error_count'])) {
                        throw new BaseException(Errno::SERVER_INTERNAL_FAIL, 'The attributes() The parameter value must be an datetime.');
                    }
                }
            }
        }
    }

    public function save()
    {
        $this->_validate();
        $this->beforeSave();
        $this->redisSave();
        $this->afterSave();
        return true;
    }

    /**
     * 生成redis key
     *
     * @return string
     */
    public function genRedisKey(): string
    {
        $primaryKeys = [];
        foreach (static::primaryKey() as $k) {
            if (!isset($this->$k)) {
                throw new BaseException(Errno::SERVER_INTERNAL_FAIL, 'The primaryKey key data not set.');
            }
            $primaryKeys [] = $this->$k;
        }

        $list = [static::keyPrefix(),];
        $list[] = implode('-', $primaryKeys);
        return implode(':', $list);
    }

    /**
     * 通过条件生成redis key
     *
     * @param array|string $condition
     *
     * @return string
     */
    public static function genRedisKeyByCondition($condition)
    {
        if (is_array($condition)) {
            $primaryKes = [];
            foreach (static::primaryKey() as $key) {
                foreach ($condition as $k => $v) {
                    if ($key == $k) {
                        $primaryKes [] = $v;
                    }
                }
            }
            $primaryPart = implode('-', $primaryKes);
        } else {
            $primaryPart = $condition;
        }

        $list = [static::keyPrefix(),];

        $list[] = $primaryPart;
        return implode(':', $list);
    }


    /**
     * 设置key的有效期
     *
     * @param int $sec 秒
     */
    public function setExpireSecond(int $sec)
    {
        static::getDb()->expire($this->genRedisKey(), $sec);
    }

    /**
     * 数据转换为json
     * @return false|string
     * @throws InvalidConfigurationException
     */
    public function toJson()
    {
        $arr = [];
        foreach ($this->attributes() as $k) {
            $arr [$k] = $this->$k;
        }

        return json_encode($arr);
    }

    /**
     * json数据转换对象
     * @return false|string
     * @throws InvalidConfigurationException
     */
    public static function stringReversalObject($data = '')
    {
        $obj = new static();
        $class = get_class($obj);
        $class = new $class();
        if (!$data) {
            return false;
        }
        $data = json_decode($data, true);
        foreach ($data as $k => $v) {
            $class->$k = $v;
        }
        return $class;
    }

    /**
     * json数据转换对象
     * @return false|string
     * @throws InvalidConfigurationException
     */
    public static function arrayReversalObject($data = [])
    {
        $obj = new static();
        $class = get_class($obj);
        $class = new $class();
        if (!$data) {
            return false;
        }
        foreach ($data as $k => $v) {
            $class->$k = $v;
        }
        return $class;
    }


    public static function basename($path, $suffix = '')
    {
        $len = mb_strlen($suffix);
        if ($len > 0 && mb_substr($path, -$len) === $suffix) {
            $path = mb_substr($path, 0, -$len);
        }
        $path = rtrim(str_replace('\\', '/', $path), '/');
        $pos = mb_strrpos($path, '/');
        if ($pos !== false) {
            return mb_substr($path, $pos + 1);
        }

        return $path;
    }


}
