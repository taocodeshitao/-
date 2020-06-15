<?php

namespace App\Model\Dao;
use Swoft\Bean\Annotation\Mapping\Bean;
use Swoft\Db\DB;

/**
 * 数据集商品数据操作类
 * @Bean()
 */
class AssembleWaresDao
{

    /**
     * 获取商品集商品列表分页
     * @param int $aid
     * @param int $pageIndex 页码
     * @param array $condition
     * @param int $restrict 最大展示数量
     * @return array
     */
    public  function getPageListByAid(int $aid,int $pageIndex,array $condition,int $restrict=0)
    {
        $a ='assemble_wares';
        $b ='commodity_wares';

        $size= config('page_size');

        $offset = $pageIndex==1 ? 0 : ($pageIndex-1)*$size;

        //最大展示商品数量
        if($restrict)
        {
            if($offset>$restrict)  return [];

            $size = ($restrict-$offset)>=$size ? $size : $restrict-$offset;
        }
        //排序
        list($column,$order) = $this->filterConditionForOrder($a,$b,$condition);

        //查询条件
        list($where,$whereBetween) = $this->filterConditionForWhere($b,$condition);

        $where[]  = ["{$a}.assemble_id",'=',$aid];

        if(empty($whereBetween))
        {
            $data = DB::table($a)
                ->join($b,"{$a}.wares_id","{$b}.id")
                ->where($where)
                ->forPage($pageIndex,$size)
                ->orderBy($column,$order)
                ->select("{$b}.code")
                ->get()
                ->toArray();

        }else{
            $data = DB::table($a)
                ->join($b,"{$a}.wares_id","{$b}.id")
                ->whereBetween($whereBetween[0],$whereBetween[1])
                ->where($where)
                ->offset($offset)
                ->limit($size)
                ->orderBy($column,$order)
                ->select("{$b}.code")
                ->get()
                ->toArray();
        }
        return $data;
    }

/*************************************************************************************************************/
    /**
     * 排序条件过滤
     * @param $a
     * @param $b
     * @param array $data
     * @return array
     */
    private  function  filterConditionForOrder($a,$b,array $data)
    {
        $order  ='DESC';$column = "{$a}.sort";

        if(isset($data['price']) && $data['price'])
        {
            $column = "{$b}.integral";
            $order = $data['price']==1 ? 'ASC' : 'DESC';
        }
        if(isset($data['sales']) && $data['sales'])
        {
            $column = "{$b}.sales";
            $order = $data['sales']==1 ? 'ASC' : 'DESC';
        }
        if(isset($data['time']) && $data['time'])
        {
            $column = "{$b}.created_at";
            $order = 'DESC';
        }

        return [$column,$order];
    }


    /**
     * 查询条件过滤
     * @param $b
     * @param array $data
     * @return array
     */
    private function  filterConditionForWhere($b,array $data) : array
    {
        $where = []; $whereBetween = [];

        $where[] =["{$b}.status",'=',1] ;

        if(isset($data['category_id']) && $data['category_id']) $where[] =["{$b}.itemize_id",'=',$data['category_id']] ;

        if(isset($data['keyword']) && $data['keyword'])         $where[] = ["{$b}.title",'like','%'.trim($data['keyword']).'%'];

        if(isset($data['price_start']) && $data['price_start'])
        {
            if(isset($data['price_end']) && $data['price_end'])
            {
                $whereBetween =["{$b}.integral",[$data['price_start'],$data['price_end']]];
            }else{
                $where[] = ["{$b}.integral",'>=',$data['price_start']];
            }
        }

        return [$where,$whereBetween];
    }
}