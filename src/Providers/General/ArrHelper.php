<?php
declare(strict_types=1);
namespace Vaedly\HdgGeneralPackage\Providers\General;

class ArrHelper
{
    /**
     * 二维数组根据某字段的值查找index
     * @param array $arr
     * @param string $field
     * @param $value
     * @return int
     */
    public function searchIndexByFieldInMultipleArr(array $arr, string $field, $value): int
    {
        $i = -99;
        foreach ($arr as $index => $item) {
            if ($item[$field] == $value) {
                $i = $index;
                break;
            }
        }
        return $i;
    }

    /**
     * 二维数组根据某个字段的值替换该元素子数组的index
     * @param array $arr
     * @param string $field\
     */
    public function assemblySetIndexByFieldMultipleArr(array $arr, string $field): array
    {
        $new_arr = [];
        foreach ($arr as $item) {
            if (is_object($item)) {
                $item = (array)$item;
            }
            $new_arr[$item[$field]] = $item;
        }
        return $new_arr;
    }

    /**
     * 获取数组中指定元素
     * @param array $arr
     * @param array $fields
     * @return array
     */
    public function getAssignElement(array $arr, array $fields): array
    {
        foreach ($arr as $key => $value) {
            if (!in_array($key, $fields)) {
                unset($arr[$key]);
            }
        }
        return $arr;
    }
}
