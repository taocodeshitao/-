<?php
/**
 * description AuthMiddleware.php
 * Created by PhpStorm.
 * User: zengqb
 * DateTime: 2019/11/26 11:22
 */

namespace App\Http\Middleware;


use App\Common\Cache;
use App\Common\StatusEnum;
use App\Exception\ApiException;
use App\Model\Dao\UserDao;
use Firebase\JWT\JWT;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Swoft\Bean\Annotation\Mapping\Bean;
use Swoft\Bean\BeanFactory;
use Swoft\Http\Server\Contract\MiddlewareInterface;
use Swoft\Redis\Redis;

/**
 * 权限中间件
 * @Bean()
 */
class AuthMiddleware implements MiddlewareInterface
{

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     * @throws ApiException
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // 排除登陆、注册和短信接口，登陆接口不需要验证jwt
        $white_path = ['/login','/p_login','/register','/getCode','/updatePwd'];

        $path = $request->getUri()->getPath();

        if (in_array($path,$white_path))
        {
            $response = $handler->handle($request);

            return $response;
        }

        $token = $request->getHeaderLine("token");

        $secret_key = config('jwt.secret_key');

        $type = config('jwt.type');

        try {

            $auth = JWT::decode($token,$secret_key,[$type]);

            $request->user_id = $auth->user_id;

        } catch (\Exception $e) {

            throw new ApiException('登录已过期',StatusEnum::LoginFailCode);
        }

        //判断用户是否已禁用
        /** @var UserDao $userDao */
        $userDao =  BeanFactory::getBean(UserDao::class);

        $userInfos = $userDao->findById($request->user_id);

        if($userInfos && $userInfos['state']==0) throw new ApiException('该账户不存在或已禁用');

        $request->integral = $userInfos['integral'];

        $key =sprintf(Cache::AUTH_TOKEN,$request->user_id);

        $old_token = Redis::get($key);

        if($old_token!=$token) throw new ApiException('登录已过期',StatusEnum::LoginFailCode);

        $response = $handler->handle($request);

        return $response;
    }
}