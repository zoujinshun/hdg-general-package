<?php
declare(strict_types=1);

namespace Vaedly\HdgGeneralPackage\Providers\General;

/**
 * token处理
 * Class TokenHelper
 * @package Vaedly\HdgGeneralPackage\Providers\General
 */
class TokenHelper
{
    /**
     * 生成普通token
     * @param string $string
     * @return string
     */
    public function createSimpleToken(string $string): string
    {
        $str = md5((string)uniqid(md5((string)microtime(true)), true));
        return sha1($str . $string);
    }
}
