<?php

namespace Vaedly\HdgGeneralPackage\Providers;

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
}
