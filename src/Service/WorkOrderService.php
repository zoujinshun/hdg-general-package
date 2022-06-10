<?php

namespace Vaedly\HdgGeneralPackage\Service;

use Illuminate\Support\Arr;
use Vaedly\HdgGeneralPackage\Providers\FeiShu\SelfBuiltApp\Robot\Message;
use Vaedly\HdgGeneralPackage\Providers\General\{ArrHelper, DateHelper, StrHelper};
use Vaedly\HdgGeneralPackage\Repository\{FeishuRepository, WorkOrderRepository};
use Illuminate\Support\Facades\{Log, Redis};

/**
 * 工单
 * Class WorkOrderService
 * @package Vaedly\HdgGeneralPackage\Service
 */
class WorkOrderService
{
    const APP = 'work_order';

    /**
     * 状态
     * 0 进行中，进行中包括待处理和处理中以及已解决
     * 1 已完成
     * 2 无需处理
     */
    const STATUS_MAP = [
        0 => [0, 1, 2],
        1 => [3],
        2 => [4],
    ];

    //工单状态
    const STATUS = [
        ['name' => '待处理', 'value' => 0, 'color' => '#87C7FF'],
        ['name' => '处理中', 'value' => 1, 'color' => '#FADA73'],
        ['name' => '已完成', 'value' => 2, 'color' => '#61CF98'],
        ['name' => '已解决', 'value' => 3, 'color' => '#61CF98'],
        ['name' => '已拒绝', 'value' => 4, 'color' => '#FF8769'],
    ];

    //严重程度
    const SERVERITY = [
        ['name' => '致命', 'value' => 0, 'color' => '#FF5733'],
        ['name' => '一般', 'value' => 1, 'color' => '#FFC300'],
        ['name' => '严重', 'value' => 2, 'color' => '#FF8D1A'],
        ['name' => '建议', 'value' => 3, 'color' => '#43CF7C'],
    ];

    //平台类型
    const PLATFORM = [
        [
            'name' => '商户端',
            'value' => 0,
            'color' => '#A8EDC8',
            'child_option' => [
                [
                    'name' => '员工端',
                    'value' => 1,
                ],
                [
                    'name' => '商家助手',
                    'value' => 2,
                ],
                [
                    'name' => '商户后台',
                    'value' => 3,
                ],
                [
                    'name' => '门户官网',
                    'value' => 4,
                ],
            ],
        ],
        [
            'name' => '用户端',
            'value' => 1,
            'color' => '#B3DDF2',
            'child_option' => [
                [
                    'name' => '活动页',
                    'value' => 1,
                ],
                [
                    'name' => '个人中心',
                    'value' => 2,
                ],
                [
                    'name' => '店铺主页',
                    'value' => 3,
                ],
            ],
        ],
        [
            'name' => '平台端',
            'value' => 2,
            'color' => '#FCD4B1',
            'child_option' => [
                [
                    'name' => '总后台',
                    'value' => 1,
                ],
                [
                    'name' => '代理后台',
                    'value' => 2,
                ],
                [
                    'name' => 'SCRM服务台',
                    'value' => 3,
                ],
            ],
        ],
    ];

    //问题类型
    const QUESTION = [
        ['name' => '订单相关', 'value' => 0],
        ['name' => '登陆注册', 'value' => 1],
        ['name' => '资金相关', 'value' => 2],
        ['name' => '账户相关', 'value' => 3],
        ['name' => '海报分享', 'value' => 4],
        ['name' => '返现红包', 'value' => 5],
        ['name' => '项目卡劵', 'value' => 6],
        ['name' => '其他', 'value' => 7],
    ];

    //缺陷类型
    const DEFECT = [
        ['name' => '功能问题', 'value' => 0],
        ['name' => '性能问题', 'value' => 1],
        ['name' => '接口问题', 'value' => 2],
        ['name' => '安全问题', 'value' => 3],
        ['name' => 'UI问题', 'value' => 4],
        ['name' => '兼容性问题', 'value' => 5],
        ['name' => '易用性问题', 'value' => 6],
    ];

    //来源
    const SOURCE = [
        ['name' => '企业微信自建应用', 'value' => 0, 'prefix' => 'QW'],
        ['name' => '飞书自建应用', 'value' => 1, 'prefix' => 'HT'],
    ];

    //可进行更新状态
    const CAN_UPDATE_STATUS = [0];

    //可进行催办状态
    const CAN_URGENT_STATUS = [0, 1];

    //编号前缀
    const CODE_PREFIX = [
        'company_wechat' => 'QW',//企业微信
        'admin' => 'HT',//后台
    ];

