<?php
/**
 * description UserValidator.php
 * Created by PhpStorm.
 * User: zengqb
 * DateTime: 2019/12/9 18:03
 */

namespace App\Validator;


use Swoft\Validator\Annotation\Mapping\AlphaDash;
use Swoft\Validator\Annotation\Mapping\IsInt;
use Swoft\Validator\Annotation\Mapping\IsString;
use Swoft\Validator\Annotation\Mapping\Length;
use Swoft\Validator\Annotation\Mapping\Min;
use Swoft\Validator\Annotation\Mapping\Mobile;
use Swoft\Validator\Annotation\Mapping\NotEmpty;
use Swoft\Validator\Annotation\Mapping\Pattern;
use Swoft\Validator\Annotation\Mapping\Validator;

/**
 * 用户表验证器
 * Class UserValidator
 *
 * @Validator(name="userValidator")
 */
class UserValidator
{

    /**
     * @NotEmpty(message="手机号缺失")
     * @IsString(message="手机格式错误")
     * @Mobile(message="手机号格式错误")
     * @var string
     */
    protected $mobile;

    /**
     * @IsString()
     * @AlphaDash(message="密码必须是数字，字母，短横，下划线组合")
     * @Length(min=6,max=15,message="密码长度在6~15之间")
     * @var string
     */
    protected $password;

    /**
     * @NotEmpty(message="福卡号缺失")
     * @IsString(name="serialNumber")
     * @var
     */
    protected $card_sn;


    /**
     * @NotEmpty(message="验证码缺失")
     * @IsString()
     * @Pattern(regex="/^\d*$/",message="验证码格式应为4位数字")
     * @Length(min=4,max=4,message="验证码格式应为4位数字")
     * @var
     */
    protected $code;


    /**
     * @NotEmpty(message="参数缺失")
     * @IsInt(message="参数格式异常")
     * @Min(value=1,message="参数格式异常")
     * @var
     */
    protected $transfer_point;

    /**
     * @NotEmpty(message="参数不合法")
     * @IsString(message="参数不合法")
     * @var
     */
    protected $accessToken;
}