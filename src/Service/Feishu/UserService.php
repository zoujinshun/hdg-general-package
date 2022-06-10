<?php

namespace Vaedly\HdgGeneralPackage\Service\Feishu;

use Vaedly\HdgGeneralPackage\Repository\FeishuRepository;

/**
 * Class UserService
 * @package Vaedly\HdgGeneralPackage\Service\Feishu
 */
class UserService
{
    /**
     * 创建用户
     * @param array $data
     * @return int
     */
    public function createUser(array $data): int
    {
        $user = array_merge($data, [
            'create_time' => time(),
            'update_time' => time()
        ]);
        $feishu_repository = new FeishuRepository();
        return $feishu_repository->createUser($user);
    }

    /**
     * 更新用户
     * @param string $user_id
     * @param array $data
     * @return bool
     */
    public function updateUser(string $user_id, array $data): bool
    {
        $user = array_merge($data, [
            'update_time' => time()
        ]);
        $feishu_repository = new FeishuRepository();
        return $feishu_repository->updateUser(['user_id' => $user_id], $user);
    }
}
