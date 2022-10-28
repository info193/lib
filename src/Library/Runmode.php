<?php
/**
 * Created by vim.
 * User: huguopeng
 * Date: 2022/10/28
 * Time: 09:09:02
 * By: Runmode.php
 */

namespace lumenFrame\Library;

class Runmode
{
    public static function get()
    {
        $mode = env('RUN_MODE');
        if ($mode == 'dev') {
            return 'dev';
        } else if ($mode == 'test') {
            return "test";
        } else if ($mode == 'pre') {
            return "pre";
        } else if ($mode == 'pro' || $mode == 'prod' || $mode == 'online') {
            return "online";
        } else {
            return "online";
        }
    }
}