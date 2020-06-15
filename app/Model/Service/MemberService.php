<?php
namespace App\Model\Service;


use App\Common\Cache;
use App\Exception\ApiException;
use App\Listener\Event;
use App\Model\Dao\ActivityDao;
use App\Model\Dao\ActivityMemberDao;
use App\Model\Dao\ActivityProductDao;
use App\Model\Dao\GradeApiConfigDao;
use App\Model\Dao\GradeDao;
use App\Model\Dao\MemberDao;
use App\Model\Dao\CardRecordDao;
use App\Model\Dao\DotDao;
use App\Model\Dao\UserDao;
use App\Utils\Check;
use Firebase\JWT\JWT;
use Swoft\Bean\Annotation\Mapping\Bean;
use Swoft\Bean\Annotation\Mapping\Inject;
use Swoft\Bean\BeanFactory;
use Swoft\Db\DB;
use Swoft\Redis\Redis;

/**
 * 会员操作逻辑
 * Class MemberService
 *
 * @Bean(scope=Bean::PROTOTYPE)
 */
class MemberService
{

    /**
     * @Inject()
     * @var DotDao
     */
    private $dotDao;

    /**
     * @Inject()
     * @var MemberDao
     */
    private $memberDao;
    /**
     * @Inject()
     * @var ActivityMemberDao
     */
    private $activityMemberDao;


    /**
     * @Inject()
     * @var ActivityProductDao
     */
    private $activityProductDao;

    /**
     * @Inject()
     * @var GradeApiConfigDao
     */
    private $gradeApiConfigDao;


    /**
     * @Inject()
     * @var ActivityDao
     */
    private $activityDao;

    /**
     * @Inject()
     * @var GradeDao
     */
    private $gradeDao;



    /**
     * 网点客户登录
     * @param int $user_id
     * @param string $account_$password
     * @return  array
     * @throws ApiException
     */
    public  function login($params)
    {
        $str = $params['sResponseXml'];
        if (empty($str)) throw new ApiException('用户标识为空');
        //var_dump($str);
        //$str='<Response><Head><ResultType>Y</ResultType><CryptType>2</CryptType><StateType>1</StateType></Head><Body>/qigtuR2GX2aR5z+z329BFyxpv1AvK5ke9fMJBW7Emob9hG6xurMDiQW9AoTA2bgDS36z/8tRDYW8sFiT9Xlqj68BzRWBkDdDS36z/8tRDbFrV7oWp+zDejcxb753wQTynDAW7cIM5KRGs63Dqp5E8tY8XVDJCs/DLoUgMVTLHMFmQbzKrIlsMuNyF0ZxIkvZikJZmLqaTUZHNL7/Jh7jjXA85/vJ1S/sBcOxBICXU2x0bFuMuzx4sBRdPVlllP0dySMlkWK4xXwssPjQlw62FvpTRJq7rpVwCNmzd8Xbcl+ZPQmpxHgHUpRK5xhj3zGd99UzBLjZom/fl87UDuS22HJhZNsxxbBd3im31ofHcPfZ+kVqd+OQUF0lcybh21mSPiG84eQwfbkFXK2YAd1VN/TuU7TSJX616MBxgsgXT6NjbJ9NX/BCNcpWj3tlzzKzX4+JU1DSx5UbXWxECPpdTXz+HIy2a8NoclmQDjmsMiqKHEmETcGembNPDeTw1PlbeZq7MLF6S4FCPzFzry3Crr7b1HgDdzTKjI8cFiNeQDak8eG6wvgtOnCed6GiADR5g+O7r4Agk+Kc4M/K2GkMXOgaIE2Gu6nRhq/EI7fgoHh6mRbimcV6HNWEZzg5UwcNOnx08qdj35ffPx7nxQaDELWpL+YQyhTWRN8ZiZxlMoSImUyHau77ar+OptVbxj42kAENIZZPCpDMQtLddvEHFJ+PHnrbPF4lMHuleWtRpYXh6Y/I+tpAyiUaiNWyTIzl9SgUo/keyTY1NJjAJfU9y108ktRVAbGltC7jY6w4OTgR9rhM/tu/JWBox65TGW9HKBsgFzsC8FB6A4rt+jjteSRfTo2HonGV+yz7VfQhDo0YvS7xVORnGkByMuS1mAj14w5JtfJtnPW5ZdV0TC4QJgaIUBJNqOgop0yrujBSqyinTKu6MFKrKKdMq7owUqsmATe754t3exmJ3Mn2dk1MyaSA4gJZ0LcWDuGdoH7TwIGlV8wOfdlaSWMsV3tWc3+uDOLoqUVMxqJg6jDFY8fBYqCre/aS0LEIzwDdrUksButULlyG+erZU/cgx9FOPr0B5zoLSLkN4sMI4L1SSkbgOmvbdThvRbO0UVsKKTj8k2q/QErXCeM/wyaRvSsjX/cxtsi1+SKYB0UiWcKn0jqac6fxtrxGnpzVa6DztfsucmcJMhiDhpa/3ElYEhWWVFgdt2o+Wi3XMVpk41ZhKA0J2T+A7RgU3tJTNsPx8rlaLpjOb5vaIVbcjoNLRmXBcymHGRGyJXFOZzz8Ju4zbm3bbazRH1y8x+t8hbx39Z0+czfKGDg90xrGHDHr01QZLNUxh4t52sH5uU9cLJYWsJ/bbYBvc9Hct8Q/oVgKS9QC6ZtNUWJshzdaw==</Body></Response>';

        $member_code=$this->apiDecrypt(json_decode($str,true));

        //记录用户信息
        $member_data = $this->memberDao->getMemberCode($member_code);

        if (empty($member_data)){//如果没有记录过该用户就新增
            $member_id = $this->memberDao->addMember(['unique_code'=>$member_code,'created_at'=>time()]);
        }else{
            $member_id = $member_data['id'];
        }

        $secret_key = config('jwt.secret_key');
        $exp = intval(config('jwt.exp'));
        $type = config('jwt.type');

        //生产网点token
        $payLoad = [
            'member_id' =>$member_id,
            'iat'  => time(),
            'exp'  => time() + $exp
        ];
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $str = '';
        for($i = 0; $i < 20; $i++)
        {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
            $str .= $chars[mt_rand(0, strlen($chars) - 1)];
        }

        //生成加密token
        $token =JWT::encode($payLoad,$secret_key,$type).$str;

        //缓存授权码
        Redis::setex(config('jwt.REDIS_DATEBASE_PREFIX').':MEMBER:LOGIN:'.$token,$exp,json_encode(['member_id'=>$member_id,'unique_code'=>$member_code]));

        return ['member_token'=>$token];

    }

