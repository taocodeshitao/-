<?php declare(strict_types=1);

namespace App\Validator;

use Swoft\Validator\Annotation\Mapping\IsInt;
use Swoft\Validator\Annotation\Mapping\NotEmpty;
use Swoft\Validator\Annotation\Mapping\Validator;

/**
 * Class OrderValidator
 * @package App\Validator
 *
 * @Validator(name="noticeValidator")
 */
class NoticeValidator
{

    /**
     * 通知类型
     * @NotEmpty()
     * @IsInt(message="缺失通知类型")
     * @var
     */
    protected $type;

}