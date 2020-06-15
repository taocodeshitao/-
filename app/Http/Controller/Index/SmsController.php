<?php

namespace App\Http\Controller\Index;
use App\Model\Service\SmsService;
use App\Rpc\Lib\RpcSmsInterface;
use App\Common\Message;
use App\Validator\SmsValidator;
use Swoft\Bean\Annotation\Mapping\Inject;
use Swoft\Bean\BeanFactory;
use Swoft\Http\Message\Request;
use Swoft\Http\Server\Annotation\Mapping\Controller;
use Swoft\Http\Server\Annotation\Mapping\RequestMapping;
use Swoft\Http\Server\Annotation\Mapping\RequestMethod;
use Swoft\Rpc\Client\Annotation\Mapping\Reference;
use Swoft\Task\Task;


/**
 * 短信控制器
 * @Controller(prefix="/api")
 */
class SmsController
{

    /**
     * @Reference(pool="sms.pool")
     * @var RpcSmsInterface
     */
    private $smsInterface;

    /**
     * 获取验证码
     * @RequestMapping("getCode",method={RequestMethod::POST})
     * @param Request $request
     * @return string
     */
    public  function getCode(Request $request)
    {
        //获取访问参数
        $data = $request->post();

        //验证参数格式是否正确
        \validate($data,SmsValidator::class,[],[SmsValidator::class]);

        /** @var SmsService $smsService */
        $smsService =  BeanFactory::getBean(SmsService::class);

        //获取短信内容
        $content = $smsService->getSmsContent($data['mobile'],$data['type']);

        //发送短信
        $result = $this->smsInterface->send($data['mobile'],$content);

        if($result['code']!=200){

            return  Message::error('发送失败');
        }

        return  Message::success($result);
    }

    /**
     * 超出限流提示信息
     * @return array
     */
    public function fallback(){

        return ['您被限流了'];
    }
}