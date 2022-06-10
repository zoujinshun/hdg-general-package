<?php

namespace Vaedly\HdgGeneralPackage\Providers;

use Vaedly\HdgGeneralPackage\Repository\Repository;
use Illuminate\Support\Facades\Redis;

class Helper
{
    public function responseProvider($array)
    {
        return response()->json([
            'status' => array_get($array, 0),
            'data' => array_get($array, 1),
            'message' => array_get($array, 2, '')
        ]);
    }

    public function getEnvConfig($key)
    {
        $redis_key = "domain_list";
        if (Redis::get($redis_key)) {
            $domain_list = json_decode(Redis::get($redis_key), true);
            return array_get($domain_list, $key . '.url');
        }

        $repository = new Repository();
        $table = 'domain';
        $domain_list = $repository->getList($table, []);
        $list = [];
        foreach ($domain_list as $item) {
            $list[$item->key] = (array)$item;
        }

        Redis::setex($redis_key, 86400, json_encode($list));
        return array_get($list, $key . '.url');
    }
}
