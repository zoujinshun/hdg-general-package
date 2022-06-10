<?php

namespace Vaedly\HdgGeneralPackage\Repository;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

/**
 * Class FeishuRepository
 * @package Vaedly\HdgGeneralPackage\Repository
 */
class FeishuRepository
{
    //产品信息
    const PRODUCT_USER = [
        'name' => '程仕豪',
        'user_id' => '6e85b6d1',
    ];

    //测试信息
    const TEST_USER = [
        'name' => '王传玉',
        'user_id' => '17ffd3f3',
    ];

    /**
     * 是否存在指定条件飞书用户
     * @param array $where
     * @return bool
     */
    public function existsUser(array $where): bool
    {
        return (bool)DB::table('feishu_internal_user')->where($where)->exists();
    }

    /**
     * 创建用户
     * @param array $user
     * @return int
     */
    public function createUser(array $user): int
    {
        return (int)DB::table('feishu_internal_user')->insertGetId($user);
    }

    /**
     * 更新用户数据
     * @param array $where
     * @param array $data
     * @return bool
     */
    public function updateUser(array $where, array $data): bool
    {
        return (bool)DB::table('feishu_internal_user')->where($where)->update($data);
    }

    /**
     * 获取用户列表
     * @param array $where
     * @param array $field
     * @return array
     */
    public function getUserList(array $where = [], array $field = ['*']): array
    {
        return DB::table('feishu_internal_user')
            ->when($where, function($query) use ($where) {
                $query->where($where);
            })
            ->get($field)
            ->transform(function ($value) {
                return (array)$value;
            })
            ->toArray();
    }


    /**
     * 获取用户信息
     * @param array $where
     * @param array|string[] $field
     * @return array
     */
    public function getUserInfo(array $where, array $field = ['*']): array
    {
        return (array)DB::table('feishu_internal_user')->where($where)->first($field);
    }

    /**
     * 根据企业微信用户id获取飞书用户信息
     * @param string $company_wechat_id
     * @param array $field
     * @return array
     */
    public function getUserInfoByCompanyWechatUserId(string $company_wechat_id, array $field = ['*']): array
    {
        $company_wechat_repository = new CompanyWechatRepository();
        $admin_info = $company_wechat_repository->getAdminUserInfoByCompanyWechatUserId($company_wechat_id);
        $feishu_user_id = Arr::get($admin_info, 'feishu_id', 0);
        return (array)DB::table('feishu_internal_user')->where('user_id', $feishu_user_id)->first($field);
    }

    /**
     * 企业微信用户id转飞书用户id
     * @param string $company_wechat_id
     * @return string
     */
    public function companyWechatUserIdConverFeishuUserId(string $company_wechat_id): string
    {
        $company_wechat_repository = new CompanyWechatRepository();
        $admin_info = $company_wechat_repository->getAdminUserInfoByCompanyWechatUserId($company_wechat_id);
        return Arr::get($admin_info, 'feishu_id', '');
    }

    /**
     * 根据admin表的id获取对应飞书表的用户信息
     * @param int $admin_id
     * @return array
     */
    public function getUserInfoByAdminId(int $admin_id): array
    {
        $info = [];
        $admin_user = (array)DB::table('admin')->where('id', $admin_id)->first(['feishu_id']);
        if ($admin_user && $admin_user['feishu_id']) {
            $info = (array)DB::table('feishu_internal_user')
                ->where('user_id', $admin_user['feishu_id'])
                ->first();
        }
        return $info;
    }

}
