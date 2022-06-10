<?php
declare(strict_types=1);

namespace Vaedly\HdgGeneralPackage\Providers\General;

use Carbon\Carbon;

class DateHelper
{
    /**
     * 格式化日期为 Y-m-d
     * @param string $date
     * @return string
     */
    public function formatDate(string $date, string $year = ''): string
    {
        empty($year) && $year = date('Y');
        $ymd = explode('-', $date);
        count($ymd) == 2 && $date = $year . '-' . $date;
        return $date;
    }

    /**
     * ymd格式转md
     * @param string $date
     * @return string
     */
    public function dateYmd2Md(string $date): string
    {
        if ($this->isMdDate($date)) {
            return $date;
        }

        [, $month, $day] = explode('-', $date);
        return $month . '-' . $day;
    }

    /**
     * 是否y-m-d格式日期
     * @param string $date
     * @return bool
     */
    public function isYmdDate(string $date): bool
    {
        $pattern = "/^\d{4}-\d{2}-\d{2}$/";
        return preg_match($pattern, $date) ? true : false;
    }

    /**
     * 是否m-d格式日期
     * @param string $date
     * @return bool
     */
    public function isMdDate(string $date): bool
    {
        $pattern = "/^\d{2}-\d{2}$/";
        return preg_match($pattern, $date) ? true : false;
    }

    /**
     * 格式化月份
     * @param string $month
     * @return string
     */
    public function formatMonth(string $month): string
    {
        strlen($month) == 1 && $month = '0' . $month;
        return $month;
    }

    /**
     * 格式化日
     * @param string $day
     * @return string
     */
    public function formatDay(string $day): string
    {
        strlen($day) == 1 && $day = '0' . $day;
        return $day;
    }

    /**
     * 判断某日期是否在指定范围区间
     * @param string $date 需要判断的日期
     * @param string $start 区间开始日期
     * @param string $end 区间结束日期
     * @return bool
     */
    public function checkDateInInterval(string $date, string $start, string $end): bool
    {
        $date_time = strtotime($date);
        $start_time = strtotime($start);
        $end_time = strtotime($end);
        return ($date_time >= $start_time) && ($date_time <= $end_time);
    }

    /**
     * 比较日期
     * @param string $date1
     * @param string $date2
     * @return int
     */
    public function compareDate(string $date1, string $date2): int
    {
        return strtotime($date1) <=> strtotime($date2);
    }

    /**
     * 计算日期差多少天
     * @param string $date1
     * @param string $date2
     * @return float|int
     */
    public function computeDiffDays(string $date1 = '', string $date2 = ''): int
    {
        $diff = 0;
        if (!$date1) {
            return $diff;
        }
        $date2 = $date2 ?: date('Y-m-d', time());
        $diff = intval(round((strtotime($date1) - strtotime($date2)) / 86400));
        $diff = ($diff < 0) ? -1 : $diff;
        return $diff;
    }

