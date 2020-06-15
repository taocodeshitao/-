<?php declare(strict_types=1);

namespace App\Model\Service;

use App\Model\Dao\NoticeDao;
use Swoft\Bean\Annotation\Mapping\Bean;
use Swoft\Bean\Annotation\Mapping\Inject;
use Swoft\Co;

/**
 * 通知业务处理类
 * Class NoticeService
 * @package App\Model\Service
 * @Bean(scope=Bean::PROTOTYPE)
 */
class NoticeService
{

    /**
     * @Inject()
     * @var NoticeDao
     */
    private $noticeDao;

    /**
     * 系统通知
     */
    const SYSTEM_NOTICE = 0;


    /**
     * 物流通知
     */
    const LOGISTIC_NOTICE = 2;

    public static $notice = [
        self::SYSTEM_NOTICE => [
            'title' => '',
            'image' => '',
            'description' => '',
            'url' => '',
        ],
        self::LOGISTIC_NOTICE => [
            'order_id' => '',
            'wares_name' => '',
            'wares_cover' => '',
        ]
    ];


    public function NoticeList(int $uid, array $params)
    {

        if($params['type'] == 1)
        {
            $requests = [
                'logistic_notice' => function () use ($params,$uid){ return $this->NoticeStr(['type'=>2,'uid'=>$uid], $params['page']);},
                'logistic_notice_sum' => function () use ($params,$uid){ return $this->noticeDao->getLogisticNoticeSum(['n.type'=>2,'n.uid'=>$uid], $uid);},
                'system_notice_sum' => function () use ($params,$uid){ return $this->noticeDao->getSystemNoticeSum(['n.type'=>0]);},
            ];
        }else{
            $requests = [
                'system_notice' => function () use ($params,$uid){ return $this->NoticeStr(['type'=>0],$params['page']);},
                'system_notice_sum' => function () use ($params,$uid){ return $this->noticeDao->getSystemNoticeSum(['n.type'=>0]);},
                'logistic_notice_sum' => function () use ($params,$uid){ return $this->noticeDao->getLogisticNoticeSum(['n.type'=>2,'n.uid'=>$uid], $uid);},
            ];
        }


        $result = Co::multi($requests);

        return $result;

    }

    public function addNotice($notice_id, $uid)
    {
        $data['nid'] = $notice_id;
        $data['uid'] = $uid;
        $data['created_at'] = time();
        return $this->noticeDao->addNotice($data);
    }


    public function NoticeStr($params, $page)
    {

        $result = $this->noticeDao->getNoticeList($params, (int)$page);

        $data = [];

        if(!$result) return $data;


        foreach ($result as $k=>$v)
        {
            $content = json_decode($v['content'], true);

            if($v['type'] == 0)
            {
                $data[$k] = array_replace(self::$notice['0'], $content);
            }else{
                $data[$k] = array_replace(self::$notice['2'], $content);
            }
            $data[$k]['id'] = $v['id'];
            $data[$k]['time'] = $v['created_at'];
        }

        return $data;

    }

}