<?php

namespace App\Model\Dao;


use Swoft\Bean\Annotation\Mapping\Bean;
use Swoft\Db\DB;

/**
 * 支行库存数据操作类
 * @Bean()
 */
class ActivityDotStockDao
{
    /**
     * 获取活动详情
     * @param string $code
     * @return null|object|\Swoft\Db\Eloquent\Model|static
     */
    public  function getDotStock($activity_id,$dot_id,$region_id,$product_id)
    {

        $where1[] = ['activity_product.product_id','=',$product_id];
        $where1[] = ['activity_product.activity_id','=',$activity_id];

        //1根据商品查询
        $data1 = DB::table('activity_product')
            ->leftJoin('activity_product_stock','activity_product_stock.activity_product_id','=','activity_product.id')
            ->select("activity_product_stock.id")
            ->where($where1)
            ->first();

        $where[] = ['activity_dot_stock.dot_id','=',$dot_id];
        $where[] = ['activity_dot_stock.region_id','=',$region_id];
        $where[] = ['activity_dot_stock.activity_product_stock_id','=',$data1['id']];

        $data = DB::table('activity_dot_stock')
            ->where($where)
            ->select('activity_dot_stock.id','activity_dot_stock.surplus_stock_num','activity_dot_stock.id')
            ->first();

        return  $data;
    }

    /**
     * 根据网点，区域，活动ID查询库存
     * @param $activity_id
     * @param $dot_id
     * @param $region_id
     */
    public function getDotProductStock($activity_id,$dot_id,$region_id){
        $where[] = ['activity_dot_stock.activity_id','=',$activity_id];
        $where[] = ['activity_dot_stock.dot_id','=',$dot_id];
        $where[] = ['activity_dot_stock.region_id','=',$region_id];

        $data = DB::table('activity_dot_stock')
            ->leftJoin('activity_product_stock','activity_product_stock.id','=','activity_dot_stock.activity_product_stock_id')
            ->leftJoin('activity_product','activity_product.id','=','activity_product_stock.activity_product_id')
            ->where($where)
            ->select('activity_product.product_id','activity_dot_stock.surplus_stock_num')
            ->get()->toArray();

        return  $data;
    }
    /**
     * 减库存
     */
    public  function getReduceStock($id)
    {
        $where = array();
        $where[] = ['id','=',$id];
        return DB::table('activity_dot_stock')->where($where)->decrement('surplus_stock_num',1);
    }

    /**
     * 商品回收数据操作
     */
    public function lossStockNum($id,$activity_id,$loss_num){
        $where = array();
        $where[] = ['id','=',$id];
        $where[] = ['activity_id','=',$activity_id];
        DB::table('activity_dot_stock')->where($where)->decrement('surplus_stock_num',$loss_num);


        DB::table('activity_dot_stock')->where($where)->update(['loss_stock_num'=>$loss_num,'is_loss'=>0]);

        return true;
    }

    /**
     * 根据ID查询一条数据
     */
    public function getActivityDotStock($id,$activity_id=0){
        $where = array();
        $where[] = ['id','=',$id];
        if ($activity_id>0){
            $where[] = ['activity_id','=',$activity_id];
        }
        return DB::table('activity_dot_stock')->where($where)->first();
    }



    /**
     * 根据活动ID和商品ID获取一条支行库存的基本信息
     */
    public function getActivityDotStockItem($product_id,$activity_id){
        $where[] = ['activity_product.activity_id','=',$activity_id];
        $where[] = ['activity_product.product_id','=',$product_id];

        return DB::table('activity_product')
            ->leftJoin('activity_product_stock','activity_product_stock.activity_product_id','=','activity_product.id')
            ->leftJoin('activity_dot_stock','activity_dot_stock.activity_product_stock_id','=','activity_product_stock.id')
            ->where($where)
            ->select('activity_dot_stock.*')->first();


    }

