<?php

namespace Vaedly\HdgGeneralPackage\Repository;

use Illuminate\Support\Facades\DB;

class TestRepository
{
    public function test()
    {
        return DB::table('feishu_internal_user')->count();
    }
}