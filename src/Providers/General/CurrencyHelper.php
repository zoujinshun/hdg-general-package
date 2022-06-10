<?php
declare(strict_types=1);
namespace Vaedly\HdgGeneralPackage\Providers\General;

/**
 * 处理货币相关类
 * Class Currency
 * @package Vaedly\HdgGeneralPackage\Providers\General
 */
class CurrencyHelper
{
    /**
     * 价格由元转分
     * @param float $price 金额,单位：元
     * @return int 单位：分
     */
    public function priceYuan2Fen(float $price): int
    {
        return (int)(new MathHelper())->highPrecisionCalculate($this->priceFormat($price), '100', '*');
    }

    /**
     * 价格由分转元
     * @param int $price 金额，单位：分
     * @return float 单位：元
     */
    public function priceFen2Yuan(int $price): float
    {
        return round((float)(new MathHelper())->highPrecisionCalculate((string)$price, '100', '/'), 2);
    }

    /**
     * 计算手续费
     * @param float $price 金额，单位：分
     * @param int $all 总份额
     * @param int $rate 费率
     * @return int 手续费，单位: 分
     */
    public function calculationFee(float $price, int $rate, int $all = 10000): int
    {
        $math = new MathHelper();
        return (int)$math->highPrecisionCalculate($math->highPrecisionCalculate((string)$price, (string)$rate, '*'), (string)$all, '/');
    }

    /**
     * 价格格式化
     *
     * @param float $price 金额
     * @return string 格式化后金额   $price_format
     */
    public function priceFormat(float $price): string
    {
        return number_format($price, 2, '.', '');
    }
}