    /**
     * 库存管理员根据支行ID查询商品库存数量
     */
    public function getDotStockNum($dot_id){
        $where = array();
        $where[] = ['dot_id','=',$dot_id];
        $where[] = ['recovery_stock_num','!=',0];
        //待出库（回收）
        $recovery_stock_num = DB::table('activity_dot_stock')
            //->leftJoin('activity_product_stock','activity_product_stock.id','=','activity_dot_stock.activity_product_stock_id')
            ->where($where)
            ->select('recovery_stock_num')->count();

        $where1 = array();
        $where1[] = ['dot_id','=',$dot_id];
        //$where1[] = ['activity_dot_stock.is_stokc_status','=',0];
        $where1[] = ['receive_stock_num','!=',0];
        //待入库（分配）
        $receive_stock_num = DB::table('activity_dot_stock')
            //->leftJoin('activity_product_stock','activity_product_stock.id','=','activity_dot_stock.activity_product_stock_id')
            ->where($where1)
            ->select('receive_stock_num')->count();

        return ['receive_stock_num'=>$receive_stock_num,'recovery_stock_num'=>$recovery_stock_num];
    }

    /**
     * 库存管理员根据支行ID查询库存管理员需要确认入库的商品
     */
    public function getDotStockConfirmAddLsit($dot_id){

        $where = array();
        $where[] = ['activity_dot_stock.dot_id','=',$dot_id];
        //$where[] = ['activity_dot_stock.is_stokc_status','=',1];
        $where[] = ['activity_dot_stock.receive_stock_num','>',0];
        $where[] = ['product_img.is_main','=',1];


        $receive_stock_num = DB::table('activity_dot_stock')
            ->leftJoin('activity_product_stock','activity_product_stock.id','=','activity_dot_stock.activity_product_stock_id')
            ->leftJoin('activity_product','activity_product.id','=','activity_product_stock.activity_product_id')
            ->leftJoin('activity','activity.id','=','activity_product.activity_id')
            ->leftJoin('product','product.id','=','activity_product.product_id')
            ->leftJoin('product_img','product_img.product_id','=','activity_product.product_id')
            ->where($where)
            ->select('product.id as product_id','product.name as product_name','product_img.url','product.code','activity_dot_stock.receive_stock_num','activity.name as activity_name','activity_dot_stock.id')
            ->get()->toArray();

        //查询操作日志
        foreach ($receive_stock_num as $key=>&$value){
            $where1 = array();
            $where1[] = ['activity_dot_stock_log.activity_dot_stock_id','=',$value['id']];
            $where1[] = ['activity_dot_stock_log.type','=',1];

            $data = DB::table('activity_dot_stock_log')
                ->leftJoin('activity_dot_stock','activity_dot_stock.id','=','activity_dot_stock_log.activity_dot_stock_id')
                ->where($where1)
                ->first();

            //查询库存分配管理员
            $receive_user_data = DB::table('system_user')->where('id',$data['receive_user_id'])->select('name')->first();
            $out_of_stock_user_data = DB::table('system_user')->where('id',$data['out_of_stock_user_id'])->select('name')->first();

            $value['receive_user_name']=$receive_user_data['name'];
            $value['out_of_stock_user_name']=$out_of_stock_user_data['name'];
            $value['receive_time']=$data['receive_time'];
            $value['out_of_stock_time']=$data['out_of_stock_time'];
            $value['activity_dot_stock_log_id']=$data['id'];
        }

        return $receive_stock_num;
    }

    /**
     * 库存管理员根据支行ID查询库存管理员需要确认入库的商品
     */
    public function dotStockConfirmOutList($dot_id){
        $where = array();
        $where[] = ['activity_dot_stock.dot_id','=',$dot_id];
        //$where[] = ['activity_dot_stock.is_recovery','=',0];
        $where[] = ['activity_dot_stock.recovery_stock_num','>',0];
        $where[] = ['product_img.is_main','=',1];

        $receive_stock_num = DB::table('activity_dot_stock')
            ->leftJoin('activity_product_stock','activity_product_stock.id','=','activity_dot_stock.activity_product_stock_id')
            ->leftJoin('activity_product','activity_product.id','=','activity_product_stock.activity_product_id')
            ->leftJoin('activity','activity.id','=','activity_product.activity_id')
            ->leftJoin('product','product.id','=','activity_product.product_id')
            ->leftJoin('product_img','product_img.product_id','=','activity_product.product_id')
            ->where($where)
            ->select('activity_product.product_id','product.name as product_name','product_img.url','product.code','activity_dot_stock.recovery_stock_num as receive_stock_num','activity.name as activity_name','activity_dot_stock.id')
            ->get()->toArray();

        //查询操作日志
        foreach ($receive_stock_num as $key=>&$value){
            $where1 = array();
            $where1[] = ['activity_dot_stock_log.activity_dot_stock_id','=',$value['id']];
            $where1[] = ['activity_dot_stock_log.type','=',2];

            $data = DB::table('activity_dot_stock_log')
                ->leftJoin('activity_dot_stock','activity_dot_stock.id','=','activity_dot_stock_log.activity_dot_stock_id')
                ->where($where1)
                ->select('activity_dot_stock_log.*')
                ->orderBy('activity_dot_stock_log.id','desc')
                ->first();

            //查询库存分配管理员
            $recovery_user_data = DB::table('system_user')->where('id',$data['recovery_user_id'])->select('name')->first();

            $value['recovery_user_name']=$recovery_user_data['name'];
            $value['recovery_time']=$data['recovery_time'];
            $value['activity_dot_stock_log_id']=$data['id'];
        }

        return $receive_stock_num;
    }

