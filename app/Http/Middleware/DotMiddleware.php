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
use App\Model\Dao\DotDao;
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
 * 客户经理登录权限中间件
 * @Bean()
 */
class DotMiddleware implements MiddlewareInterface
{

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     * @throws ApiException
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        //验证网站是否关闭
        $config_data= Redis::hget(config('jwt.REDIS_DATEBASE_PREFIX').':SYSTEM:WEB:CONFIG','wap_site_open');

        if($config_data==0)
            throw new ApiException('网站已关闭',1001);

        $token = $request->getParsedBody()['dot_token'];

        $dot_data = Redis::get(config('jwt.REDIS_DATEBASE_PREFIX').':DOT:LOGIN:'.$token);

        if (empty($dot_data)) throw new ApiException('登录已过期',1002);

        $dot_data = json_decode($dot_data,true);

        //判断用户是否已禁用
        /** @var DotDao $userDao */
        $userDao =  BeanFactory::getBean(DotDao::class);

        $userInfos = $userDao->getAccountById($dot_data['id']);

        if(empty($userInfos)) throw new ApiException('该账户不存在或已禁用');

        $response = $handler->handle($request);

        return $response;
    }
}