    /**
     * 处理请求返回的结果
     */
    public function apiDecrypt($str){
        if($str['resultType']!='Y'){
            //$this->errorPage('登录失败','/','提示','');
            throw new ApiException('登录失败');
        }

        //转义空格
        //$str=str_replace(' ','+',"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");
        $str=str_replace(' ','+',$str['body']);

        $data=$this->decrypt($str,config('CMB_KEY'));

        $datas = json_decode($data,true);


        if (empty($datas['data']['corpInfo']['uniqueUserID'])) throw new ApiException('获取用户信息失败');

        return $datas['data']['corpInfo']['uniqueUserID'];


    }


    /**解密
     * @param $text
     * @param $key
     */
    public function decrypt($text,$key)
    {
        $data =  openssl_decrypt ($text, 'des-ecb', $key);
        return $data;
    }

    /**
     * 会员端活动详情
     */
    public function getActivityProductList($params){

        if (empty($params['activity_id'])) throw new ApiException('活动ID错误');

        $token =$params['member_token'];
        $activity_id =$params['activity_id'];

        //获取登录信息
        //获取会员的登录信息
        $member_data = Redis::get(config('jwt.REDIS_DATEBASE_PREFIX').':MEMBER:LOGIN:'.$token);
        $member_data = json_decode($member_data,true);
        if (empty($member_data)) throw new ApiException('会员信息查询失败');

        $member_status = $this->activityMemberDao->getStatus($activity_id,$member_data['unique_code']);
        if ($member_status['state']==2&&$member_status['status']==0) throw new ApiException('用户已禁用');

        //验证领取权限
        return $this->checkActivity($activity_id,$member_data['unique_code']);

    }
    /**
     * 验证活动会员是否满足兑换条件
     * @param $activity_id
     * @param $product_id
     * @param int $grade_id
     */
    public function checkActivity($activity_id,$uniqueuserid,$grade_id=0){

        $activity_data =$this->activityDao->getOndByCode($activity_id);
        //查询活动下商品的的档次
        $getActivityProductByIdData=$this->activityProductDao->getActivityProductGrade($activity_id);

        if (empty($getActivityProductByIdData)) throw new ApiException('活动无商品');

        //白名单
        if ($activity_data['member_source']==1){

            //验证是否创建档次
            $gradeData = $this->gradeDao->getOndByCode($activity_id);
            if (empty($gradeData)) throw new ApiException('您没有领取机会了');

            //查询活动一共有几个档次
            $activityMemberGrade =$this->activityMemberDao->getActivityGrade($activity_id,$grade_id,$uniqueuserid);

            //if (empty($activityMemberGrade)) throw new ApiException('线上活动档次未创建');

            $grade_id_data = array_column($activityMemberGrade,'grade_id');

            $grade_status_str=[];


            foreach ($grade_id_data as $v){
                $grade_status_str[$v]=true;
            }

        }

        //接口
        if ($activity_data['member_source']==2){

            $grade_data =  $this->gradeDao->getOndByCode($activity_id);

            if (empty($grade_data)) throw new ApiException('线下活动档次不存在');

            $grade_id_data = array_column($grade_data,'id');
            $grade=[];
            foreach ($grade_id_data as $val){
                $gradeApiData = $this->gradeApiConfigDao->getOneGradeById($val);

                $where_str='?uniqueuserid='.$uniqueuserid;
                $url='';
                foreach ($gradeApiData as $key=>$v){
                    $url=$v['url'];
                    $where_str=$where_str.$v['api_where'].'='.$v['api_val'].'$';
                }

                $where_str = rtrim($where_str, '&');
                $result =post_curl_func($url,$where_str);
                if ($result){
                    $grade[]=$val ;
                }
            }

            $grade_status_str=[];
            foreach ($grade_id_data as $v){
                $grade_status_str[$v]=true;
            }
        }

        return $grade_status_str;
    }


    /**
     * 会员端确认订单
     */
    public function memberConfirmOrderInfo($params){

        if (empty($params['activity_id'])) throw new ApiException('活动ID错误');
        if (empty($params['product_id'])) throw new ApiException('商品ID错误');

        $data = $this->activityDao->getOndByCode($params['activity_id']);
        //线上
        if ($data['activity_type']==1){
            return $this->activityProductDao->getProductActivitById($params['activity_id'],$params['product_id']);
        }else{//线下
            return $this->activityProductDao->getProductActivitByIdxia($params['activity_id'],$params['product_id']);
        }

    }

}