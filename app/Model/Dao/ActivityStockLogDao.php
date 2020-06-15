<?php

namespace App\Model\Dao;


use Swoft\Bean\Annotation\Mapping\Bean;
use Swoft\Db\DB;

/**
 * 库存变动记录
 * @Bean()
 */
class ActivityStockLogDao
{

    /**
     * 添加数据
     * @param array $data
     * @return string
     */
    public  function addData(array $data)
    {
        return DB::table('activity_stock_log')->insertGetId($data);
    }

    /**
     * 查询库存变动记录
     */
    public function getAllPage(int $dot_id, int $page = 1,$params){
        $where=array();

        //商品名称
        if(!empty($params['product_name'])){
            $where[] = ['p.name','like','%'.$params['product_name'].'%'];
        }

        $where[] = ['d.dot_id','=',$dot_id];

        $onWhere = array();
        $onWhere[] = ['d.recovery_stock_num','=',0];
        $onWhere[] = ['d.receive_stock_num','=',0];

        if (!empty($params['type'])){

            $whereIn=[];
            //入库
            if ($params['type']==1){
                $whereIn =[1,3];
            }

            //出库
            if ($params['type']==2){
                $whereIn = [2,4];
            }
            $data = DB::table('activity_dot_stock_log')
                ->leftJoin('order as c', 'c.id', '=', 'activity_dot_stock_log.order_id')
                ->leftJoin('activity_dot_stock as d', 'd.id', '=', 'activity_dot_stock_log.activity_dot_stock_id')
                ->leftJoin('activity_product_stock as f', 'f.id', '=', 'd.activity_product_stock_id')
                ->leftJoin('activity_product as g', 'g.id', '=', 'f.activity_product_id')
                ->leftJoin('product as p', 'p.id', '=', 'g.product_id')
                ->select("activity_dot_stock_log.*",'c.created_at as order_time','c.sn','p.name','g.product_id','d.recovery_stock_num','d.receive_stock_num')
                ->where($where)
                ->whereIn('activity_dot_stock_log.type',$whereIn)
                //->orWhere($onWhere)
                ->forPage($page,config('page_size'))
                ->orderBy('activity_dot_stock_log.updated_at','desc')
                ->get()
                ->toArray();

        } else{
            $data = DB::table('activity_dot_stock_log')
                ->leftJoin('order as c', 'c.id', '=', 'activity_dot_stock_log.order_id')
                ->leftJoin('activity_dot_stock as d', 'd.id', '=', 'activity_dot_stock_log.activity_dot_stock_id')
                ->leftJoin('activity_product_stock as f', 'f.id', '=', 'd.activity_product_stock_id')
                ->leftJoin('activity_product as g', 'g.id', '=', 'f.activity_product_id')
                ->leftJoin('product as p', 'p.id', '=', 'g.product_id')
                ->select("activity_dot_stock_log.*",'c.sn','c.created_at as order_time','p.name','g.product_id','d.recovery_stock_num','d.receive_stock_num')
                ->where($where)
                ->forPage($page,config('page_size'))
                ->orderBy('c.id','desc')
                ->get()
                ->toArray();
        }

        //删除待接收库存为0或者回收待确认库存为0的数据
        foreach ($data as  $k=>&$v){
            if ($v['recovery_stock_num']!=0||$v['receive_stock_num']!=0){
                unset($data[$k]);
            }

            if ($v['type']==1){
                $v['time']=$v['warehousing_time'];
            }elseif ($v['type']==2){
                $v['time']=$v['recovery_confirm_time'];
            }elseif ($v['type']==3){
                $v['time']=$v['loss_time'];
            }else{
                $v['time']=$v['order_time'];
            }
        }

        return $data;
    }

