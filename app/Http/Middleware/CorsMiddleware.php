<?php


namespace App\Http\Middleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Swoft\Bean\Annotation\Mapping\Bean;
use Swoft\Context\Context;
use Swoft\Http\Server\Contract\MiddlewareInterface;

/**
 *  跨域设置
 * @Bean()
*/
class CorsMiddleware implements MiddlewareInterface
{
    /**
    * Process an incoming server request.
    * @param ServerRequestInterface $request
    * @param RequestHandlerInterface $handler
    * @return ResponseInterface
    * @inheritdoc
    */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ('OPTIONS' === $request->getMethod())
        {
             $response =Context::mustGet()->getResponse();

             return $this->configResponse($response);
        }


          $response = $handler->handle($request);

          return $this->configResponse($response);
    }

    private function configResponse(ResponseInterface $response)
    {
        return $response
                ->withHeader('Access-Control-Allow-Origin', 'http://testceshi2.li91.com')
                ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With,token,Content-Type, Accept, Origin, Authorization')
                ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
    }
}

