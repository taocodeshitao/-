<?php declare(strict_types=1);

namespace App\Http\Controller\Index;


use App\Model\Service\AccountService;
use App\Common\Message;
use Swoft\Bean\BeanFactory;
use Swoft\Http\Message\Request;
use Swoft\Http\Server\Annotation\Mapping\Controller;
use Swoft\Http\Server\Annotation\Mapping\RequestMapping;
use Swoft\Http\Server\Annotation\Mapping\RequestMethod;
use Swoft\Task\Task;
use Swoft\Validator\Annotation\Mapping\Validate;

/**
 * 账号控制器
 * Class AccountController
 *
 * @Controller(prefix="/api")
 */
class AccountController
{

    /**
     * 账号登录
     * @RequestMapping(route="login",method={RequestMethod::POST})
     * @Validate(validator="userValidator",fields={"mobile","password"})
     * @param Request $request
     * @return string
     */
    public  function login(Request $request)
    {
        //获取并验证请求数据
        $params = $request->getParsedBody();

        /** @var AccountService $accountService */
        $accountService = BeanFactory::getBean(AccountService::class);
        //登录验证
        $user_id = $accountService->doLogin($params['mobile'],$params['password']);

        //生成验证token
        $data = $accountService->getToken($user_id);

        return Message::success($data,'登录成功');
    }

    /**
     * 手机登录
     * @RequestMapping(route="p_login",method={RequestMethod::POST})
     * @Validate(validator="userValidator",fields={"mobile","code"})
     * @param Request $request
     * @return string
     */
    public  function p_login(Request $request)
    {

        //获取并验证请求数据
        $params = $request->getParsedBody();

        /** @var AccountService $accountService */
        $accountService = BeanFactory::getBean(AccountService::class);
        //登录验证
        $user_id = $accountService->doplogin($params);

        //生成验证token
        $data = $accountService->getToken($user_id);

        return Message::success($data,'登录成功');
    }

    /**
     * 用户注册
     * @RequestMapping(route="register",method=RequestMethod::POST)
     * @Validate(validator="userValidator",fields={"mobile","password","code","accessToken"})
     * @param Request $request
     * @return string
     */
    public  function register(Request $request)
    {
        //获取请求数据
        $params = $request->getParsedBody();

        /** @var AccountService $accountService */
        $accountService = BeanFactory::getBean(AccountService::class);

        //注册用户业务逻辑
        $user_id = $accountService->doRegister($params);

        //异步充值福卡
        Task::async('asyn','recharge',[$params['serialNumber'],$user_id]);

        //获取登录授权码
        $data =  $accountService->getToken($user_id);

        return Message::success($data,'恭喜您,注册成功');
    }


}