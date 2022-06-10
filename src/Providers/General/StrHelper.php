<?php
declare(strict_types=1);
namespace Vaedly\HdgGeneralPackage\Providers\General;

/**
 * 字符串处理类
 * Class StrHelper
 * @package Vaedly\HdgGeneralPackage\Providers\General
 */
class StrHelper
{
    /**
     * 指定替换字符
     * @param string $str
     * @param int $start
     * @param int $length
     * @param string $replace
     * @return string
     */
    public function strSpecifiReplace(string $str, int $start = 3, int $length = 4, string $replace = '*'): string
    {
        $replace_str = str_repeat($replace, $length);
        return substr_replace($str, $replace_str, $start, $length);
    }

    /**
     * 字符串超过长度省略号代替
     * @param string $string
     * @param int $limit_length
     * @return string
     */
    public function omitString(string $string, int $limit_length): string
    {
        if (mb_strlen($string, 'utf8') > $limit_length) {
            return mb_substr($string, 0, $limit_length - 3, 'utf8') . '...';
        } else {
            return $string;
        }
    }

    /**
     * 随机字符串
     * @param int $length
     * @return string
     */
    public function randString(int $length = 6): string
    {
        $str = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $len = strlen($str) - 1;
        $randstr = '';
        for ($i = 0; $i < $length; $i++) {
            $num = mt_rand(0, $len);
            $randstr .= $str[$num];
        }
        return $randstr;
    }

    /**
     * 截取并加上省略号
     * @param string $string
     * @param int $length
     * @param string $omit_str
     * @return string
     */
    public function substrAndOmit(string $string, int $length = 30, $omit_str = '...'): string
    {
        $str_len = strlen($string);
        if ($str_len <= $length) {
            return $string;
        }
        return mb_substr($string, 0, $length) . $omit_str;
    }
}
