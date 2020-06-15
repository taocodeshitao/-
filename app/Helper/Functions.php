<?php


    if (!function_exists('randNum'))
    {
        /**
         * 获取随机数
         * @param int $slen 长度
         * @return String
         */
        function randNum(int $slen=4):String
        {
            $chars = '1234567890';

            $string ='';

            for($i=0;$i<$slen;$i++)
            {
                $rand = rand(0,strlen($chars)-1);
                $string.=substr($chars,$rand,1);
            };

            return $string;
        }
    }



    if (!function_exists('getFileKey'))
    {
        /**
         * 生成加密串
         * @param $str
         * @return string
         */
        function getFileKey(String $str)
        {
            return substr(md5(makeRandomString(). $str . time() . rand(0,9999)),8,16);
        }
    }


    if (!function_exists('makeRandomString'))
    {
        /**
         * 生成随机字符串
         * @param int $length
         * @return null|string
         */
        function  makeRandomString(int $length=1)
        {
            $str = null;

            $strPol = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz';

            $max = strlen($strPol)-1;

            for($i=0;$i<$length;$i++)
            {
                //生成介于min和max两个数之间的一个随机整数
                $str.=$strPol[rand(0,$max)];
            }

            return $str;
        }
    }


    if (!function_exists('getClientIp')) {
        /**
         * 获取客户端的真实IP
         * @return string
         */
        function getClientIp()
        {
            return context()->getRequest()->getServerParams()['remote_addr'];
        }
    }

    if(!function_exists('getOrderSn'))
    {

        /**
         * 生成订单号
         * @return string
         */
        function  getOrderSn()
        {
            @date_default_timezone_set("PRC");

            $order_main = date('YmdHis') . rand(10000000,99999999);

            $order_len = strlen($order_main);

            $order_sum = 0;

            for($i=0; $i<$order_len; $i++)
            {
                $order_sum += (int)(substr($order_main,$i,1));
            }

            return $order_main . str_pad((100 - $order_sum % 100) % 100,2,'0',STR_PAD_LEFT);
        }


    if(!function_exists('curlFunc')){

             function curlFunc($url,$param)
             {

                $ch = curl_init();

                // 选项参数配置
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

                // https 请求
                if (strlen($url) > 5 && strtolower(substr($url, 0, 5)) == "https")
                {
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                }

                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($param));
                curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));

                // 抓取URL并把它传递给浏览器
                $output = curl_exec($ch);

                if (curl_errno($ch))
                {
                    throw new \Exception(curl_error($ch), 0);
                }
                else
                {
                    $httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    if (200 !== $httpStatusCode) throw new \Exception($output, $httpStatusCode);
                }

                curl_close($ch);

                return json_decode($output, true);
            }
        }
    }

    if(!function_exists('get_curl_func'))
    {

         function get_curl_func($url,$data)
        {

            $ch = curl_init();

            $url = $url.'?'.http_build_query($data);

            // 选项参数配置
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, false);

            // https 请求
            if (strlen($url) > 5 && strtolower(substr($url, 0, 5)) == "https")
            {
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            }

            $result = curl_exec($ch); //执行CURL

            curl_close($ch);

            return json_decode($result,true);
        }
    }

    if(!function_exists('post_curl_func')) {

        function post_curl_func($url, $data)
        {

            $ch = curl_init();

            // 选项参数配置
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

            // https 请求
            if (strlen($url) > 5 && strtolower(substr($url, 0, 5)) == "https") {
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            }

            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Content-Length: ' . strlen($data)));

            // 抓取URL并把它传递给浏览器
            $output = curl_exec($ch);

            if (curl_errno($ch)) {
                throw new \Exception(curl_error($ch), 0);
            } else {
                $httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                if (200 !== $httpStatusCode) throw new \Exception($output, $httpStatusCode);
            }

            curl_close($ch);

            return json_decode($output, true);
        }
}
     if (!function_exists('encryptPassword'))
     {
        function encryptPassword($str, $pre = '')
        {
            return md5($pre . 'WELFARE~.$$xxCC**`I1l0oOs5S~`.ERASDjkA^&*,.' . $str . '@x^#^$#@@&~!@~5+-/!@.');
        }
     }

    if (!function_exists('get_curl'))
    {
        function get_curl($url)
        {
            $oCurl = curl_init();

            if (stripos($url, "https://") !== FALSE) {
                curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, FALSE);
                curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, FALSE);
                curl_setopt($oCurl, CURLOPT_SSLVERSION, 1); //CURL_SSLVERSION_TLSv1
            }
            curl_setopt($oCurl, CURLOPT_URL, $url);
            curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1);
            $output = curl_exec($oCurl);
            $aStatus = curl_getinfo($oCurl);

            curl_close($oCurl);

            if (intval($aStatus["http_code"]) !== 200) return false;

            return json_decode($output, true);
        }

    }


    if(!function_exists('check_phone'))
    {
        function check_phone($phone)
        {
            $check = '/^(1(([3456789][0-9])|(47)))\d{8}$/';

            if (preg_match($check, $phone))
            {
                return true;
            } else {
                return false;
            }}

    }


    if(!function_exists('_createAccessToken'))
    {
        function _createAccessToken($sign)
        {

          return   hash_hmac('sha256', $sign, config('secret'));
        }
    }

