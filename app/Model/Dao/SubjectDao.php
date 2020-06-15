<?php declare(strict_types=1);


namespace App\Model\Dao;


use Swoft\Bean\Annotation\Mapping\Bean;
use Swoft\Db\DB;

/**
 * 专区栏目数据操作
 * Class SubJectDao
 *
 * @Bean()
 */
class SubjectDao
{

    /**
     * 专题禁用
     */
    const SUBJECT_STATUS_UNENABLE = 0;

    /**
     * 专题可用
     */
    const SUBJECT_STATUS_ENBALE = 1;

    /**
     * 根据id获取单条记录
     * @param int $subject_id
     * @param array $fields
     * @return null|object|\Swoft\Db\Eloquent\Model|static
     */
    public  function findById(int $subject_id,array $fields=['*'])
    {

        $where[] = ['id','=',$subject_id];

        $where[] = ['state','=',self::SUBJECT_STATUS_ENBALE];

        return DB::table('subject')->where($where)->first($fields);

    }


    /**
     * 获取专题栏目
     * @return array
     */
    public  function getList():array
    {
        $where['state'] = self::SUBJECT_STATUS_ENBALE;

        $data = DB::table('subject')
            ->leftJoin('attachment','subject.icon','=','attachment.id')
            ->select('subject.id as subject_id','subject.title','subject.type','attachment.url as image','subject.url')
            ->orderBy('subject.sort','asc')
            ->get()
            ->toArray();

        return $data;
    }

}