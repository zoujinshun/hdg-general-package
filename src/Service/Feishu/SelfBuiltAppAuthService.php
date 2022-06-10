<?php

namespace Vaedly\HdgGeneralPackage\Service\Feishu;

use Vaedly\HdgGeneralPackage\Providers\FeiShu\SelfBuiltApp\Auth;
use Vaedly\HdgGeneralPackage\Providers\General\{ArrHelper, TokenHelper};
use Vaedly\HdgGeneralPackage\Repository\FeishuRepository;
use Illuminate\Support\Facades\Redis;

/**
 * Class SelfBuiltAppAuthService
 * @package Vaedly\HdgGeneralPackage\Service\Feishu
 */
class SelfBuiltAppAuthService
{
    private $app;

    const CONFIG_FUNCTION = [
        'work_order' => 'getWorkOrderAuthConfig',
    ];

    const USER_TOKEN_PREFIX = [
        'work_order' => 'feishu_app_work_order',
    ];

    const TENANT_ACCESS_TKOEN_PREFIX = [
        'work_order' => 'feishu_work_order_tenant_access_token',
    ];

    const TOKEN_EXPIRE = 12960000;

    public function __construct(string $app)
    {
        $this->app = $app;
    }

    /**
     * 获取认证配置
     * @return array
     */
    public function getAuthConfig(): array
    {
        return call_user_func([$this, self::CONFIG_FUNCTION[$this->app]]);
    }

    /**
     * 获取工单应用认证配置
     */
    public function getWorkOrderAuthConfig()
    {
        return [
            'app_id' => env('FEISHU_SELFBUILTAPP_WORK_ORDER_APP_ID', ''),
            'app_secret' => env('FEISHU_SELFBUILTAPP_WORK_ORDER_APP_SECRET', ''),
            'tenant_access_token_key' => self::TENANT_ACCESS_TKOEN_PREFIX[$this->app]
        ];
    }

    /**
     * 授权码认证登陆
     * @param string $code
     * @return array
     */
    public function loginByAuthCode(string $code): array
    {
        $result = [
            'code' => 0,
            'message' => '登陆成功',
            'data' => []
        ];
        try {
            $auth = new Auth($this->getAuthConfig());
            $user_info = $auth->getUserAccessTokenInfo($code);
            if (empty($user_info)) {
                throw new \Exception('获取登录用户身份信息失败');
            }
            $feishu_repository = new FeishuRepository();
            $user = $feishu_repository->getUserInfo(['user_id' => $user_info['user_id']]);
            $user_service = new UserService();
            $arr_helper = new ArrHelper();
            $data = $arr_helper->getAssignElement($user_info, [
                'name',
                'en_name',
                'mobile',
                'email',
                'avatar_url',
                'user_id',
                'open_id',
                'union_id',
                'tenant_key'
            ]);
            if (!$user) {
                $id = $user_service->createUser($data);
            } else {
                $user_service->updateUser($user_info['user_id'], $data);
                $id = $user['id'];
            }
            $data['id'] = $id;
            $token_helper = new TokenHelper();
            $token = $token_helper->createSimpleToken($user_info['user_id']);
            $this->setUserToken($token, $data);
            $data['role'] = ($data['user_id'] == FeishuRepository::TEST_USER['user_id']) ? 'test' : 'normal';
            $result['data'] = ['token' => $token, 'user_info' => $data];
        } catch (\Exception $exception) {
            $result['code'] = 1;
            $result['message'] = '登陆失败' . $exception->getMessage();
        }
        return $result;
    }

    /**
     * 设置token缓存数据
     * @param string $token
     * @param array $info
     * @return bool
     */
    public function setUserToken(string $token, array $info): bool
    {
        $redis_key = self::USER_TOKEN_PREFIX[$this->app] . $token;
        return (bool)Redis::setex($redis_key, self::TOKEN_EXPIRE, json_encode($info));
    }

    /**
     * 退出
     * @param string $token
     * @return bool
     */
    public function logout(string $token): bool
    {
        $redis_key = self::USER_TOKEN_PREFIX[$this->app] . $token;
        return (bool)Redis::del($redis_key);
    }
}