<?php
declare(strict_types=1);
namespace Vaedly\HdgGeneralPackage\Providers\General;

/**
 * 日志辅助类
 * Class LogHelper
 * @package Vaedly\HdgGeneralPackage\Providers\General
 */
class LogHelper
{
    public function writeLog($log, string $filename = ''): void
    {
        try {
            if (!empty($log)) {
                $filename = $filename ?: 'log' . date('Ymd') . '.log';
                $logFile = fopen(storage_path('logs' . DIRECTORY_SEPARATOR . $filename), 'a+');
                if (is_array($log) || is_object($log)) {
                    $log = json_encode($log);
                }
                fwrite($logFile,  date('Y-m-d H:i:s', time()) . PHP_EOL . $log . PHP_EOL);
                fclose($logFile);
            }
        } catch (\Exception $exception) {
        }
    }
}