<?php

namespace Vaedly\HdgGeneralPackage\Service;

use Illuminate\Support\Facades\DB;

class RunLogService
{
    /**
     * @param string $tag
     * @param string $action
     * @param array $params
     * @param string $desc
     * @param int $level
     */
    public static function info(string $tag, string $action, array $params, string $desc = '',$level = 0)
    {
        DB::table("run_logs")->insert([
            'tag' => $tag,
            'action' => $action,
            'params' => json_encode($params),
            'level' => $level,
            'date' => date("Y-m-d"),
            'create_time' => time(),
            'desc' => $desc
        ]);
    }
}