    /**
     * 变动明细（入库）
     */
    public function getStockChangeDetails(int $id){
        $where[]=['activity_dot_stock_log.id','=',$id];
        $where[]=['product_img.is_main','=',1];

        $data = DB::table('activity_dot_stock_log')
            ->leftJoin('activity_dot_stock as b', 'b.id', '=', 'activity_dot_stock_log.activity_dot_stock_id')
            ->leftJoin('activity as h', 'h.id', '=', 'b.activity_id')
            ->leftJoin('activity_dot_stock as d', 'd.id', '=', 'activity_dot_stock_log.activity_dot_stock_id')
            ->leftJoin('activity_product_stock as f', 'f.id', '=', 'd.activity_product_stock_id')
            ->leftJoin('activity_product as g', 'g.id', '=', 'f.activity_product_id')
            ->leftJoin('product', 'product.id', '=', 'g.product_id')
            ->leftJoin('product_img', 'product_img.product_id', '=', 'g.product_id')
            ->select('h.name as activity_name',"activity_dot_stock_log.*",'product.name','product.code','g.product_id','product_img.url')
            ->where($where)
            ->first();

        return $data;
    }

    /**
     * 变动明细（订单出库）
     */
    public function getStockChangeOrderDetails(int $id){
        $where[]=['activity_dot_stock_log.id','=',$id];
        $where[]=['product_img.is_main','=',1];

        $data = DB::table('activity_dot_stock_log')
            ->leftJoin('activity_dot_stock as b', 'b.id', '=', 'activity_dot_stock_log.activity_dot_stock_id')
            ->leftJoin('activity_dot_stock as d', 'd.id', '=', 'activity_dot_stock_log.activity_dot_stock_id')
            ->leftJoin('activity as h', 'h.id', '=', 'b.activity_id')
            ->leftJoin('activity_product_stock as f', 'f.id', '=', 'd.activity_product_stock_id')
            ->leftJoin('activity_product as g', 'g.id', '=', 'f.activity_product_id')
            ->leftJoin('product', 'product.id', '=', 'g.product_id')
            ->leftJoin('product_img', 'product_img.product_id', '=', 'g.product_id')
            ->leftJoin('order', 'order.id', '=', 'activity_dot_stock_log.order_id')
            ->leftJoin('member', 'member.id', '=', 'order.member_id')
            ->select('order.confirm_order_user_id','order.updated_at as  confirm_order_time','h.name as activity_name','member.unique_code',"activity_dot_stock_log.*",'product.name','product.code','g.product_id','product_img.url')
            ->where($where)
            ->first();

        return $data;
    }

    /**
     * 修改状态
     */
    public function updateStatus($product_id,$dot_id,$activity_id,$user_id){
        $where = array();
        $where[] = ['product_id','=',$product_id];
        $where[] = ['dot_id','=',$dot_id];
        $where[] = ['activity_id','=',$activity_id];
        $where[] = ['type','=',1];
        return DB::table('activity_stock_log')->where($where)->update(['is_comfirmation'=>1,'confirmation_time'=>time(),'identify_people'=>$user_id]);
    }

    /**
     * 修改回收确认信息
     * @param $order_id
     * @param $user_id
     * @return int
     * @throws \ReflectionException
     * @throws \Swoft\Bean\Exception\ContainerException
     * @throws \Swoft\Db\Exception\DbException
     */
    public function updateRecoveryStatus($product_id,$dot_id,$activity_id,$user_id){
        $where = array();
        $where[] = ['product_id','=',$product_id];
        $where[] = ['dot_id','=',$dot_id];
        $where[] = ['activity_id','=',$activity_id];
        $where[] = ['type','=',4];
        $where[]=['is_comfirmation',0];

        return DB::table('activity_stock_log')->where($where)->update(['is_comfirmation'=>1,'confirmation_time'=>time(),'identify_people'=>$user_id]);

    }

    //根据订单号修改数据
    public function updateOrderSn($order_id,$user_id){
        $where[] = ['order_id','=',$order_id];

        return DB::table('activity_stock_log')->where($where)->update(['is_comfirmation'=>1,'confirmation_time'=>time(),'identify_people'=>$user_id]);
    }
}