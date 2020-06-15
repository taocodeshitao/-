<?php declare(strict_types=1);

namespace App\Validator;

use Swoft\Validator\Annotation\Mapping\Enum;
use Swoft\Validator\Annotation\Mapping\IsInt;
use Swoft\Validator\Annotation\Mapping\IsString;
use Swoft\Validator\Annotation\Mapping\Mobile;
use Swoft\Validator\Annotation\Mapping\NotEmpty;
use Swoft\Validator\Annotation\Mapping\Validator;

/**
 * Class OrderValidator
 * @package App\Validator
 *
 * @Validator(name="orderValidator")
 */
class OrderValidator
{

    /**
     * 收货信息ID
     * @IsInt()
     * @var int
     */
    protected $address_id=0;

    /**
     * 手机号码
     * @NotEmpty()
     * @IsString()
     * @Mobile()
     * @var string
     */
    protected $mobile;

    /**
     * 订单总额（积分）
     * @NotEmpty()
     * @IsInt()
     * @var int
     */
    protected $total_price;

    /**
     * sku列表
     * @NotEmpty()
     * @IsString()
     * @var string
     */
    protected $sku_list;


    /**
     * 兑换来源
     * @NotEmpty()
     * @IsInt(message="系统繁忙")
     * @Enum(values={1,2},message="系统繁忙")
     * @var
     */
    protected $source;

    /**
     * 订单备注
     * @IsString(message="系统繁忙")
     * @var string
     */
    protected $order_list;


    /**
     * 订单编号
     * @NotEmpty()
     * @IsString(message="系统繁忙")
     * @var string
     */
    protected $order_sn;
    /**
     * 订单ID
     * @NotEmpty()
     * @IsInt(message="缺失订单ID")
     * @var
     */
    protected $order_id;

    /**
     * 支付标识
     * @IsInt(message="系统繁忙")
     * @var
     */
    protected $pay_sign=1;


    /**
     * 物流单号
     * @NotEmpty()
     * @IsString(message="系统繁忙")
     * @var string
     */
    protected $express_num;

    /**
     * 快递名称
     * @NotEmpty()
     * @IsString(message="系统繁忙")
     * @var string
     */
    protected $express_name;
}