    /**
     * 修改支行待接收库存
     * @param int $id
     */
    public function updateReceiveStockNum(int $id,int $receive_stock_num){
        $where = array();
        $where[] = ['id','=',$id];

        //待入库库存改为0
        DB::table('activity_dot_stock')->where($where)->update(['receive_stock_num'=>0]);

        //添加剩余库存
        DB::table('activity_dot_stock')->where($where)->increment('surplus_stock_num',$receive_stock_num);

        //添加总库存
        DB::table('activity_dot_stock')->where($where)->increment('stock_num',$receive_stock_num);

        return true;

    }

    /**
     * 修改支行要回收的库存
     */
    public function updateRecoveryStockNum(int $id,int $receive_stock_num){
        $where = array();
        $where[] = ['id','=',$id];

        //待入库库存改为0
        $update=[
            'recovery_stock_num'=>0,
           // 'is_recovery'=>1,
        ];
        DB::table('activity_dot_stock')->where($where)->update($update);

        //扣减剩余库存
        DB::table('activity_dot_stock')->where($where)->decrement('surplus_stock_num',$receive_stock_num);

        return true;
    }

    /**
     * 扣减支行库存
     */
    public function decrementStock($activity_id,$dot_id,$product_id){
        $where = array();
        $where[] = ['activity_dot_stock.activity_id','=',$activity_id];
        $where[] = ['activity_dot_stock.dot_id','=',$dot_id];
        $where[] = ['activity_product.product_id','=',$product_id];
        return DB::table('activity_dot_stock')
            ->leftJoin('activity_product_stock','activity_product_stock.id','=','activity_dot_stock.activity_product_stock_id')
            ->leftJoin('activity_product','activity_product.id','=','activity_product_stock.activity_product_id')
            ->where($where)
            ->decrement('activity_dot_stock.surplus_stock_num',1);

    }

    /**
     * 未发货订单自动取消，并返还库存
     */
    public function incrementOrderStock($activity_id,$dot_id,$product_id){
        $where = array();
        $where[] = ['activity_dot_stock.activity_id','=',$activity_id];
        $where[] = ['activity_dot_stock.dot_id','=',$dot_id];
        $where[] = ['activity_product.product_id','=',$product_id];
        return DB::table('activity_dot_stock')
            ->leftJoin('activity_product_stock','activity_product_stock.id','=','activity_dot_stock.activity_product_stock_id')
            ->leftJoin('activity_product','activity_product.id','=','activity_product_stock.activity_product_id')
            ->where($where)
            ->increment('activity_dot_stock.surplus_stock_num',1);

    }

    /**
     * 新增已兑换的库存
     */
    public function incrementStock($activity_id,$dot_id,$product_id){
        $where = array();
        $where[] = ['activity_dot_stock.activity_id','=',$activity_id];
        $where[] = ['activity_dot_stock.dot_id','=',$dot_id];
        $where[] = ['activity_product.product_id','=',$product_id];

        return DB::table('activity_dot_stock')
            ->leftJoin('activity_product_stock','activity_product_stock.id','=','activity_dot_stock.activity_product_stock_id')
            ->leftJoin('activity_product','activity_product.id','=','activity_product_stock.activity_product_id')
            ->where($where)
            ->increment('activity_product_stock.received_stock',1);

    }

}