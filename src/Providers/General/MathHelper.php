<?php
declare(strict_types=1);
namespace Vaedly\HdgGeneralPackage\Providers\General;

class MathHelper
{
    /**
     * 高精计算
     * @param $n1 操作数1
     * @param $n2 操作数2
     * @param $symbol 操作类型 + - * / ...
     * @param string $scale 保留小数位
     * @return string
     */
    public function highPrecisionCalculate(string $n1, string $n2, string $symbol, int $scale = 2): string
    {
        switch ($symbol) {
            case '+'://加法
                $res = bcadd($n1, $n2, $scale);
                break;
            case '-'://减法
                $res = bcsub($n1, $n2, $scale);
                break;
            case '*'://乘法
                $res = bcmul($n1, $n2, $scale);
                break;
            case '/'://除法
                $res = bcdiv($n1, $n2, $scale);
                break;
            case '%'://求余、取模
                $res = bcmod($n1, $n2, $scale);
                break;
            default:
                $res = '';
                break;
        }
        return $res;
    }


    /**
     * 判断两个数是否相等
     * @param  $n1
     * @param  $n2
     * @return bool
     */
    public function isEqual(float $n1, float $n2): bool
    {
        $delta = 0.00001;
        return (abs($n1 - $n2) < $delta);
    }

}