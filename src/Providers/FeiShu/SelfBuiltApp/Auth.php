<?php
declare(strict_types=1);

namespace Vaedly\HdgGeneralPackage\Providers\FeiShu\SelfBuiltApp;

use Vaedly\HdgGeneralPackage\Providers\General\Http;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

/**
 * 飞书自建应用认证类
 * Class Auth
 * @package Vaedly\HdgGeneralPackage\Providers\FeiShu\SelfBuildApp
 */
class Auth
{
    /**
     * 应用唯一标识
     * @var string
     */
    private $app_id;

    /**
     * 应用密钥
     * @var string
     */
    private $app_secret;

    /**
     * 访问令牌
     * @var
     */
    private $tenant_access_token_key;

    /**
     * Auth constructor.
     * @param array $config
     * @throws \Exception
     */
    public function __construct(array $config = [])
    {
        $this->app_id = Arr::get($config, 'app_id');
        $this->app_secret = Arr::get($config, 'app_secret');
        $this->tenant_access_token_key = Arr::get($config, 'tenant_access_token_key');
        if (!$this->app_id || !$this->app_secret) {
            throw new \Exception('应用配置错误');
        }
    }

    public function setTenantAccessTokenKey(string $tenant_access_token_key): void
    {
        $this->tenant_access_token_key = $tenant_access_token_key;
    }

    /**
     * 获取tenant_access_token
     * @return string
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getTenantAccessToken(): string
    {
        $tenant_access_token = '';
        try {
            if (!$this->tenant_access_token_key) {
                throw new \Exception('empty tenant_access_token_key');
            }
            $redis_key = $this->tenant_access_token_key . $this->app_id;
            $tenant_access_token = Redis::get($redis_key);
            if ($tenant_access_token) {
                return $tenant_access_token;
            }
            $data = [
                'app_id' => $this->app_id,
                'app_secret' => $this->app_secret
            ];
            $url = 'https://open.feishu.cn/open-apis/auth/v3/tenant_access_token/internal';
            $http = new Http();
            $res = $http->post($url, $data);
            $code = Arr::get($res, 'code');
            $tenant_access_token = Arr::get($res, 'tenant_access_token', '');
            if ($code !== 0 || !$tenant_access_token) {
                throw new \Exception(json_encode($res));
            }
            $expire = Arr::get($res, 'expire', 7200);
            Redis::setex($redis_key, $expire, $tenant_access_token);
        } catch (\Exception $exception) {
            Log::info('获取tenant_access_token失败, res:' . $exception->getMessage());
        }
        return (string)$tenant_access_token;
    }

    /**
     * 获取登录用户身份
     * @param string $code 用户扫码登录后重定向的授权码
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getUserAccessTokenInfo(string $code): array
    {
        $user_access_token_info = [];
        try {
            $tenant_access_token = $this->getTenantAccessToken();
            $url = 'https://open.feishu.cn/open-apis/authen/v1/access_token';
            $header = [
                'Authorization' => 'Bearer ' . $tenant_access_token,
                'Content-Type' => 'application/json; charset=utf-8',
            ];
            $data = [
                'grant_type' => 'authorization_code',
                'code' => $code
            ];
            $http = new Http();
            $res = $http->post($url, $data, $header);
            $code = Arr::get($res, 'code');
            if ($code !== 0) {
                throw new \Exception(json_encode($res));
            }
            $user_access_token_info = Arr::get($res, 'data', []);
        } catch (\Exception $exception) {
            Log::info('获取登录用户身份失败,' . $exception->getMessage());
        }
        return $user_access_token_info;
    }

    /**
     * 获取用户信息
     * @param string $user_access_token
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getUserInfo(string $user_access_token): array
    {
        $user_info = [];
        try {
            $url = 'https://open.feishu.cn/open-apis/authen/v1/user_info';
            $header = [
                'Authorization' => 'Bearer ' . $user_access_token,
            ];
            $http = new Http();
            $res = $http->get($url, [], $header);
            $code = Arr::get($res, 'code');
            if ($code !== 0) {
                throw new \Exception(json_encode($res));
            }
            $user_info = Arr::get($res, 'data', []);
        } catch (\Exception $exception) {
            Log::info('获取用户信息失败,' . $exception->getMessage());
        }
        return $user_info;
    }

    /**
     * 刷新user_access_token
     * @param string $refresh_token
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function refreshUserAccessToken(string $refresh_token): array
    {
        $user_access_token_info = [];
        try {
            $tenant_access_token = $this->getTenantAccessToken();
            $url = 'https://open.feishu.cn/open-apis/authen/v1/refresh_access_token';
            $header = [
                'Authorization' => 'Bearer ' . $tenant_access_token,
                'Content-Type' => 'application/json; charset=utf-8',
            ];
            $data = [
                'grant_type' => 'refresh_token',
                'refresh_token' => $refresh_token
            ];
            $http = new Http();
            $res = $http->post($url, $data, $header);
            $code = Arr::get($res, 'code');
            if ($code !== 0) {
                throw new \Exception(json_encode($res));
            }
            $user_access_token_info = Arr::get($res, 'data', []);
        } catch (\Exception $exception) {
            Log::info('刷新user_access_token失败,' . $exception->getMessage());
        }
        return $user_access_token_info;
    }
}
