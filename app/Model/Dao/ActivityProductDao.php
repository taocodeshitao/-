<?php

namespace App\Model\Dao;


use Swoft\Bean\Annotation\Mapping\Bean;
use Swoft\Db\DB;

/**
 * 活动商品数据操作类
 * @Bean()
 */
class ActivityProductDao
{

    /**
     *  活动启用
     */
    const  ACTIVITY_STATUS_ENABLE  = 1;

    /**
     * 活动禁用
     */
    const  ACTIVITY_STATUS_UNENABLE = 0;

    /**
     * 获取商品列表，所有
     * @return array
     */
    public  function getList($activity_id,$dot_id):array
    {
        $where[] = ['activity_product.status','=',self::ACTIVITY_STATUS_ENABLE];
        $where[] = ['activity_product.activity_id','=',$activity_id];
        //$where[] = ['activity_product_stock.is_stokc_status','=',1];
        $where[] = ['product_img.is_main','=',1];
        $where[] = ['product.status','=',1];
        //$where[] = ['activity_dot_stock.is_recovery','=',0];
        //$where[] = ['activity_dot_stock.dot_id','=',$dot_id];

        $data = DB::table('activity_product')
            ->leftJoin('activity_product_stock','activity_product_stock.activity_product_id','=','activity_product.id')
            ->leftJoin('product','product.id','=','activity_product.product_id')
            ->leftJoin('grade','grade.id','=','activity_product.grade_id')
            ->leftJoin('product_img','product_img.product_id','=','activity_product.product_id')
            //->leftJoin('product','product.id','=','activity_product.product_id')
            //->leftJoin('activity_dot_stock','activity_dot_stock.activity_product_stock_id','=','activity_product_stock.id')
            ->where($where)
            ->select('activity_product.*','grade.name as grade_name','product.name as product_name','product_img.url')
            ->groupBy('activity_product.product_id')
            ->get()
            ->toArray();
        $activity_data =DB::table('activity')->where('id',$activity_id)->first();

        foreach ($data as $key=>&$v){
            if ($activity_data['activity_type']==1){
            $data1 = DB::table('activity')
                ->leftJoin('grade','grade.activity_id','=','activity.id')
                ->leftJoin('activity_product','activity_product.activity_id','=','activity.id')
                ->where(['grade.activity_id'=>$activity_id,'grade.id'=>$v['grade_id'],'activity_product.product_id'=>$v['product_id']])
                ->select('grade.grade_money')
                ->first();

                //默认没有超出预算
                $v['activity_budget']=true;
                //var_dump($activity_data['activity_moeny'],$activity_data['use_activity_moeny'],$data1);
                if($activity_data['activity_moeny']-$activity_data['use_activity_moeny']<$data1['grade_money']){
                    $v['activity_budget']=false;
                }

            }else{
                $data1 = DB::table('activity_product')
                    ->leftJoin('activity_product_stock','activity_product_stock.activity_product_id','=','activity_product.id')
                    ->leftJoin('product','product.id','=','activity_product.product_id')
                    ->leftJoin('grade','grade.id','=','activity_product.grade_id')
                    ->leftJoin('product_img','product_img.product_id','=','activity_product.product_id')
                    ->leftJoin('activity_dot_stock','activity_dot_stock.activity_product_stock_id','=','activity_product_stock.id')
                    ->where(['activity_dot_stock.activity_id'=>$activity_id,'activity_dot_stock.dot_id'=>$dot_id,'activity_product.product_id'=>$v['product_id']])
                    ->select('activity_dot_stock.surplus_stock_num','grade.grade_money')
                    ->first();

                if (empty($data1)) {
                    $v['surplus_stock_num'] = 0;
                }else{
                    $v['surplus_stock_num']=$data1['surplus_stock_num'];
                }
            }

        }

        return $data;
    }


    /**
     * 会员端获取活动详情商品列表
     */
    public  function getMemberList($activity_id):array
    {
        $where[] = ['activity_product.status','=',self::ACTIVITY_STATUS_ENABLE];
        $where[] = ['activity_product.activity_id','=',$activity_id];
        //$where[] = ['activity_product_stock.is_stokc_status','=',1];
        $where[] = ['product_img.is_main','=',1];
        $where[] = ['product.status','=',1];

        $data = DB::table('activity_product')
            //->leftJoin('activity_product_stock','activity_product_stock.activity_product_id','=','activity_product.id')
            ->leftJoin('product','product.id','=','activity_product.product_id')
            ->leftJoin('product_img','product_img.product_id','=','activity_product.product_id')
            ->leftJoin('grade','grade.id','=','activity_product.grade_id')
            //->leftJoin('activity_dot_stock','activity_dot_stock.activity_product_stock_id','=','activity_product_stock.id')
            ->where($where)
            ->select('activity_product.*','grade.name as grade_name','product.name as product_name','product_img.url')
            ->orderBy('activity_product.sort')
            ->get()
            ->toArray();

        return $data;
    }