    //飞书工单处理群id
    const FEISHU_ORDER_WORK_GROUP_CHAT_ID = 'oc_114cfa3ba815f8a2ed424598f9d5a91c';

    //跟踪记录标点颜色
    const TRACK_RECORD_COLOR = [
        'self' => '#A5D63F',
        'other' => '#1590FF',
    ];

    /**
     * 是否可以催办
     * @param array $work_order
     * @return array
     */
    public function checkCanUrgent(array $work_order): array
    {
        $result = [
            'can_urgent' => true,
            'reason' => '',
        ];
        try {
            if ($this->checkTodayUrgentWorkOrder($work_order['id'])) {
                throw new \Exception('今日已催办过');
            }
            if (!in_array($work_order['status'], self::CAN_URGENT_STATUS) ||
            $work_order['severity_type'] == 3) {
                throw new \Exception('该工单状态不支持催办操作');
            }
            //待处理状态
            if ($work_order['status'] == 0) {
                $expend_time = time() - $work_order['create_time'];
                switch ($work_order['severity_type']) {
                    case 0:
                        $limit_time = 300;
                        break;
                    case 1:
                        $limit_time = 3600;
                        break;
                    case 2:
                        $limit_time = 24 * 3600;
                        break;
                    default:
                        $limit_time = 0;
                        break;
                }
                if ($expend_time < $limit_time) {
                    throw new \Exception('当前时间不支持催办操作');
                }
            }
            //处理中状态
            if ($work_order['status'] == 1) {
                if (time() < $work_order['expect_end_handle_time']) {
                    throw new \Exception('当前时间不支持催办操作');
                }
            }

        } catch (\Exception $exception) {
            $result['can_urgent'] = false;
            $result['reason'] = $exception->getMessage();
        }
        return $result;
    }

    /**
     * 今日是否催办过工单
     * @param int $id
     * @return bool
     */
    public function checkTodayUrgentWorkOrder(int $id): bool
    {
        $redis_key = 'work_order_urgent_' . date('Ymd') . $id;
        return (bool)Redis::get($redis_key);
    }

    /**
     * 催办记录
     * @param int $id
     * @return bool
     */
    public function logTodayUrgentWorkOrder(int $id): bool
    {
        $redis_key = 'work_order_urgent_' . date('Ymd') . $id;
        return (bool)Redis::setex($redis_key, 3600, time());
    }

    /**
     * 选项配置数据
     * @return array[]
     */
    public function getWorkOrderOptionConfig()
    {
        return [
            'question_type' => [
                'field' => 'question_type',
                'name' => '问题类型',
                'options' => self::QUESTION,
                'is_require' => true
            ],
            'severity_type' => [
                'field' => 'severity_type',
                'name' => '严重程度',
                'options' => self::SERVERITY,
                'is_require' => true
            ],
            'defect_type' => [
                'field' => 'defect_type',
                'name' => '缺陷类别',
                'options' => self::DEFECT,
                'is_require' => true
            ],
            'platform_type' => [
                'field' => 'platform_type',
                'name' => '来自什么端',
                'options' => self::PLATFORM,
                'is_require' => true
            ],
        ];
    }

    /**
     * 生成工单编号
     * @param string $prefix
     * @return string
     */
    public function generateWorkOrderCode(string $prefix = 'HDG'): string
    {
        $repository = new WorkOrderRepository();
        return $prefix . '-' . $repository->getTodayWorkOrderCodeNumber($this->getSourceTypeByPrefix($prefix));
    }

    public function getSourceTypeByPrefix(string $prefix): int
    {
        $source_type = 0;
        foreach (self::SOURCE as $item) {
            if ($item['prefix'] == $prefix) {
                $source_type = $item['value'];
                break;
            }
        }
        return $source_type;
    }

    /**
     * 严重程度数据
     * @param int $severity_type
     * @return array
     */
    public function getSeverityInfo(int $severity_type): array
    {
        return self::SERVERITY[$severity_type];
    }

    /**
     * 问题类型数据
     * @param int $question_type
     * @return array
     */
    public function getQuestionInfo(int $question_type): array
    {
        return self::QUESTION[$question_type];
    }

    /**
     * 缺陷类型数据
     * @param int $defect_type
     * @return array
     */
    public function getDefectInfo(int $defect_type): array
    {
        return self::DEFECT[$defect_type];
    }

    /**
     * 平台类型
     * @param int $platform_type
     * @param int $child_platform_type
     * @return array     */
    public function getPlatformInfo(int $platform_type, int $child_platform_type = 0): array
    {
        $platform_info = self::PLATFORM[$platform_type];
        $platform_info['child_platform_info'] = [];
        foreach ($platform_info['child_option'] as $child) {
            if ($child['value'] == $child_platform_type) {
                $platform_info['child_platform_info'] = $child;
                break;
            }
        }
        unset($platform_info['child_option']);
        return $platform_info;
    }

