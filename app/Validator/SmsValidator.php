<?php declare(strict_types=1);

namespace App\Validator;

use App\Exception\ApiException;
use Swoft\Validator\Annotation\Mapping\Validator;
use Swoft\Validator\Contract\ValidatorInterface;
use Swoft\Validator\Exception\ValidatorException;

/**
 * 短信自定义验证器
 * Class SmsValidator
 *
 * @Validator()
 */
class SmsValidator implements ValidatorInterface
{
    /**
     * @param array $data
     * @param array $params
     *
     * @return array
     * @throws ValidatorException
     */
    public function validate(array $data, array $params): array
    {

        $mobile = trim($data['mobile']);

        $type = $data['type'];

        if ($mobile === null) throw new ApiException('手机号不能为空');

        if($type===null ||!is_numeric($type))throw new ApiException('系统繁忙');

        $exp = '/^1[3|4|5|6|7|8|9][0-9]{9}$/';

        if(!preg_match($exp,$mobile)) throw new ApiException('手机格式错误');

        return $data;
    }
}