    /**
     * 根据活动ID和商品ID查询一条商品信息（线上）
     *
     * @param int $activity_id
     * @param int $product_id
     */
    public function getProductById(int $activity_id,int $product_id,int $grade_id){
        $where[] = ['activity_product.activity_id','=',$activity_id];
        $where[] = ['activity_product.product_id','=',$product_id];
        $where[] = ['activity_product.grade_id','=',$grade_id];
        $where[] = ['product_img.is_main','=',1];

        return DB::table('activity_product')
            ->leftJoin('activity','activity.id','=','activity_product.activity_id')
            ->leftJoin('product','product.id','=','activity_product.product_id')
            ->leftJoin('product_img','product_img.product_id','=','activity_product.product_id')
            ->where($where)
            ->select('activity.activity_code','activity.name as activity_name','product.name as product_name','product_img.url','activity.activity_type')
            ->first();
    }

    /***
     * 根据活动ID和商品ID支行ID查询一条商品信息（线下）
     * @param int $activity_id
     * @param int $product_id
     * @param int $dot_id
     */
    public function getProductDotById(int $activity_id,int $product_id,int $dot_id,$grade_id){
        $where[] = ['activity_product.activity_id','=',$activity_id];
        $where[] = ['activity_product.product_id','=',$product_id];
        //$where[] = ['activity_dot_stock.dot_id','=',$dot_id];
        $where[] = ['activity_product.grade_id','=',$grade_id];
        $where[] = ['product_img.is_main','=',1];

        return DB::table('activity_product')
            ->leftJoin('activity','activity.id','=','activity_product.activity_id')
            ->leftJoin('product','product.id','=','activity_product.product_id')
            ->leftJoin('product_img','product_img.product_id','=','activity_product.product_id')
            ->leftJoin('product_price','product_price.product_id','=','activity_product.product_id')
            ->leftJoin('activity_product_stock','activity_product_stock.activity_product_id','=','activity_product.id')
            //->leftJoin('activity_dot_stock','activity_dot_stock.activity_product_stock_id','=','activity_product_stock.id')
            ->where($where)
            ->select('product_price.settlement_price','activity_product_stock.stock_sum','activity_product_stock.received_stock','activity.activity_code','activity.name as activity_name','product.name as product_name','product_img.url','activity.activity_type')
            ->first();
    }

    /**
     * //根据活动id和商品ID查询一条信息（线上）
     * @param int $activity_id
     * @param int $product_id
     * @return object|\Swoft\Db\Eloquent\Model|\Swoft\Db\Query\Builder|null
     * @throws \Swoft\Db\Exception\DbException
     */
    public function getProductActivitById(int $activity_id,int $product_id){
        $where[] = ['activity_product.activity_id','=',$activity_id];
        $where[] = ['activity_product.product_id','=',$product_id];
        $where[] = ['product_img.is_main','=',1];

        return DB::table('activity_product')
            ->leftJoin('activity','activity.id','=','activity_product.activity_id')
            ->leftJoin('product','product.id','=','activity_product.product_id')
            ->leftJoin('product_img','product_img.product_id','=','activity_product.product_id')
            ->where($where)
            ->select('activity_product.id','activity.activity_code','activity.name as activity_name','product.nature','product.name as product_name','product_img.url','activity.activity_type')
            ->first();
    }

    /**
     * //根据活动id和商品ID查询一条信息（线下）
     * @param int $activity_id
     * @param int $product_id
     * @return object|\Swoft\Db\Eloquent\Model|\Swoft\Db\Query\Builder|null
     * @throws \Swoft\Db\Exception\DbException
     */
    public function getProductActivitByIdxia(int $activity_id,int $product_id){
        $where[] = ['activity_product.activity_id','=',$activity_id];
        $where[] = ['activity_product.product_id','=',$product_id];
        $where[] = ['product_img.is_main','=',1];

        return DB::table('activity_product')
            ->leftJoin('activity','activity.id','=','activity_product.activity_id')
            ->leftJoin('product','product.id','=','activity_product.product_id')
            ->leftJoin('product_img','product_img.product_id','=','activity_product.product_id')
            ->leftJoin('activity_product_stock','activity_product_stock.activity_product_id','=','activity_product.id')
            ->where($where)
            ->select('activity_product.id','product.nature','activity.activity_code','activity.name as activity_name','product.name as product_name','product_img.url','activity.activity_type')
            ->first();
    }