    /**
     * 工单状态
     * @param int $status
     * @return array
     */
    public function getStatusInfo(int $status): array
    {
        return self::STATUS[$status];
    }

    /**
     * 数据处理
     * @param object $info
     * @return object
     */
    public function handleInfoData($info)
    {
        $info->status_info = $this->getStatusInfo($info->status);
        $info->severity_info = $this->getSeverityInfo($info->severity_type);
        $info->question_info = $this->getQuestionInfo($info->question_type);
        $info->defect_info = $this->getDefectInfo($info->defect_type);
        $info->platform_info = $this->getPlatformInfo($info->platform_type, $info->child_platform_type);
        if (empty($info->title)) {
            $info->title = $this->extractHtmlTagContent($info->detail);
        }

        $format_date_field = [
            'create_time',
            'update_time',
            'expect_end_handle_time',
            'start_handle_time',
            'end_handle_time',
            'urgent_time'
        ];
        $date_helper = new DateHelper();
        foreach ($format_date_field as $field) {
            if (property_exists($info, $field)) {
                $info->$field = $date_helper->formatAndJudgeDate($info->$field);
            }
        }

        $info->time = '';
        if (in_array($info->status, [1, 2])) {
            $info->time = "预计处理结束时间：{$info->expect_end_handle_time}";
        }
        if (in_array($info->status, [3, 4])) {
            $info->time = "结束时间：{$info->end_handle_time}";
        }
        return $info;
    }

    /**
     * 工单变更记录
     * @param array $update_data
     * @return bool
     */
    public function recordWorkOrderUpdate(array $update_data): bool
    {
        if (empty($update_data)) {
            return false;
        }
        $work_order_repository = new WorkOrderRepository();
        return (bool)$work_order_repository->insertWorkOrderUpdateLog($update_data);
    }

    /**
     * 通知
     * @param string $event_id
     * @param array $data
     * @return bool
     */
    public function notifyInternalUser(string $event_id, array $data): bool
    {
        $message = new Message(self::APP);
        return $message->sendEventMessage(
            $event_id,
            'chat_id',
            self::FEISHU_ORDER_WORK_GROUP_CHAT_ID,
            $data
        );
    }

    /**
     * 根据发起人id获取发起人用户信息
     * @param string $initiator_id
     * @param array $field
     * @param int $type
     * @return array
     */
    public function getInitiatorUserInfoByInitiatorId(string $initiator_id, array $field = ['*'], int $type = 0): array
    {
        $initiator_user_info = [];
        $feishu_repository = new FeishuRepository();
        switch ($type) {
            case 0:
                $initiator_user_info = $feishu_repository->getUserInfoByCompanyWechatUserId($initiator_id, $field);
                break;
            case 1:
                $initiator_user_info = $feishu_repository->getUserInfo(['user_id' => $initiator_id], $field);
                break;
            default:
                break;
        }
        return $initiator_user_info;
    }

    /**
     * 获取评论列表
     * @param int $work_order_id
     * @param string $user_id
     * @return array
     */
    public function getCommentList(int $work_order_id, string $user_id = ''): array
    {
        $work_order_repository = new WorkOrderRepository();
        $comment_list = $work_order_repository->getCommentList(['work_order_id' => $work_order_id]);
        $comment_ids = array_column($comment_list, 'id');

        $reply_list = $work_order_repository->getReplyList([[function ($query) use ($comment_ids) {
            $query->whereIn('comment_id', $comment_ids);
        }]]);
        $user_ids = array_unique(array_merge(
            array_column($comment_list, 'critic_user_id'),
            array_column($reply_list, 'reply_user_id')
        ));
        $feishu_repository = new FeishuRepository();
        $users = $feishu_repository->getUserList([[function ($query) use ($user_ids) {
            $query->whereIn('id', $user_ids);
        }]], ['id', 'name', 'avatar_url', 'user_id']);
        $arr_helper = new ArrHelper();
        $users = $arr_helper->assemblySetIndexByFieldMultipleArr($users, 'id');
        $reply_list = $arr_helper->assemblySetIndexByFieldMultipleArr($reply_list, 'id');

        foreach ($comment_list as $key => $value) {
            $comment_list[$key]['name'] = $users[$value['critic_user_id']]['name'];
            $comment_list[$key]['avatar_url'] = $users[$value['critic_user_id']]['avatar_url'];
            $comment_list[$key]['is_myself'] = ($user_id == $users[$value['critic_user_id']]['user_id']);
            $child_reply_list = [];
            foreach ($reply_list as $item) {
                if ($item['comment_id'] == $value['id']) {
                    $item['name'] = $users[$item['reply_user_id']]['name'];
                    $item['avatar_url'] = $users[$item['reply_user_id']]['avatar_url'];
                    $item['is_myself'] = ($user_id == $users[$item['reply_user_id']]['user_id']);
                    if ($item['target_id']) {
                        $item['reply_name'] = $users[$reply_list[$item['target_id']]['reply_user_id']]['name'];
                        $item['reply_avatar_url'] = $users[$reply_list[$item['target_id']]['reply_user_id']]['avatar_url'];
                    } else {
                        $item['reply_name'] = $users[$value['critic_user_id']]['name'];
                        $item['reply_avatar_url'] = $users[$value['critic_user_id']]['avatar_url'];
                    }
                    $child_reply_list[] = $item;
                }
            }
            $comment_list[$key]['child_reply_list'] = $child_reply_list;
        }
        return $comment_list;
    }

