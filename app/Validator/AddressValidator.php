<?php


namespace App\Validator;
use Swoft\Validator\Annotation\Mapping\IsInt;
use Swoft\Validator\Annotation\Mapping\IsString;
use Swoft\Validator\Annotation\Mapping\Mobile;
use Swoft\Validator\Annotation\Mapping\NotEmpty;
use Swoft\Validator\Annotation\Mapping\Validator;


/**
 * 参数验证器
 * Class ParamsValidator
 *
 * @Validator(name="addressValidator")
 */
class AddressValidator
{

    /**
     * @NotEmpty(message="手机号缺失")
     * @IsString(name="mobile",message="""手机号缺失")
     * @Mobile(message="手机号格式错误")
     * @var string
     */
    private $phone;

    /**
     * @NotEmpty(message="收货人缺失")
     * @IsString(name="consignee",message="收货人参数异常")
     * @var
     */
    private $name;

    /**
     * @NotEmpty(message="省区缺失")
     * @IsInt(message="省|市参数异常")
     * @var
     */
    private $province_id;


    /**
     * @NotEmpty(message="市|区缺失")
     * @IsInt(message="市|区参数异常")
     * @var
     */
    private $city_id;


    /**
     * @NotEmpty(message="区|县缺失")
     * @IsInt(message="区|县参数异常")
     * @var
     */
    private $country_id;

    /**
     * @IsInt(message="乡|镇参数异常")
     * @var
     */
    private $town_id=0;

    /**
     * @NotEmpty(message="详细地址缺失")
     * @IsString(message="参数异常")
     * @var
     */
    private $address;


    /**
     * @IsString(message="详细地址参数异常")
     * @var
     */
    private $area;

    /**
     * @IsInt(message="参数异常")
     * @var
     */
    private $default =0;

    /**
     * @IsInt(message="地址参数异常")
     * @var
     */
    private $address_id;
}