<?php declare(strict_types=1);

namespace App\Model\Dao;


use Swoft\Bean\Annotation\Mapping\Bean;
use Swoft\Db\DB;

/**
 * 通知消息数据操作
 * Class NoticeDao
 *
 * @Bean()
 */
class NoticeDao
{

    /**
     * 已过期
     */
    const  NOTICE_STATUS_UNENABLE= 0;


    /**
     * 待公示
     */
    const  NOTICE_STATUS_ABLE= 1;

    /**
     * 正在公示
     */
    const  NOTICE_STATUS_ENABLE= 2;

    /**
     * 系统通知
     */
    const  NOTICE_TYPE_SYSTEM= 0;

    /**
     * 站点通知
     */
    const  NOTICE_TYPE_ZD= 1;

    /**
     * 物流通知
     */
    const  NOTICE_TYPE_EXPRESS= 2;
    /**
     * 获取通知信息
     * @return mixed
     */
    public  function getOne()
    {
        $where[] = ['notice.type','=',self::NOTICE_TYPE_ZD];
        $where[] = ['notice.status',self::NOTICE_STATUS_ENABLE];

        $data = DB::table('notice')
                ->join('attachment','notice.cover','=','attachment.id')
                ->where($where)
                ->select('notice.title','notice.content_type as c_type','notice.content','notice.sign','attachment.url as image','notice.sign','notice.begin_time','notice.end_time')
                ->orderBy('notice.id','desc')
                ->first();
        return $data;
    }


    public function getNoticeList(array $option, int $page = 1)
    {
        return DB::table('notice')
            ->where($option)
            ->where('status', '>=', self::NOTICE_STATUS_UNENABLE)
            ->forPage($page,config('page_size'))
            ->orderByDesc('id')
            ->select('id','type','status','content_type','content','created_at')
            ->get()
            ->toArray();
    }

    public function getSystemNoticeSum(array $option)
    {

        return DB::table('notice as n')
            ->leftJoin('notice_read as nr', 'nr.nid', '=', 'n.id')
            ->where($option)
            ->where('n.status', '>=',self::NOTICE_STATUS_UNENABLE)
            ->where('nr.id', '=', null)
            ->count();
    }

    public function getLogisticNoticeSum(array $option,int $uid)
    {

        return DB::table('notice as n')
            ->leftJoin('notice_read as nr', function ($leftJoin) use ($uid){
                $leftJoin->on('nr.nid', '=', 'n.id')->where('nr.uid', '=', $uid);
            })
            ->where($option)
            ->where('n.status', '>=', self::NOTICE_STATUS_UNENABLE)
            ->where('nr.id', '=', null)
            ->count();
    }

    public function addNotice(array $data)
    {

        return DB::table('notice_read')->insert($data);

    }


}