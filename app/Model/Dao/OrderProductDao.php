<?php declare(strict_types=1);

namespace App\Model\Dao;


use Swoft\Bean\Annotation\Mapping\Bean;
use Swoft\Db\DB;

/**
 * 订单商品数据操作类
 * @Bean()
 */
class OrderProductDao
{


    /**
     * 添加数据
     * @param array $data
     * @return string
     */
    public  function addData(array $data)
    {

        return DB::table('order_product')->insertGetId($data);
    }


    /**
     * 根据订单编号更新数据
     * @param array $condition
     * @param array $data
     * @return int
     */
    public  function updateData(array $condition,array $data)
    {

        return DB::table('order')->where($condition)->update($data);
    }

    /**
     * 根据订单id获取单个订单信息
     * @param int $order_id
     * @param int $user_id
     * @param array $field
     * @return null|object|\Swoft\Db\Eloquent\Model|static
     */
    public  function findById(int $order_id)
    {
        $where['order.id'] = $order_id;
        $where['f.is_main'] = 1;
        $field=['order.express_status','c.created_at as receive_time','g.member_code','order.sn','f.url','order.created_at','d.name','d.activity_type','b.product_name','c.consignee_name','c.consignee_mobile','c.address_info'];

        return DB::table('order')
            ->leftJoin('order_product as b', 'b.order_id', '=', 'order.id')
            ->leftJoin('order_receive as c', 'c.order_id', '=', 'order.id')
            ->leftJoin('activity_member as g', 'g.id', '=', 'order.member_id')
            ->leftJoin('activity as d', 'd.id', '=', 'order.activity_id')
            ->leftJoin('product_img as f', 'f.id', '=', 'b.product_id')
            ->where($where)->first($field);

    }



    /**
     * 获取订单分页数据
     * @param int $dot_id
     * @param int $uid
     * @param array $fields
     * @param int $page
     * @return array
     * @throws \Swoft\Db\Exception\DbException
     */
    public function getOrdersPage(int $dot_id, int $page = 1,$params)
    {
        if (!empty($params['sn']))   $where[] =  ['order.sn','=',$params['sn']];
        if (!empty($params['express_status']))  $where[] = ['order.express_status','=',$params['express_status']];;

        $where['order.dot_id'] = $dot_id;


        return DB::table('order')
            ->leftJoin('order_product as b', 'b.order_id', '=', 'order.id')
            ->leftJoin('product_img as c', 'c.product_id', '=', 'b.product_id')
            ->where($where)
            ->forPage($page,config('page_size'))
            ->select('order.*','b.product_name','c.url')
            ->orderByDesc('order.id')
            ->get()
            ->toArray();
    }

    /**
     * 获取订单分页数据
     * @param array $option
     * @param int $uid
     * @param array $fields
     * @param int $page
     * @return array
     * @throws \Swoft\Db\Exception\DbException
     */
    public function getOrders(array $option, int $uid
        , array $fields = ['id','sn','uid','nature','is_activity','price','total_price','total_integral','integral','payment_time','created_at','state','refund_state','return_state']
    )
    {
        return DB::table('order')
            ->where($option)
            ->where('uid', $uid)
            ->orderByDesc('id')
            ->get($fields)
            ->toArray();
    }

    public function getOrdersWaresFind(int $order_id, $fields = ['*'])
    {
        return DB::table('order_wares as ow')
            ->join('commodity_wares as cw', 'ow.source_id', '=', 'cw.source_id')
            ->leftJoin('commodity as c', 'cw.source_id', '=', 'c.source_id')
            ->where('ow.order_id', $order_id)
            ->get($fields)
            ->toArray();

    }

    public function getTypeSum(int $uid, array $option)
    {
        return DB::table('order')
            ->where('uid', $uid)
            ->whereIn('state',$option)
            ->count();
    }

}