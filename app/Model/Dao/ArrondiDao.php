<?php declare(strict_types=1);

namespace App\Model\Dao;


use Swoft\Bean\Annotation\Mapping\Bean;
use Swoft\Db\DB;

/**
 * 专区数据操作类
 * @Bean()
 */
class ArrondiDao
{
    /**
     *  专区启用
     */
    const  ARROND_STATUS_ENABLE  = 1;

    /**
     * 专区禁用
     */
    const  ARROND_STATUS_UNENABLE = 0;

    /**
     * 获取自定义专区列表(自定义专区id大于 2)
     * @param array  $condition
     * @return array
     */
    public  function getList(array  $condition =[]):array
    {
        $where[] = ['arrondi.state','=',self::ARROND_STATUS_ENABLE];

        if(empty($condition))
        {
            //
            $where[] =['arrondi.id','>',2];

            $data = DB::table('arrondi')
                    ->leftJoin('attachment','arrondi.cover','=','attachment.id')
                    ->where($where)
                    ->select('arrondi.id as arrondi_id','arrondi.aid','arrondi.url','arrondi.title','arrondi.subtitle','attachment.url as image')
                    ->orderByDesc('arrondi.sort')
                    ->get()
                    ->toArray();
        }else{

            $data = DB::table('arrondi')
                    ->leftJoin('attachment','arrondi.cover','=','attachment.id')
                    ->where($where)
                    ->whereIn('arrondi.id',$condition)
                    ->select('arrondi.id as arrondi_id','arrondi.aid','arrondi.url','arrondi.title','arrondi.subtitle','attachment.url as image')
                    ->orderByDesc('arrondi.sort')
                    ->get()
                    ->toArray();
        }
        return $data;
    }

    /**
     * 获取专区推荐商品集合
     *
     * @param int $aid  商品集id
     * @param string $orderDesc 排序
     * @param int $size 查询数量
     * @return array
     */
    public  function getListByAid(int $aid,string $orderDesc,int $size=10):array
    {
        $a ='assemble_wares';
        $b ='commodity_wares';

        if($orderDesc=='sort')
        {
            $orderDesc ="{$a}.sort";
        }else{
            $orderDesc ="{$b}.{$orderDesc}";
        }

        $data = DB::table($a)
            ->join($b,"{$a}.wares_id","{$b}.id")
            ->where("{$a}.assemble_id",$aid)
            ->where("{$b}.status",1)
            ->select( "{$b}.code")
            ->orderByDesc($orderDesc)
            ->limit($size)
            ->get()
            ->toArray();

        return $data;
    }

    /**
     * 获取固定专区推荐商品集合
     *
     * @param int $aid  商品集id
     * @param int $size 查询数量
     * @return array
     */
    public  function getCustomizeListByAid(int $aid,int $size=10):array
    {
        $a ='assemble_wares';
        $b ='commodity_wares';

        $data = DB::table($a)
            ->join($b,"{$a}.wares_id","{$b}.id")
            ->where("{$a}.assemble_id",$aid)
            ->select( "{$b}.code")
            ->orderByDesc("{$a}.sort")
            ->limit($size)
            ->get()
            ->toArray();

        return $data;
    }

    /**
     * 根据id获取单条记录
     * @param int $arrondy_id
     * @return array
     */
    public  function findById(int $arrondy_id)
    {
        $data = DB::table('arrondi')
            ->leftJoin('attachment','arrondi.cover','=','attachment.id')
            ->where('arrondi.id',$arrondy_id)
            ->select('arrondi.aid','arrondi.keyword','arrondi.restrict','arrondi.title','arrondi.subtitle','attachment.url as image')
            ->first();

        return $data;
    }

}