    /**
     * 根据活动ID，商品ID，档次ID查询一条商品数据（会员端商品接口详情调用）
     */
    public function getMemberActivityProductById(int $activity_id,int $product_id,int $grade_id){
        $where[] = ['activity_product.activity_id','=',$activity_id];
        $where[] = ['activity_product.product_id','=',$product_id];
        $where[] = ['activity_product.grade_id','=',$grade_id];
        $where[] = ['product_img.is_main','=',1];

        return DB::table('activity_product')
            ->where($where)
            ->leftJoin('product','product.id','=','activity_product.product_id')
            ->leftJoin('product_info','product_info.product_id','=','activity_product.product_id')
            ->leftJoin('product_img','product_img.product_id','=','activity_product.product_id')
            ->select('product.name','product_info.pc_info as wap_info','product_img.url')
            ->first();
    }

    /**
     * 根据活动id和商品ID查询一条信息
     */
    public function getActivityProductById(int $activity_id,int $product_id){

        $where[] = ['activity_id','=',$activity_id];
        $where[] = ['product_id','=',$product_id];

        return DB::table('activity_product')->where($where)->first();
    }

    /**
     * 根据活动ID获取所有的档次
     */
    public function getActivityProductGrade(int $activity_id){
        $where[] = ['activity_id','=',$activity_id];

        return DB::table('activity_product')->where($where)->get()->toArray();
    }

    /**
     * 商品库存列表
     */
    public function getProductStockList($params){

        //$where[] = ['activity_product.status','=',self::ACTIVITY_STATUS_ENABLE];
        $where[] = ['product_img.is_main','=',1];
        $where[] = ['activity_dot_stock.dot_id','=',$params['dot_id']];

        if (!empty($params['product_name'])) $where[] = ['product.name', 'like', '%'.$params['product_name'].'%'];

        //排序条件
        $order_by_name = 'activity_product.created_at';
        $order_by_sort = 'asc';
        if (!empty($params['sort_type'])){
            switch ($params['sort_type'])
            {
                case 1: //入库时间
                    $order_by_name = 'activity_product.created_at';
                    break;

                case 2: //库存
                    $order_by_name = 'activity_dot_stock.surplus_stock_num';
                    break;
            }
        }
        if(!empty($param['sort_by']))
        {
            $order_by_sort = $param['sort_by']==1 ? 'asc' : 'desc';
        }

        $data = DB::table('activity_product')
            ->leftJoin('activity_product_stock','activity_product_stock.activity_product_id','=','activity_product.id')
            ->leftJoin('product','product.id','=','activity_product.product_id')
            ->leftJoin('product_img','product_img.product_id','=','activity_product.product_id')
            ->leftJoin('activity_dot_stock','activity_dot_stock.activity_product_stock_id','=','activity_product_stock.id')
            ->where($where)
            ->select('activity_product.*','activity_dot_stock.stock_num','activity_dot_stock.surplus_stock_num','product.code','product.name as product_name','product_img.url')
            ->groupBy('activity_product.product_id')
            ->orderBy($order_by_name,$order_by_sort)
            ->get()
            ->toArray();
        return $data;
    }

    /**
     * 根据商品ID查询所有的满足条件的商品
     */
    public function getProductStockDetails($params){

        $where[] = ['product.id','=',$params['product_id']];
        //商品详情
        $product_info = DB::table('product')
            ->leftJoin('product_img','product_img.product_id','=','product.id')
            ->where($where)
            ->select('product.id','product.code','product.name as product_name','product_img.url')
            ->first();

        //商品剩余库存
       $surplus_stock_num = DB::table('activity_product')
            ->leftJoin('activity_product_stock','activity_product_stock.activity_product_id','=','activity_product.id')
            ->leftJoin('activity_dot_stock','activity_dot_stock.activity_product_stock_id','=','activity_product_stock.id')
            ->where('activity_product.product_id',$params['product_id'])
            ->sum('activity_dot_stock.surplus_stock_num');

       //获取活动数量
        $where1[]=['activity_product.product_id','=',$params['product_id']];
        $where1[]=['activity.activity_type','=',2];
        $activity_data = DB::table('activity_product')
            ->leftJoin('activity','activity.id','=','activity_product.activity_id')
            ->where($where1)
            //->groupBy('product_id')
            ->select('activity_id')
            ->get()->toArray();

        return [
            'product_info'=>$product_info,
            'surplus_stock_num'=>$surplus_stock_num,
            'activity_data'=>$activity_data
        ];
    }

    /**
     * 根据活动ID和商品ID查询库存剩余总数
     */
    public function getActivityProductCount(int $activity_id,int $product_id){
        $where[] = ['activity_product.activity_id','=',$activity_id];
        $where[] = ['activity_product.product_id','=',$product_id];
       // $where[] = ['activity.activity_type','=',2];
        return DB::table('activity_product')
            ->leftJoin('activity_product_stock','activity_product_stock.activity_product_id','=','activity_product.id')
            ->leftJoin('activity_dot_stock','activity_dot_stock.activity_product_stock_id','=','activity_product_stock.id')
            ->where($where)
            ->sum('activity_dot_stock.surplus_stock_num');

    }
}