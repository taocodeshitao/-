<?php declare(strict_types=1);


namespace App\Exception\Handler;

use App\Common\StatusEnum;
use App\Exception\ApiException;
use App\Common\Message;
use Swoft\Error\Annotation\Mapping\ExceptionHandler;
use Swoft\Http\Message\Response;
use Swoft\Http\Server\Exception\Handler\AbstractHttpErrorHandler;
use Swoft\Validator\Exception\ValidatorException;
use Throwable;

/**
 * 接口异常处理类
 * Class ApiExceptionHandler
 * @ExceptionHandler({ApiException::class,ValidatorException::class})
 */
class ApiExceptionHandler extends AbstractHttpErrorHandler
{
    /**
     * @param Throwable $except
     * @param Response  $response
     *
     * @return Response
     */
    public function handle(Throwable $except, Response $response): Response
    {
        $error_code = $except->getCode() ? $except->getCode():StatusEnum::FailCode;

        $data = Message::error($except->getMessage(),$error_code);

        return $response->withStatus(200)->withData($data);
    }
}
