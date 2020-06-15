<?php
/**
 * description ParamsValidator.php
 * Created by PhpStorm.
 * User: zengqb
 * DateTime: 2019/12/18 16:46
 */

namespace App\Validator;
use Swoft\Validator\Annotation\Mapping\Enum;
use Swoft\Validator\Annotation\Mapping\IsInt;
use Swoft\Validator\Annotation\Mapping\IsString;
use Swoft\Validator\Annotation\Mapping\Min;
use Swoft\Validator\Annotation\Mapping\NotEmpty;
use Swoft\Validator\Annotation\Mapping\Validator;


/**
 * 参数验证器
 * Class ParamsValidator
 *
 * @Validator(name="commonValidator")
 */
class CommonValidator
{

    /**
     * @NotEmpty(message="兑换码缺失")
     * @IsString(message="兑换码异常")
     * @var
     */
    protected $serialNumber;

    /**
     * @NotEmpty(message="商城标识缺失")
     * @IsString(message="参数异常")
     * @var
     */
    protected $mark;

    /**
     * @NotEmpty(message="文章id缺失")
     * @IsInt(message="参数异常")
     * @var
     */
    protected $article_id;


    /**
     * @NotEmpty(message="参数缺失")
     * @IsString(message="商品sku异常")
     * @var
     */
    protected $sku;


    /**
     * @NotEmpty(message="收藏参数缺失")
     * @IsInt(message="收藏参数异常")
     * @var
     */
    protected $collect_id;


    /**
     * @NotEmpty(message="参数缺失")
     * @IsInt(message="系统繁忙")
     * @Enum(values={1,2},message="系统繁忙")
     * @var
     */
    protected $arrondi_id;

    /**
     * @NotEmpty(message="参数缺失")
     * @IsInt(message="系统繁忙")
     * @Min(value=3,message="参数异常")
     * @var
     */
    protected $c_arrondy_id;


    /**
     * 专题id
     * @NotEmpty(message="参数缺失")
     * @IsInt(message="系统繁忙")
     * @var
     */
    protected $subject_id;

    /**
     * @IsString(message="活动编号异常")
     * @var
     */
    protected $code;


    /**
     * @NotEmpty(message="商品数量异常")
     * @IsInt(message="商品数量异常")
     * @Min(value=1,message="商品数量异常")
     * @var
     */
    protected $num;


    /**
     * @NotEmpty(message="参数缺失")
     * @IsInt()
     * @Min(value=1,message="参数异常")
     * @var
     */
    protected $pageIndex;

    /**
     * 消费类型
     * @IsInt(message="系统繁忙")
     * @Enum(values={0,1,2},message="系统繁忙")
     * @var
     */
    protected $type;


    /**
     * @NotEmpty()
     * @IsString(message="授权码异常")
     * @var
     */
    protected $auth_code;

    /**
     * 支付凭证
     * @IsInt(message="系统繁忙")
     * @var
     */
    protected $payment_id;


    /**
     * 时间排序
     * @IsInt(message="系统繁忙")
     * @Enum(values={0,1,2},message="系统繁忙")
     * @var
     */
    protected $time =0;


    /**
     * 销量排序
     * @IsInt(message="系统繁忙")
     * @Enum(values={0,1,2},message="系统繁忙")
     * @var
     */
    protected $sales =0;

    /**
     * 价格排序
     * @IsInt(message="系统繁忙")
     * @Enum(values={0,1,2},message="系统繁忙")
     * @var
     */
    protected $price =0;

    /**
     * 分类
     * @IsInt(message="系统繁忙")
     * @var
     */
    protected $category_id =0;


    /**
     * 分类
     * @IsInt(message="系统繁忙")
     * @var
     */
    protected $price_start=0;


    /**
     * 分类
     * @IsInt(message="系统繁忙")
     * @var
     */
    protected $price_end=0;
}