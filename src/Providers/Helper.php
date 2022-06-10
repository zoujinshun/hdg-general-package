<?php

namespace Vaedly\HdgGeneralPackage\Providers;

use Vaedly\HdgGeneralPackage\Repository\Repository;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Arr;

class Helper
{
    public function responseProvider($array)
    {
        return response()->json([
            'status' => Arr::get($array, 0),
            'data' => Arr::get($array, 1),
            'message' => Arr::get($array, 2, '')
        ]);
    }

    public function getEnvConfig($key)
    {
        $redis_key = "domain_list";
        if (Redis::get($redis_key)) {
            $domain_list = json_decode(Redis::get($redis_key), true);
            return Arr::get($domain_list, $key . '.url');
        }

        $repository = new Repository();
        $table = 'domain';
        $domain_list = $repository->getList($table, []);
        $list = [];
        foreach ($domain_list as $item) {
            $list[$item->key] = (array)$item;
        }

        Redis::setex($redis_key, 86400, json_encode($list));
        return Arr::get($list, $key . '.url');
    }
}
