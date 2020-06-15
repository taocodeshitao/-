<?php
/**
 * description TestController.php
 * Created by PhpStorm.
 * User: zengqb
 * DateTime: 2019/12/23 10:58
 */

namespace App\Http\Controller\Index;


use App\Common\Cache;
use EasyWeChat\Factory;
use Swoft\Co;
use Swoft\Db\DB;
use Swoft\Http\Message\ContentType;
use Swoft\Http\Server\Annotation\Mapping\Controller;
use Swoft\Http\Server\Annotation\Mapping\RequestMapping;
use Swoft\Redis\Redis;
use Swoft\Stdlib\Helper\JsonHelper;
use Swoft\Task\Task;

/**
 * Class TestController
 *
 * @Controller(prefix="test")
 */
class TestController
{


    /**
     * @RequestMapping(route="test1")
     * @return mixed
     */
    public  function test()
    {
        echo "1";

    }


    public  function getActivity()
    {
        $data = DB::table('activity')
            ->leftJoin('attachment','activity.cover','=','attachment.id')
            ->select('activity.id as activity_id','activity.title','attachment.url as image','activity.begin_time','activity.end_time')
            ->where('model')
            ->get()
            ->toArray();


        return $data;
    }

    public  function getProduct()
    {
        $a ='activity_wares';
        $b ='commodity_wares';
        $c ='commodity';

        $data = DB::table($a)
            ->join($b,"{$a}.wares_id","{$b}.id")
            ->join($c,"{$b}.source_id","{$c}.source_id")
            ->where("{$a}.activity_id",1)
            ->select("{$b}.code","{$b}.title","{$b}.typeid","{$b}.integral as old_peas","{$c}.url as image","{$a}.integral as peas")
            ->orderBy("{$a}.sort",'desc')
            ->limit(10)
            ->get()
            ->toArray();

        return $data;
    }


    /**
     * @RequestMapping(route="test2")
     * @return mixed
     */
    public  function test2()
    {

        $a ='commodity_wares';
        $b ='commodity';

        $data = DB::table($a)
            ->leftJoin($b,"{$a}.source_id","{$b}.source_id")
            ->select("{$a}.id","{$a}.code","{$a}.itemize_id","{$a}.source_id","{$a}.title","{$a}.status","{$a}.typeid","{$a}.market_price","{$a}.settlement","{$a}.integral","{$a}.app_introduce","{$b}.cover","{$b}.images")
            ->orderBy('id','asc')
            ->get()
            ->toArray();
         foreach ($data as $k=>$v)
         {
             Redis::hSet(Cache::PRODUCT_DETAILS,strval($v['code']),json_encode($data[$k]));
         }
    }



    /**
     * @RequestMapping(route="test3")
     * @return mixed
     */
    public  function test3()
    {
        $where[] = ['activity.end_time','>',time()];
        $where[] = ['activity.model','=','LIMITED_TIME'];
        $data = DB::table('activity')
            ->leftJoin('attachment','activity.cover','=','attachment.id')
            ->where($where)
            ->select('activity.id','activity.sign','activity.id as status','activity.model','activity.title','attachment.url as image','activity.begin_time','activity.end_time')
            ->get()
            ->toArray();

        foreach ($data as $k=>$v)
        {
            Redis::hSet(Cache::ACTIVITY_LIMIT_BASE,$v['sign'],json_encode($v));
        }

    }




    /**
     * @RequestMapping(route="test4")
     * @return mixed
     */
    public  function test4()
    {

        $a='activity';
        $b ='activity_wares';
        $c ='commodity_wares';

        $data = DB::table($a)
            ->join($b,"{$a}.id","{$b}.activity_id")
            ->join($c,"{$b}.wares_id","{$c}.id")
            ->where("{$a}.model","LIMITED_TIME")
            ->select("{$a}.sign","{$c}.code","{$b}.sort","{$b}.integral","{$b}.stock","{$b}.stock_eable","{$b}.limit")
            ->get()
            ->toArray();

        foreach ($data as $k=>$v)
        {
            $key = sprintf(Cache::ACTIVITY_LIMIT_PRODUCT,$v['sign']);

            Redis::hset($key,strval($v['code']),json_encode($v));


            $key1 = sprintf(Cache::ACTIVITY_LIMIT_SORT,$v['sign']);
            Redis::zAdd($key1,[$v['code']=>$v['sort']]);

            $key2 = sprintf(Cache::ACTIVITY_LIMIT_INVENTORY,$v['sign'],$v['code']);

            for ($i=1;$i<=$v['stock_eable'];$i++)
            {
                Redis::rPush($key2,1);
            }

        }
    }


    /**
     * @RequestMapping(route="test6")
     * @return mixed
     */
    public  function test6()
    {
        Redis::set('operation:token',"MDAwMDAwMDAwMILgpLB-d3zas3mnYIXcoWCDfNKfh4680bOli5qBs5msg5isbIuHgc2_ia9gkbmEm4SMyp6Se6zQssuoq4F9e6qBuqSyfYd4lK95p6mHzINkhaKzaYehq5iy2m2rgaOLqIPgqK5-h4CVtZ_Np4W2f6eEiaNy");
    }

    /**
     * @RequestMapping(route="test8")
     * @return mixed
     */
    public  function getToken()
    {
       $data['app_id'] = '3000100043';

       $data['app_key'] = '409c9ef44a3827b7e2ca24add57bd1e2';

       $data['tamptimes'] =date('Y-m-d H:i:s');

       $data['sign'] = strtoupper(md5('app_id='.$data['app_id'].':'.'app_key='.$data['app_key'].':'.'tamptimes='.$data['tamptimes']));
        post_curl_func('t.blhapi.li91.com/index/getToken',JsonHelper::encode());
    }

    public  function test7()
    {

        $config = [
            // 必要配置
            'app_id'             => 'wx3d58e6bcd1544bf4',
            'mch_id'             => '1311855001',
            'key'                => '14e1b600b1fd579f47433b88e8d85291',   // API 密钥

            'notify_url'         => '默认的订单回调地址',     // 你也可以在下单时单独设置来想覆盖它
        ];

        $app = Factory::payment($config);

    }


    /**
     * @RequestMapping("test9")
     */
    public  function test8()
    {
        $pre = '19923324483';

        $password = '123456';

        $data = password_hash(encryptPassword($password,$pre),PASSWORD_DEFAULT);

       return context()->getResponse()->withContent($data);

    }

    /**
     * @RequestMapping("test10")
     */
    public  function test10()
    {
        Task::async('asyn','addHistory',[1,3,3]);

    }


    /**
     * @RequestMapping(route="test11")
     * @return mixed
     */
    public  function test11()
    {

        $data = DB::table('subgroup')->get(['id','pid','title'])->toArray();

        foreach ($data as $k=>$v)
        {

            Redis::hSet(Cache::SYSTEM_CATEGORY,strval($v['id']),json_encode($v));
        }

    }

    /**
     * @RequestMapping(route="test12")
     * @return mixed
     */
    public  function test12()
    {

        print_r(config('jwt.type'));
    }

}