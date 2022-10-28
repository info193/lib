<?php
/**
 * Created by vim.
 * User: huguopeng
 * Date: 2022/10/28
 * Time: 09:09:40
 * By: Terminal.php
 */

namespace lumenFrame\Library;

class Terminal
{
    const UNKNOWN = 0;
    const PC = 1;
    const WAP = 2;
    const IOS = 3;
    const ANDROID = 4;
    const WEIXIN = 5;

    /**
     * @param string $terminal
     * @return int
     */
    public static function getTerminalType(string $terminal = '')
    {
        if (!empty($terminal)) {
            return self::_reverseTerminal($terminal);
        }

        $agent = strtolower($_SERVER['HTTP_USER_AGENT']);

        if (preg_match('/win/i', $agent)) {
            return self::PC;
        } else if (preg_match('/mac/i', $agent) && preg_match('/pc/i', $agent)) {
            return self::PC;
        } else if (strpos($agent, 'android') !== false) {
            return self::ANDROID;
        } elseif (strpos($agent, 'iphone') !== false) {
            return self::IOS;
        } elseif (strpos($agent, 'ipad') !== false) {
            return self::IOS;
        } elseif (strpos($agent, 'micromessenger') !== false) {
            return self::WEIXIN;
        } else {
            return self::UNKNOWN;
        }
    }

    /**
     * @param int $type
     * @return string
     */
    public static function getReverseType(int $type = 0)
    {
        switch ($type) {
            case 0:
                return 'unknown';
                break;
            case 1:
                return 'pc';
                break;
            case 2:
                return 'wap';
                break;
            case 3:
                return 'ios';
                break;
            case 4:
                return 'android';
                break;
            case 5:
                return 'weixin';
                break;
            default:
                return 'unknown';
                break;
        }
    }

    /**
     * @param string $terminal
     * @return int
     */
    private static function _reverseTerminal(string $terminal = '')
    {
        if (empty($terminal) || $terminal === 'unknown') {
            return self::UNKNOWN;
        }
        if ($terminal === 'pc') {
            return self::PC;
        }
        if ($terminal === 'wap') {
            return self:WAP;
		}
        if ($terminal === 'ios') {
            return self::IOS;
        }
        if ($terminal === 'android') {
            return self::ANDROID;
        }
        if ($terminal === 'weixin') {
            return self::WEIXIN;
        }
    }
}