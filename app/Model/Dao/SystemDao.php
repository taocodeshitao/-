<?php declare(strict_types=1);

namespace App\Model\Dao;


use Swoft\Bean\Annotation\Mapping\Bean;
use Swoft\Db\DB;

/**
 * 系统配置数据操作
 * Class SystemDao
 *
 * @Bean()
 */
class SystemDao
{

    /**
     * 根据企业id获取banner信息
     * @param array $condition
     * @return array
     */
    public  function getBannerList(array $condition):array
    {
        $where['ad.state'] =1;

        $data =DB::table('ad')
                   ->join('attachment','ad.attachment_id','attachment.id')
                   ->where($where)
                   ->whereIn('ad.id',$condition)
                   ->select('ad.title','ad.url','attachment.url as image')
                   ->orderBy('ad.sort','desc')
                   ->get()
                   ->toArray();
        return $data;
    }

}