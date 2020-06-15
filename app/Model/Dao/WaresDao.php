<?php
/**
 * description WaresDao.php
 * Created by PhpStorm.
 * User: zengqb
 * DateTime: 2019/12/20 15:10
 */

namespace App\Model\Dao;


use Swoft\Bean\Annotation\Mapping\Bean;
use Swoft\Db\DB;

/**
 * 商品数据库操作
 * Class WaresDao
 *
 * @Bean()
 */
class WaresDao
{

    /**
     * 商品禁用
     */
    const WARES_STATUS_UNENABLE = 0;

    /**
     * 商品可用
     */
    const WARES_STATUS_ENBALE = 1;

    /**
     * 根据id获取单条商品记录
     * @param int $product_id
     * @param array $fields
     * @return array
     */
    public  function findById(int $product_id,$fields= ['*'])
    {

        return DB::table('commodity_wares')->find($product_id,$fields);
    }


    /**
     * 根据sku获取单条商品记录
     * @param string $sku
     * @param array $fields
     * @return null|object|\Swoft\Db\Eloquent\Model|static
     */
    public  function findBySku(string $sku,array $fields= ['*'])
    {

        return DB::table('commodity_wares')->where('code',$sku)->first($fields);
    }


    /**
     * 根据sku更新单条商品销量
     * @param string $sku
     * @param int $num
     * @return int
     */
    public  function updateBySku(string $sku,int $num)
    {
        return DB::table('commodity_wares')->where('code',$sku)->increment('sales',$num);
    }

    /**
     * 根据sku获取单个商品的详细记录
     * @param string $sku
     * @return null|object|\Swoft\Db\Eloquent\Model|static
     */
    public  function findDetailBySku(string $sku)
    {
        $a ='commodity_wares';
        $b ='commodity';
        $c ='attachment';

        $data = DB::table($a)
                ->leftJoin($b,"{$a}.source_id","{$b}.source_id")
                ->select("{$a}.id","{$a}.code","{$a}.itemize_id","{$a}.source_id","{$a}.title","{$a}.cover as covers","{$a}.status","{$a}.typeid","{$a}.market_price","{$a}.settlement","{$a}.integral","{$a}.app_introduce","{$a}.product_infos","{$b}.cover","{$b}.images")
                ->where("{$a}.code",$sku)
                ->first();

        if($data['covers'])
        {
            //获取自定义商品图片信息
            $cover = DB::table($c)->whereIn('id',explode(',',$data['covers']))->pluck('url')->toArray();

            //更新轮播图
            $data['images'] = implode(',',$cover).','.$data['images'];

            unset($data['covers']);
        }

        return $data;
    }

    /**
     * 根据分类获取商品sku集合
     * @param array $itemize_list
     * @param int $size
     * @return array
     */
    public  function getListByItemize(array $itemize_list=null,int $size=10)
    {
        if(!empty($itemize_list))
        {
            $data =  DB::table('commodity_wares')
                ->where('status',self::WARES_STATUS_ENBALE)
                ->whereIn('itemize_id',$itemize_list)
                ->limit($size)
                ->select('code')
                ->get()
                ->toArray();
        }else{

            $data =  DB::table('commodity_wares')
                ->where('status',self::WARES_STATUS_ENBALE)
                ->limit($size)
                ->select('code')
                ->orderBy('id','DESC')
                ->get()
                ->toArray();
        }
        return $data;
    }

    /**
     * 获取分页商品sku集合
     * @param int $pageIndex
     * @param array $condition
     * @return array
     */
    public function getPageList(int $pageIndex = 1,array $condition)
    {

        //排序
        list($column,$order) = $this->filterConditionForOrder($condition);

        //查询条件
        list($where,$whereBetween) = $this->filterConditionForWhere($condition);

        if(empty($whereBetween))
        {
            $data =  DB::table('commodity_wares')
                    ->where($where)
                    ->forPage($pageIndex, config('page_size'))
                    ->select('code')
                    ->orderBy($column,$order)
                    ->get()
                    ->toArray();
        }else{

            $data =  DB::table('commodity_wares')
                ->whereBetween($whereBetween[0],$whereBetween[1])
                ->where($where)
                ->forPage($pageIndex, config('page_size'))
                ->select('code')
                ->orderBy($column,$order)
                ->get()
                ->toArray();

        }
        return $data;
    }

    /**
     * 根据排序规则获取对应商品数据
     * @param string $orderDesc
     * @param int $size
     * @return array
     */
    public  function getListByOrder(string $orderDesc,int $size=50)
    {

        $data =  DB::table('commodity_wares')
                ->where('status',self::WARES_STATUS_ENBALE)
                ->select('code')
                ->orderByDesc($orderDesc)
                ->orderBy('id','ASC')
                ->limit($size)
                ->get()
                ->toArray();

        return $data;
    }

    /*************************************************************************************************************/
    /**
     * 排序条件过滤
     * @param array $data
     * @return array
     */
    private  function  filterConditionForOrder(array $data)
    {
        $order  ='ASC';$column = 'id';

        if(isset($data['price']) && $data['price'])
        {
            $column = 'integral';
            $order = $data['price']==1 ? 'ASC' : 'DESC';
        }
        if(isset($data['sales']) && $data['sales'])
        {
            $column = 'sales';
            $order = $data['sales']==1 ? 'ASC' : 'DESC';
        }
        if(isset($data['time']) && $data['time'])
        {
            $column = 'created_at';
            $order = 'DESC';
        }

        return [$column,$order];
    }


    /**
     * 查询条件过滤
     * @param array $data
     * @return array
     */
    private function  filterConditionForWhere(array $data) : array
    {
        $where = []; $whereBetween = [];

        $where[] =['status','=',self::WARES_STATUS_ENBALE] ;

        if(isset($data['category_id']) && $data['category_id']) $where[] =['itemize_id','=',$data['category_id']] ;

        if(isset($data['keyword']) && $data['keyword'])         $where[] = ['title','like','%'.trim($data['keyword']).'%'];

        if(isset($data['price_start']) && $data['price_start'])
        {
            if(isset($data['price_end']) && $data['price_end'])
            {
                $whereBetween =['integral',[$data['price_start'],$data['price_end']]];
            }else{
                $where[] = ['integral','>=',$data['price_start']];
            }
        }
        return [$where,$whereBetween];
    }

}