    /**
     * 时间选择器
     * @param int $type
     * @param bool $is_timestamp
     * @return array
     */
    public function timeSelect(int $type = 1, bool $is_timestamp = true): array
    {
        $start_build = null;
        $end_build = null;
        switch ($type) {
            case 1:
                $start_build = mktime(0, 0, 0, (int)date('m'), (int)date('d'), (int)date('Y'));
                $end_build = mktime(0, 0, 0, (int)date('m'), (int)date('d') + 1, (int)date('Y')) - 1;
                break;
            case 2:
                $start_build = mktime(0, 0, 0, (int)date("m"), (int)date("d") - (int)date("w") + 1, (int)date("Y"));
                $end_build = mktime(23, 59, 59, (int)date("m"), (int)date("d") - (int)date("w") + 7, (int)date("Y"));
                break;
            case 3:
                $start_build = mktime(0, 0, 0, (int)date('m'), 1, (int)date('Y'));
                $end_build = mktime(23, 59, 59, (int)date('m'), (int)date('t'), (int)date('Y'));
                break;
            case 4:
                $start_build = mktime(0, 0, 0, (int)date('m'), (int)date('d') - 1, (int)date('Y'));
                $end_build = mktime(23, 59, 59, (int)date('m'), (int)date('d') - 1, (int)date('Y'));
                break;
            case 6:
                $start_build = mktime(0, 0, 0, (int)date('m') - 3, (int)date('d'), (int)date('Y'));
                $end_build = mktime(0, 0, 0, (int)date('m'), (int)date('d') + 1, (int)date('Y')) - 1;
                break;
            case 7:
                $start_build = mktime(0, 0, 0, (int)date('m') - 6, (int)date('d'), (int)date('Y'));
                $end_build = mktime(0, 0, 0, (int)date('m'), (int)date('d') + 1, (int)date('Y')) - 1;
                break;
            default:
                $start_build = mktime(0, 0, 0, (int)date('m'), (int)date('d'), (int)date('Y'));
                $end_build = mktime(0, 0, 0, (int)date('m'), (int)date('d') + 1, (int)date('Y')) - 1;
                break;
        }

        return [
            'start' => $is_timestamp ? $start_build : date('Y-m-d H:i:s', $start_build),
            'end' => $is_timestamp ? $end_build : date('Y-m-d H:i:s', $end_build)
        ];
    }

    /**
     * 格式化日期
     * @param $time
     * @param string $format
     * @return string
     */
    public function formatAndJudgeDate($time, string $format = 'Y-m-d H:i:s'): string
    {
        if (!$time) {
            return '';
        }
        return date($format, $time);
    }

    /**
     * 时间戳转为
     * @param int $start_time
     * @param int $end_time
     * @return string
     */
    public function timeFormat(int $start_time, int $end_time = 0): string
    {
        if (!$end_time) {
            $end_time = time();
        }
        $time = (int)substr((string)$start_time, 0, 10);
        $int = $end_time - $time;
        if ($int <= 30) {
            $str = sprintf('刚刚', $int);
        } elseif ($int < 60) {
            $str = sprintf('%d秒前', $int);
        } elseif ($int < 3600) {
            $str = sprintf('%d分钟前', floor($int / 60));
        } elseif ($int < 86400) {
            $str = sprintf('%d小时前', floor($int / 3600));
        } elseif ($int < 604800) {
            $str = sprintf('%d天前', floor($int / 86400));
        } elseif ($int < 2592000) {
            $str = sprintf('%d周前', floor($int / 604800));
        } elseif ($int < 31536000) {
            $str = sprintf('%d月前', floor($int / 2592000));
        } elseif ($int < 946080000) {
            $str = sprintf('%d年前', floor($int / 31536000));
        } else {
            $str = date('Y-m-d H:i:s', $time);
        }
        return $str;
    }

    /**
     * 过去多少时间
     * @param int $start_time
     * @param int $end_time
     * @return string
     */
    public function longTimeFormat(int $start_time, int $end_time = 0): string
    {
        if (!$end_time) {
            $end_time = time();
        }
        $time = (int)substr((string)$start_time, 0, 10);
        $int = $end_time - $time;
        if ($int <=30) {
            $str = sprintf('%d秒', $int);
        } elseif ($int < 3600) {
            $str = sprintf('%d分钟', floor($int / 60));
        } elseif ($int < 86400) {
            $str = sprintf('%d小时', floor($int / 3600));
        } elseif ($int < 604800) {
            $str = sprintf('%d天', floor($int / 86400));
        } elseif ($int < 2592000) {
            $str = sprintf('%d周', floor($int / 604800));
        } elseif ($int < 31536000) {
            $str = sprintf('%d月', floor($int / 2592000));
        } elseif ($int < 946080000) {
            $str = sprintf('%d年', floor($int / 31536000));
        } else {
            $str = date('Y-m-d H:i:s', $time);
        }
        return $str;
    }
}
