<?php declare(strict_types=1);

namespace App\Model\Dao;


use Swoft\Bean\Annotation\Mapping\Bean;
use Swoft\Db\DB;

/**
 * 支付数据操作
 * @Bean()
 */
class PaymentDao
{


    /**
     * H5 支付
     */
    const PAY_TRADE_TYPE_MWEB = 'MWEB';

    /**
     * JSAPI支付
     */
    const PAY_TRADE_TYPE_JSAPI = 'JSAPI';


    /**
     * 添加支付记录
     * @param $data
     * @return int
     */
    public  function addData(array $data)
    {

       return DB::table('payment')->insertGetId($data);
    }


    /**
     * 根据订单查询
     * @param string $order_sn
     * @return null|object|\Swoft\Db\Eloquent\Model|static
     */
    public  function findBySn(string  $order_sn)
    {

        return DB::table('payment')->where('merge_sn',$order_sn)->first();
    }

    public  function findById(string  $payment_id)
    {

        return DB::table('payment')->where('id',$payment_id)->first();
    }

    public  function getTotalFeeSum(array $payment_ids)
    {
        $where['state'] = 1;

        return DB::table('payment')->where($where)->whereIn('id',$payment_ids)->sum('total_fee');
    }


    /**
     * 更新订单信息
     * @param int $payment_id
     * @param array $data
     * @return int
     */
    public  function updateById(int $payment_id,array $data)
    {

        return DB::table('payment')->where('id',$payment_id)->update($data);
    }

    /**
     * 支付类型
     * @param int $pay_sign
     * @return mixed
     */
    public  function getTradeType(int $pay_sign=1)
    {
        $data = [
            1 => self::PAY_TRADE_TYPE_MWEB,
            2 => self::PAY_TRADE_TYPE_JSAPI
        ];
        return $data[$pay_sign];
    }
}