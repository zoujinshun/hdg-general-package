<?php

namespace Vaedly\HdgGeneralPackage\Providers\FeiShu\SelfBuiltApp\Robot;

use Vaedly\HdgGeneralPackage\Providers\FeiShu\SelfBuiltApp\Auth;
use Vaedly\HdgGeneralPackage\Providers\General\Http;
use Vaedly\HdgGeneralPackage\Service\Feishu\SelfBuiltAppAuthService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class Message
{
    private $robot_app;

    /**
     * 事件id
     */
    const EVENT_IDS = [
        'create_work_order',//提交工单通知
        'update_work_order',//更新工单通知
        'timeout_work_order',//超时处理工单通知
        'finish_work_order',//处理完成工单通知
        'urgent_work_order',//催办工单通知
        'allot_work_order',//分配工单
        'transfer_work_order',//流转工单
    ];

    /**
     * 消息类型
     */
    const MSG_TYPES = [
        //卡片消息
        'interactive' => [
            'create_work_order',
            'update_work_order',
            'finish_work_order',
            'timeout_work_order',
            'urgent_work_order',
            'allot_work_order',
            'transfer_work_order'
        ],
        //文字消息
        'text' => [],
    ];

    public function __construct(string $robot_app)
    {
        $this->robot_app = $robot_app;
    }

    /**
     * 针对事件进行消息发送
     * @param string $event_id 事件id
     * @param string $receive_id_type 接收id类型
     * @param string $receive_id 接收id
     * @param array $content 消息内容
     * @return bool
     */
    public function sendEventMessage(
        string $event_id,
        string $receive_id_type,
        string $receive_id,
        array $content
    ): bool
    {
        try {
            $url = 'https://open.feishu.cn/open-apis/im/v1/messages?receive_id_type=' . $receive_id_type;
            $auth_service = new SelfBuiltAppAuthService($this->robot_app);
            $auth = new Auth($auth_service->getAuthConfig());
            $access_token = $auth->getTenantAccessToken();
            $header = [
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type' => 'application/json; charset=utf-8',
            ];
            $msg_type = $this->getMsgTypeByEvent($event_id);
            if (!$msg_type) {
                throw new \Exception('msg_type error');
            }
            $content_json = $this->getContentJsonByEvent($event_id, $content);
            if (!$content_json) {
                throw new \Exception('content error');
            }
            $data = [
                'receive_id' => $receive_id,
                'msg_type' => $msg_type,
                'content' => $content_json
            ];
            $http = new Http();
            $res = $http->post($url, $data, $header);
            $code = Arr::get($res, 'code');
            if ($code !== 0) {
                throw new \Exception(json_encode($res));
            }
            return true;
        } catch (\Exception $exception) {
            Log::info('发送飞书消息失败, res:' . $exception->getMessage());
            return false;
        }
    }

    /**
     * 根据事件获取消息类型
     * @param string $event_id
     * @return string
     */
    public function getMsgTypeByEvent(string $event_id): string
    {
        $msg_type = '';
        foreach (self::MSG_TYPES as $type => $event_ids) {
            if (in_array($event_id, $event_ids)) {
                $msg_type = $type;
                break;
            }
        }
        return $msg_type;
    }

    /**
     * 消息体数据组装
     * @param string $event_id
     * @param array $content
     * @return string
     */
    public function getContentJsonByEvent(string $event_id, array $content): string
    {
        if (empty($content)) {
            return '';
        }
        $content_template = [];
        switch ($event_id) {
            case 'create_work_order':
                $content_template = $this->getCreateWorkOrderContentTemplate($content);
                break;
            case 'update_work_order':
                $content_template = $this->getUpdateWorkOrderContentTemplate($content);
                break;
            case 'urgent_work_order':
                $content_template = $this->getUrgentWorkOrderContentTemplate($content);
                break;
            case 'allot_work_order':
                $content_template = $this->getAllotWorkOrderContentTemplate($content);
                break;
            case 'finish_work_order':
                $content_template = $this->getFinishWorkOrderContentTemplate($content);
                break;
            case 'transfer_work_order':
                $content_template = $this->getTransferWorkOrderContentTemplate($content);
                break;
            case 'timeout_work_order':
                $content_template = $this->getTimeoutWorkOrderContentTemplate($content);
                break;
            default:
                break;
        }
        return json_encode($content_template);
    }

    /**
     * 创建工单事件消息体
     * @param array $content
     * @return array
     */
    public function getCreateWorkOrderContentTemplate(array $content): array
    {
        $user_id = Arr::get($content, 'user_id', '');//提交人user_id
        $time = Arr::get($content, 'time', '');//提交时间
        $code = Arr::get($content, 'code', '');//工单编号
        $source = Arr::get($content, 'source', '');//来源
        $desc = Arr::get($content, 'desc', '');//内容
        $severity = Arr::get($content, 'severity', '');//严重程度
        $template = [
            'header' => [
                'template' => 'blue',
                'title' => [
                    'content' => '📪 产生新的工单，请及时处理！',
                    'tag' => 'plain_text',
                ],
            ],
            'i18n_elements' => [
                'zh_cn' => [
                    [
                        'fields' => [
                            [
                                'is_short' => true,
                                'text' => [
                                    'content' => '**👤 提交人：** <at id="' . $user_id . '"></at>',
                                    'tag' => 'lark_md',
                                ],
                            ],
                            [
                                'is_short' => true,
                                'text' => [
                                    'content' => "**📅 提交时间：**{$time}",
                                    'tag' => 'lark_md',
                                ],
                            ],
                        ],
                        'tag' => 'div',
                    ],
                    [
                        'fields' => [
                            [
                                'is_short' => true,
                                'text' => [
                                    'content' => "**\x23\xE2\x83\xA3 工单编号：** {$code}",
                                    'tag' => 'lark_md',
                                ],
                            ],
                            [
                                'is_short' => true,
                                'text' => [
                                    'content' => "**🗂️ 工单来源：**{$source}",
                                    'tag' => 'lark_md',
                                ],
                            ],
                        ],
                        'tag' => 'div',
                    ],
                    [
                        'fields' => [
                            [
                                'is_short' => true,
                                'text' => [
                                    'content' => "**📚 工单内容：**{$desc}",
                                    'tag' => 'lark_md',
                                ],
                            ],
                            [
                                'is_short' => true,
                                'text' => [
                                    'content' => "**\xE2\x80\xBC 严重程度：**{$severity}",
                                    'tag' => 'lark_md',
                                ],
                            ],
                        ],
                        'tag' => 'div',
                    ],
                    [
                        'actions' => [
                            [
                                'tag' => 'button',
                                'url' => "http://work-order.growth114.com/#/my-work/work-detail?id={$content['work_order_id']}",
                                'text' => [
                                    'content' => '立即处理',
                                    'tag' => 'plain_text',
                                ],
                                'type' => 'primary',
                                'value' => [
                                    'action_id' => 'goto_work_order_info_page',
                                    'work_order_code' => $code
                                ],
                            ],
                        ],
                        'tag' => 'action',
                    ],
                ],
            ],
        ];

        return $template;
    }

    /**
     * 变更工单消息体
     * @param array $content
     * @return array
     */
    public function getUpdateWorkOrderContentTemplate(array $content): array
    {
        $user_id = Arr::get($content, 'user_id', '');//提交人user_id
        $code = Arr::get($content, 'code', '');//工单编号
        $desc = Arr::get($content, 'desc', '');//内容
        $update_desc = Arr::get($content, 'update_desc', '');//变更内容
        $template = [
            'header' => [
                'template' => 'blue',
                'title' => [
                    'content' => "\xF0\x9F\x93\x9D 「{$code}」发生了新的变化，去看看吧",
                    'tag' => 'plain_text',
                ],
            ],
            'i18n_elements' => [
                'zh_cn' => [
                    [
                        'fields' => [
                            [
                                'is_short' => true,
                                'text' => [
                                    'content' => "**\x23\xE2\x83\xA3 工单编号：** {$code}",
                                    'tag' => 'lark_md',
                                ],
                            ],
                            [
                                'is_short' => true,
                                'text' => [
                                    'content' => "**📚 工单内容：** {$desc}",
                                    'tag' => 'lark_md',
                                ],
                            ],
                        ],
                        'tag' => 'div',
                    ],
                    [
                        'fields' => [
                            [
                                'is_short' => true,
                                'text' => [
                                    'content' => "**📚 更新内容：** {$update_desc}",
                                    'tag' => 'lark_md',
                                ],
                            ],
                            [
                                'is_short' => true,
                                'text' => [
                                    'content' => "**👤 变更人：** <at id={$user_id}></at>",
                                    'tag' => 'lark_md',
                                ],
                            ],
                        ],
                        'tag' => 'div',
                    ],
                    [
                        'actions' => [
                            [
                                'tag' => 'button',
                                'url' => "http://work-order.growth114.com/#/my-work/work-detail?id={$content['work_order_id']}",
                                'text' => [
                                    'content' => '查看更新',
                                    'tag' => 'plain_text',
                                ],
                                'type' => 'primary',
                                'value' => [
                                    'action_id' => 'goto_work_order_info_page',
                                    'work_order_code' => $code
                                ],
                            ],
                        ],
                        'tag' => 'action',
                    ],
                ],
            ],
        ];

        return $template;
    }

    /**
     * 超时处理工单消息体
     * @param array $content
     * @return array
     */
    public function getTimeoutWorkOrderContentTemplate(array $content): array
    {
        $user_id = Arr::get($content, 'user_id', '');//提交人user_id
        $handler_name = Arr::get($content, 'handler_name', '');//处理人user_id
        $time = Arr::get($content, 'time', '');//提交时间
        $code = Arr::get($content, 'code', '');//工单编号
        $desc = Arr::get($content, 'desc', '');//内容
        $expect_handle_time = Arr::get($content, 'expect_handle_time', '');//预计处理时长
        $real_handle_time = Arr::get($content, 'real_handle_time', '');//实际处理时长
        $template = [
            'header' => [
                'template' => 'red',
                'title' => [
                    'content' => "📪 「{$handler_name}」 您处理的工单已超时，请尽快完成！",
                    'tag' => 'plain_text',
                ],
            ],
            'i18n_elements' => [
                'zh_cn' => [
                    [
                        'fields' => [
                            [
                                'is_short' => true,
                                'text' => [
                                    'content' => '**👤 提交人：** <at id="' . $user_id . '"></at>',
                                    'tag' => 'lark_md',
                                ],
                            ],
                            [
                                'is_short' => true,
                                'text' => [
                                    'content' => "**📅 提交时间：**{$time}",
                                    'tag' => 'lark_md',
                                ],
                            ],
                        ],
                        'tag' => 'div',
                    ],
                    [
                        'fields' => [
                            [
                                'is_short' => true,
                                'text' => [
                                    'content' => "**\x23\xE2\x83\xA3 工单编号：** {$code}",
                                    'tag' => 'lark_md',
                                ],
                            ],
                            [
                                'is_short' => true,
                                'text' => [
                                    'content' => "**📚 工单内容：**{$desc}",
                                    'tag' => 'lark_md',
                                ],
                            ],
                        ],
                        'tag' => 'div',
                    ],
                    [
                        'fields' => [
                            [
                                'is_short' => true,
                                'text' => [
                                    'content' => "**📅  预计处理时长：**{$expect_handle_time}",
                                    'tag' => 'lark_md',
                                ],
                            ],
                            [
                                'is_short' => true,
                                'text' => [
                                    'content' => "**📅  实际处理时长：**{$real_handle_time}",
                                    'tag' => 'lark_md',
                                ],
                            ],
                        ],
                        'tag' => 'div',
                    ],
                    [
                        'actions' => [
                            [
                                'tag' => 'button',
                                'url' => "http://work-order.growth114.com/#/my-work/work-detail?id={$content['work_order_id']}",
                                'text' => [
                                    'content' => '点击查看',
                                    'tag' => 'plain_text',
                                ],
                                'type' => 'primary',
                                'value' => [
                                    'action_id' => 'goto_work_order_info_page',
                                    'work_order_code' => $code
                                ],
                            ],
                        ],
                        'tag' => 'action',
                    ],
                ],
            ],
        ];

        return $template;
    }

    /**
     * 处理完成工单消息体
     * @param array $content
     * @return array
     */
    public function getFinishWorkOrderContentTemplate(array $content): array
    {
        $user_id = Arr::get($content, 'user_id', '');//负责人人user_id
        $name = Arr::get($content, 'name', '');//负责人名
        $time = Arr::get($content, 'time', '');//提交时间
        $handle_time = Arr::get($content, 'handle_time', '');//处理时长
        $code = Arr::get($content, 'code', '');//工单编号
        $status = Arr::get($content, 'status', '');//状态
        $template = [
            'header' => [
                'template' => 'green',
                'title' => [
                    'content' => "👍 「{$code}」已处理完成并发布上线！",
                    'tag' => 'plain_text',
                ],
            ],
            'i18n_elements' => [
                'zh_cn' => [
                    [
                        'fields' => [
                            [
                                'is_short' => true,
                                'text' => [
                                    'content' => "**👤 负责人：**<at id={$user_id}></at>",
                                    'tag' => 'lark_md',
                                ],
                            ],
                            [
                                'is_short' => true,
                                'text' => [
                                    'content' => "**📅 处理时长：**{$handle_time}",
                                    'tag' => 'lark_md',
                                ],
                            ],
                        ],
                        'tag' => 'div',
                    ],
                    [
                        'fields' => [
                            [
                                'is_short' => true,
                                'text' => [
                                    'content' => "**📅 完成时间：**{$time}",
                                    'tag' => 'lark_md',
                                ],
                            ],
                            [
                                'is_short' => true,
                                'text' => [
                                    'content' => "** 工单状态：**{$status}",
                                    'tag' => 'lark_md',
                                ],
                            ],
                        ],
                        'tag' => 'div',
                    ],
                    [
                        'tag' => 'hr',
                    ],
                    [
                        'elements' => [
                            [
                                'content' => "✅ 该工单已由{$name}完成",
                                'tag' => 'plain_text'
                            ]
                        ],
                        'tag' => 'note',
                    ],
                ],
            ],
        ];

        return $template;
    }

    /**
     * 催办工单消息体
     * @param array $content
     * @return array
     */
    public function getUrgentWorkOrderContentTemplate(array $content): array
    {
        $user_id = Arr::get($content, 'user_id', '');//提交人user_id
        $handle_user_id = Arr::get($content, 'handle_user_id', '');//负责人user_id
        $time = Arr::get($content, 'time', '');//提交时间
        $start_handle_time = Arr::get($content, 'start_handle_time', '');//开始处理时间
        $code = Arr::get($content, 'code', '');//工单编号
        $source = Arr::get($content, 'source', '');//来源
        $desc = Arr::get($content, 'desc', '');//内容
        $severity = Arr::get($content, 'severity', '');//严重程度
        $template = [
            'header' => [
                'template' => 'red',
                'title' => [
                    'content' => "\xF0\x9F\x93\xA2 收到一条催办通知，请尽快处理！",
                    'tag' => 'plain_text',
                ],
            ],
            'i18n_elements' => [
                'zh_cn' => [
                    [
                        'fields' => [
                            [
                                'is_short' => true,
                                'text' => [
                                    'content' => '**👤 负责人：** <at id="' . $handle_user_id . '"></at>',
                                    'tag' => 'lark_md',
                                ],
                            ],
                            [
                                'is_short' => true,
                                'text' => [
                                    'content' => "**📅 开始处理时间：**{$start_handle_time}",
                                    'tag' => 'lark_md',
                                ],
                            ],
                        ],
                        'tag' => 'div',
                    ],
                    [
                        'fields' => [
                            [
                                'is_short' => true,
                                'text' => [
                                    'content' => '**👤 提交人：** <at id="' . $user_id . '"></at>',
                                    'tag' => 'lark_md',
                                ],
                            ],
                            [
                                'is_short' => true,
                                'text' => [
                                    'content' => "**📅 提交时间：**{$time}",
                                    'tag' => 'lark_md',
                                ],
                            ],
                        ],
                        'tag' => 'div',
                    ],
                    [
                        'fields' => [
                            [
                                'is_short' => true,
                                'text' => [
                                    'content' => "**\x23\xE2\x83\xA3 工单编号：** {$code}",
                                    'tag' => 'lark_md',
                                ],
                            ],
                            [
                                'is_short' => true,
                                'text' => [
                                    'content' => "**🗂️ 工单来源：**{$source}",
                                    'tag' => 'lark_md',
                                ],
                            ],
                        ],
                        'tag' => 'div',
                    ],
                    [
                        'fields' => [
                            [
                                'is_short' => true,
                                'text' => [
                                    'content' => "**📚 工单内容：**{$desc}",
                                    'tag' => 'lark_md',
                                ],
                            ],
                            [
                                'is_short' => true,
                                'text' => [
                                    'content' => "**\xE2\x80\xBC 严重程度：**{$severity}",
                                    'tag' => 'lark_md',
                                ],
                            ],
                        ],
                        'tag' => 'div',
                    ],
                    [
                        'actions' => [
                            [
                                'tag' => 'button',
                                'url' => "http://work-order.growth114.com/#/my-work/work-detail?id={$content['work_order_id']}",
                                'text' => [
                                    'content' => '立即处理',
                                    'tag' => 'plain_text',
                                ],
                                'type' => 'primary',
                                'value' => [
                                    'action_id' => 'goto_work_order_info_page',
                                    'work_order_code' => $code
                                ],
                            ],
                        ],
                        'tag' => 'action',
                    ],
                ],
            ],
        ];

        return $template;
    }

    /**
     * 分配工单消息体
     * @param array $content
     * @return array
     */
    public function getAllotWorkOrderContentTemplate(array $content): array
    {
        $user_id = Arr::get($content, 'user_id', '');//提交人user_id
        $handle_user_id = Arr::get($content, 'handle_user_id', '');//负责人user_id
        $time = Arr::get($content, 'time', '');//提交时间
        $code = Arr::get($content, 'code', '');//工单编号
        $source = Arr::get($content, 'source', '');//来源
        $desc = Arr::get($content, 'desc', '');//内容
        $severity = Arr::get($content, 'severity', '');//严重程度
        $allot_name = Arr::get($content, 'allot_name', '');//分配人
        $template = [
            'header' => [
                'template' => 'purple',
                'title' => [
                    'content' => "\xF0\x9F\x94\x80 「{$allot_name}」 分配给你一条待处理工单，请尽快处理！",
                    'tag' => 'plain_text',
                ],
            ],
            'i18n_elements' => [
                'zh_cn' => [
                    [
                        'fields' => [
                            [
                                'is_short' => false,
                                'text' => [
                                    'content' => '**👤 负责人：** <at id="' . $handle_user_id . '"></at>',
                                    'tag' => 'lark_md',
                                ],
                            ],
                        ],
                        'tag' => 'div',
                    ],
                    [
                        'fields' => [
                            [
                                'is_short' => true,
                                'text' => [
                                    'content' => '**👤 提交人：** <at id="' . $user_id . '"></at>',
                                    'tag' => 'lark_md',
                                ],
                            ],
                            [
                                'is_short' => true,
                                'text' => [
                                    'content' => "**📅 提交时间：**{$time}",
                                    'tag' => 'lark_md',
                                ],
                            ],
                        ],
                        'tag' => 'div',
                    ],
                    [
                        'fields' => [
                            [
                                'is_short' => true,
                                'text' => [
                                    'content' => "**\x23\xE2\x83\xA3 工单编号：** {$code}",
                                    'tag' => 'lark_md',
                                ],
                            ],
                            [
                                'is_short' => true,
                                'text' => [
                                    'content' => "**🗂️ 工单来源：**{$source}",
                                    'tag' => 'lark_md',
                                ],
                            ],
                        ],
                        'tag' => 'div',
                    ],
                    [
                        'fields' => [
                            [
                                'is_short' => true,
                                'text' => [
                                    'content' => "**📚 工单内容：**{$desc}",
                                    'tag' => 'lark_md',
                                ],
                            ],
                            [
                                'is_short' => true,
                                'text' => [
                                    'content' => "**\xE2\x80\xBC 严重程度：**{$severity}",
                                    'tag' => 'lark_md',
                                ],
                            ],
                        ],
                        'tag' => 'div',
                    ],
                    [
                        'actions' => [
                            [
                                'tag' => 'button',
                                'url' => "http://work-order.growth114.com/#/my-work/work-detail?id={$content['work_order_id']}",
                                'text' => [
                                    'content' => '立即处理',
                                    'tag' => 'plain_text',
                                ],
                                'type' => 'primary',
                                'value' => [
                                    'action_id' => 'goto_work_order_info_page',
                                    'work_order_code' => $code
                                ],
                            ],
                        ],
                        'tag' => 'action',
                    ],
                ],
            ],
        ];

        return $template;
    }

    /**
     * 工单流转通知
     * @param array $content
     * @return array
     */
    public function getTransferWorkOrderContentTemplate(array $content): array
    {
        $old_user_id = Arr::get($content, 'old_user_id', '');//原负责人user_id
        $new_user_id = Arr::get($content, 'new_user_id', '');//现负责人user_id
        $code = Arr::get($content, 'code', '');//工单编号
        $desc = Arr::get($content, 'desc', '');//内容
        $template = [
            'header' => [
                'template' => 'purple',
                'title' => [
                    'content' => "\xF0\x9F\x94\x80 工单流转通知",
                    'tag' => 'plain_text',
                ],
            ],
            'i18n_elements' => [
                'zh_cn' => [
                    [
                        'fields' => [
                            [
                                'is_short' => true,
                                'text' => [
                                    'content' => '**👤 原负责人：** <at id="' . $old_user_id . '"></at>',
                                    'tag' => 'lark_md',
                                ],
                            ],
                            [
                                'is_short' => true,
                                'text' => [
                                    'content' => '**👤 现负责人：** <at id="' . $new_user_id . '"></at>',
                                    'tag' => 'lark_md',
                                ],
                            ],
                        ],
                        'tag' => 'div',
                    ],
                    [
                        'fields' => [
                            [
                                'is_short' => true,
                                'text' => [
                                    'content' => "**\x23\xE2\x83\xA3 工单编号：** {$code}",
                                    'tag' => 'lark_md',
                                ],
                            ],
                            [
                                'is_short' => true,
                                'text' => [
                                    'content' => "**📚 工单内容：**{$desc}",
                                    'tag' => 'lark_md',
                                ],
                            ],
                        ],
                        'tag' => 'div',
                    ],
                    [
                        'actions' => [
                            [
                                'tag' => 'button',
                                'url' => "http://work-order.growth114.com/#/my-work/work-detail?id={$content['work_order_id']}",
                                'text' => [
                                    'content' => '立即处理',
                                    'tag' => 'plain_text',
                                ],
                                'type' => 'primary',
                                'value' => [
                                    'action_id' => 'goto_work_order_info_page',
                                    'work_order_code' => $code
                                ],
                            ],
                        ],
                        'tag' => 'action',
                    ],
                ],
            ],
        ];

        return $template;
    }

}