    /**
     * 跟踪记录数据处理
     * @param string $self_user_id
     * @param array $data
     * @return array
     */
    public function handleTrackRecord(string $self_user_id, array $data): array
    {
        $pattern = '/「(.*?)」/';
        preg_match($pattern, $data['degest'], $matches);
        $user_id = Arr::get($matches, 1, '');
        if ($self_user_id == $user_id) {
            $data['degest'] = preg_replace($pattern, '「您」', $data['degest']);
            $data['color'] = self::TRACK_RECORD_COLOR['self'];
        } else {
            $feishu_repository = new FeishuRepository();
            $user_info = $feishu_repository->getUserInfo(['user_id' => $user_id], ['name']);
            $name = Arr::get($user_info, 'name', '未知');
            $data['degest'] = preg_replace($pattern, "「{$name}」", $data['degest']);
            $data['color'] = self::TRACK_RECORD_COLOR['other'];
        }
        return $data;
    }

    /**
     * 提取富文本内容
     * @param string $html
     * @return string
     */
    public function extractHtmlTagContent(string $html): string
    {
        $pattern = '/<p.*?>(.+?)\<\/p>/';
        preg_match_all($pattern, $html, $matches);
        $content = implode(',', $matches[1]);
        $img_preg = "/<[img|IMG].*?src=[\'|\"](.*?(?:[\.jpg|\.jpeg|\.png|\.gif|\.bmp]))[\'|\"].*?[\/]?>/";
        $content = preg_replace($img_preg, '【图片】', $content);
        return mb_substr($content, 0, 30);
    }

    /**
     * 更新差异
     * @param array $old
     * @param array $new
     * @return array
     */
    public function getWorkOrderUpdateDiff(array $old, array $new): array
    {
        $diff = [
            'diff' => false,
            'desc' => '',
        ];
        $desc = [];
        $diff_field = [
            'detail', 'platform_type', 'child_platform_type', 'question_type', 'severity_type', 'defect_type'
        ];
       foreach ($diff_field as $field) {
           if ($old[$field] != $new[$field]) {
               switch ($field) {
                   case 'detail':
                       $desc[] = '工单描述内容';
                       break;
                   case 'platform_type':
                       $desc[] = '工单来自端由' . self::PLATFORM[$old['platform_type']]['name'] . '改为' .
                           self::PLATFORM[$new['platform_type']]['name'];
                       break;
                   case 'question_type':
                       $desc[] = '工单问题类型由' . self::QUESTION[$old['question_type']]['name'] . '改为' .
                           self::QUESTION[$new['question_type']]['name'];
                       break;
                   case 'severity_type':
                       $desc[] = '工单严重程度由' . self::SERVERITY[$old['severity_type']]['name'] . '改为' .
                           self::SERVERITY[$new['severity_type']]['name'];
                       break;
                   case 'defect_type':
                       $desc[] = '工单缺类别由' . self::DEFECT[$old['defect_type']]['name'] . '改为' .
                           self::DEFECT[$new['defect_type']]['name'];
                       break;
                   default:
                       break;
               }
           }
       }
       if ($desc) {
            $diff['diff'] = true;
            $diff['desc'] = implode(',', $desc);
       }
       return $diff;
    }

    /**
     * 是否可以分配
     * @param string $handler_id
     * @param array $work_order
     * @return bool
     */
    public function canAllot(string $handler_id, array $work_order): bool
    {
        $can = false;
        if (in_array($work_order['status'], self::STATUS_MAP[0])) {
            if ($work_order['handler_id']) {
                if ($work_order['handler_id'] == $handler_id) {
                    $can = true;
                }
            } else {
                $can = true;
            }
        }
        return $can;
    }
}
