<?php
declare(strict_types=1);

namespace Vaedly\HdgGeneralPackage\Providers\General;

use Illuminate\Support\Facades\Redis;

/**
 * 锁
 * Class LockHelper
 * @package Vaedly\HdgGeneralPackage\Providers\General
 */
class LockHelper
{
    /**
     * 锁值
     * @return string
     */
    public function getLockValue(): string
    {
        return md5(uniqid());
    }

    /**
     * redis分布式锁加锁
     * @param string $key
     * @param string $value
     * @param int $ttl
     * @return bool
     */
    public function redisLock(string $key, string $value, int $ttl): bool
    {
        return (bool)Redis::set($key, $value, 'NX', 'EX', $ttl);
    }

    /**
     * redis分布式锁解锁
     * @param string $key
     * @param string $value
     * @return bool
     */
    public function redisUnlock(string $key, string $value): bool
    {
        $res = Redis::get($key);
        if (!$res || $res != $value) {
            return false;
        }
        return (bool)Redis::del($key);
    }
}