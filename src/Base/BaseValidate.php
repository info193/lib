<?php
/**
 * Created by vim.
 * User: huguopeng
 * Date: 2022/10/28
 * Time: 09:09:02
 * By: BaseValidate.php
 */
namespace lumenFrame\Library;

use common\Errno;
use frame\Exceptions\BaseException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Laravel\Lumen\Http\Request;

class BaseValidate
{
    public $rules = [];
    public $attributes = [];
    public $message = [];
    private $_default = [];

    public function vaild(Request $request, $className = null)
    {
        Log::withContext(['params' => $request->all()]);
        if ($this->rules) {
            foreach ($this->rules as $key => $rule) {
                if (strpos($rule, 'default') !== false) {
                    $rows = explode('|', $rule);
                    foreach ($rows as $row) {
                        if (strpos($row, 'default') !== false) {
                            $value = explode(':', $row);
                            $this->_default[$key] = isset($value[1]) ? $value[1] : '';
                            $this->rules[$key] = trim(str_replace($row, '', $this->rules[$key]), '|');
                        }
                    }
                }
            }
        }
        if (!$this->attributes) {
            $validator = Validator::make($request->all(), $this->rules, $this->message);
        } else {
            $validator = Validator::make($request->all(), $this->rules, $this->attributes, $this->message);
        }

        if ($validator->fails()) {
            $errors = $validator->errors()->first();
            // 记录日志
            Log::alert($errors, $request->input());
            throw new BaseException(Errno::PARAM_FAIL);
        }
        $validateds = $validator->validated();

        // 注入容器
        $app = app();
        $app->singleton($className, function () use ($validateds, $className) {
            $obj = new $className();
            foreach ($validateds as $key => $validated) {
                $obj->$key = $validated;
            }
            // 设置默认值
            if ($this->_default) {
                foreach ($this->_default as $key => $default) {
                    if (!isset($obj->$key)) {
                        $obj->$key = $default;
                    }
                }
            }
            return $obj;
        });

    }
}
