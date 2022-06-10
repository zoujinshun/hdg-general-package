<?php

namespace Vaedly\HdgGeneralPackage\Repository;

use Vaedly\HdgGeneralPackage\Service\WorkOrderService;
use Illuminate\Support\Facades\DB;

/**
 * Class WorkOrderRepository
 * @package Vaedly\HdgGeneralPackage\Repository
 */
class WorkOrderRepository
{
    /**
     * 指定条件工单是否存在
     * @param array $where
     * @return bool
     */
    public function existsWorkOrder(array $where): bool
    {
        return (bool)DB::table('work_order')->where($where)->exists();
    }

    /**
     * 今日工单序号
     * @param int $source_type
     * @return string
     */
    public function getTodayWorkOrderCodeNumber(int $source_type = 0): string
    {
        $work_order_codes =  DB::table('work_order')
            ->where('source_type', $source_type)
            ->pluck('work_order_code')
            ->toArray();
        $last = last($work_order_codes);
        if (!$last) {
            $number = 1;
        } else {
            $number = substr($last, -3);
            $number++;
        }
        $number = str_pad($number,3,"0",STR_PAD_LEFT);
        return $number;
    }

    /**
     * 分页获取工单数据
     * @param array $where
     * @param array $field
     * @param int $page
     * @param int $page_size
     * @return array
     */
    public function getWorkOrderListForPage(array $where, array $field, int $page, int $page_size): array
    {
        $res = DB::table('work_order')
            ->when($where, function ($query) use($where) {
                $query->where($where);
            })
            ->select($field)
            ->orderBy('id', 'DESC')
            ->forPage($page)
            ->paginate($page_size);
        return [
            'items' => $res->items(),
            'total' => $res->total()
        ];
    }

    /**
     * 获取工单列表数据
     * @param array $where
     * @param array $field
     * @return array
     */
    public function getWorkOrderList(array $where, array $field): array
    {
        return DB::table('work_order')
            ->when($where, function ($query) use($where) {
                $query->where($where);
            })
            ->get($field)
            ->transform(function ($value) {
                return (array)$value;
            })
            ->toArray();
    }

    /**
     * 获取工单详情
     * @param array $where
     * @param array $field
     * @return object
     */
    public function getWorkOrderInfo(array $where, array $field)
    {
        return DB::table('work_order')->where($where)->first($field);
    }

    /**
     * 创建工单
     * @param array $data
     * @return int
     */
    public function createWorkOrder(array $data): int
    {
        $data['create_time'] = time();
        $data['update_time'] = time();
        return (int)DB::table('work_order')->insertGetId($data);
    }

    /**
     * 更新工单
     * @param array $where
     * @param array $data
     * @return bool
     */
    public function updateWorkOrder(array $where, array $data): bool
    {
        $data['update_time'] = time();
        return (bool)DB::table('work_order')->where($where)->update($data);
    }

    /**
     * 插入变更记录表
     * @param array $data
     * @return int
     */
    public function insertWorkOrderUpdateLog(array $data): int
    {
        $data['create_time'] = time();
        return (int)DB::table('work_order_update_log')->insertGetId($data);
    }

    /**
     * 获取工单数
     * @param array $where
     * @return int
     */
    public function getWorkOrderCount(array $where): int
    {
        return DB::table('work_order')
            ->when($where, function ($query) use($where) {
                $query->where($where);
            })
            ->count();
    }

    /**
     * 评论是否存在
     * @param array $where
     * @return bool
     */
    public function existsComment(array $where): bool
    {
        return DB::table('work_order_comment')->where($where)->exists();
    }

    /**
     * 插入评论
     * @param array $data
     * @return int
     */
    public function insertComment(array $data): int
    {
        $data['create_time'] = time();
        $data['update_time'] = time();
        return DB::table('work_order_comment')->insertGetId($data);
    }

    /**
     * 插入评论回复
     * @param array $data
     * @return int
     */
    public function insertCommentReply(array $data): int
    {
        $data['create_time'] = time();
        return DB::table('work_order_comment_reply')->insertGetId($data);
    }

    /**
     * 更新记录
     * @param array $where
     * @return array
     */
    public function getUpdateLogList(array $where): array
    {
        return DB::table('work_order_update_log')
            ->where($where)
            ->orderByDesc('id')
            ->get()
            ->transform(function ($value) {
                return (array)$value;
            })
            ->toArray();
    }

    /**
     * 获取评论数据
     * @param array $where
     * @param array|string[] $field
     * @return array
     */
    public function getCommentList(array $where, array $field = ['*']): array
    {
        return DB::table('work_order_comment')
            ->where($where)
            ->get($field)
            ->transform(function ($value) {
                $value->create_time = date('Y-m-d H:i:s', $value->create_time);
                $value->update_time = date('Y-m-d H:i:s', $value->update_time);
                return (array)$value;
            })
            ->toArray();
    }

    /**
     * 获取回复数据
     * @param array $where
     * @param array|string[] $field
     * @return array
     */
    public function getReplyList(array $where, array $field = ['*']): array
    {
        return DB::table('work_order_comment_reply')
            ->where($where)
            ->get($field)
            ->transform(function ($value) {
                $value->create_time = date('Y-m-d H:i:s', $value->create_time);
                return (array)$value;
            })
            ->toArray();
    }

}
