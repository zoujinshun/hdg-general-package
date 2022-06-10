<?php
declare(strict_types=1);
namespace Vaedly\HdgGeneralPackage\Providers\General;

use Vaedly\HdgGeneralPackage\Repository\TencentCloudRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

/**
 * 验证辅助类
 * Class VerifyHelper
 * @package Vaedly\HdgGeneralPackage\Providers\General
 */
class VerifyHelper
{
    /**
     * 腾讯的防水墙验证
     * @return void
     */
    public function tencentWaterproofWall(): void
    {
        if (env('SMS_TENCENT_WALL_SWITCH', true)) {
            $request = app(Request::class);
            $data = [
                'ticket' => $request->ticket ?? '',
                'rand_str' => $request->rand_str ?? '',
                'user_ip' => $request->ip(),
            ];
            $tencent = new TencentCloudRepository();
            $result = $tencent->verifyTicket($data);
            if (!$result || Arr::get($result, 'CaptchaCode', 0) != 1) {
                die(json_encode([
                    'status' => 1,
                    'data' => $result['CaptchaMsg'],
                    'message' => '防水墙验证失败'
                ]));
            }
        }
    }

    /**
     * 手机号格式验证
     * @param string $mobile
     * @return bool
     */
    public function checkMobile(string $mobile): bool
    {
        return (bool)preg_match('/^1[3456789][0-9]{9}$/', $mobile);
    }

}