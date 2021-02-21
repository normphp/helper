<?php
/**
 * Created by PhpStorm.
 * User: pizepei
 * Date: 2019/07/15
 * Time: 16:24
 * @title Helper字符串来
 */
declare(strict_types=1);
namespace normphp\helper;


class Str
{
    /**
     * 判断大小写
     * @param $str 需要判断的字符串
     */
    public function checkcase(string $str):bool
    {
        if (preg_match('/^[a-z]+$/', $str)) {
            //echo '小写字母';
            return false;
        } elseif (preg_match('/^[A-Z]+$/', $str)) {
            //echo '大写字母';
            return true;
        }
    }
    /**
     * 判断大小写
     * @param $str 需要判断的字符串(首字母)
     */
    public function checkcaseOrd(string $str):bool
    {
        $str = ord($str[0]);
        if($str>64&&$str<91){
            # 大写字母
            return true;
        }
        if($str>96 && $str<123){
            # 小写字母
            return false;
        }
    }
    /**
     * @Author 皮泽培
     * @Created 2020/5/23 10:25
     * @title  返回空字符串方法
     * @return string
     */
    public function returnEmpty(){return '';}
    /**
     * 获取随机数字（加入了时间参数但是性能一般）
     * @param $length 长度
     * @param $one 干扰
     */
    public  function int_rand(int $length,string $one=''):int
    {
        $str = $this->random_pseudo_bytes(32,10,$one);
        $strlen = strlen($str)-1;
        $results = '';
        for($i=1;$i<=$length;$i++){
            $results  .= ((($int = $str[mt_rand(0,$strlen)])==='0') && $results=== '')?$this->returnEmpty($i--):$int;
        }
        return (int)$results;
    }

    /**
     * @Author 皮泽培
     * @Created 2020/5/23 10:29
     * @param string $function
     * @param string $one
     * @param int $amount
     * @title  测试统计随机数出现频率 4268
     * @return array
     * @throws \Exception
     */
    public function statistics_rand(string $function,string $one='',$amount=1000):array
    {
        for ($i=1;$i<$amount;$i++){
            $int = Helper()->str()->$function(1,$one);
            $data[$int] = isset($data[$int])?(++$data[$int]):0;
        }
        return $data;
    }
    /**
     * 获取随机字符串(加入了时间参数但是性能一般)
     * @param $length 长度
     * @param $one 干扰
     * @throws \Exception
     */
    public  function str_rand(int $length,string $one='',$strtoupper=false):string
    {
        $str = $this->random_pseudo_bytes(32,16,$one);
        $strlen = strlen($str)-1;
        $results = '';
        for($i=1;$i<=$length;$i++){
            $results  .= $str[mt_rand(0,$strlen)];
        }
        if ($strtoupper){
            $results = strtoupper($results);
        }
        return $results;
    }

    /**
     * 随机
     * @param int    $length
     * @param int    $tobase
     * @param string $one
     * @return string
     * @throws \Exception
     */
    public  function random_pseudo_bytes(int$length=32,int$tobase=16,string $one=''):string
    {
        if(function_exists('openssl_random_pseudo_bytes')){
            $str = openssl_random_pseudo_bytes($length,$crypto_strong);
            if(!$crypto_strong){ throw new \Exception('请检测系统环境');}
            return $tobase==16?md5(bin2hex($one.$str)):base_convert(md5(bin2hex($one.$str)),16,$tobase);
        }else{
            $str = md5($one.str_replace('.', '', uniqid((string)mt_rand(), true)));
            return $tobase==16?$str:base_convert($one.$str,16,$tobase);
        }
    }

    /**
     * @Author 皮泽培
     * @Created 2019/11/14 10:00
     * @param array $data 需要替换的数据['key'=>'替换成的字符串']
     * @param string $srt  被替换的字符串
     * @param string $left  左边
     * @param string $right  右边
     * @title  批量替换字符串方法
     * @throws \Exception
     */
    public function str_replace(array $data,string &$srt,$left='{{',$right='}}')
    {
        foreach ($data as $key=>$value){
            if (is_array($value)){

            }else{
                $srt = str_replace($left.$key.$right,$value,$srt);
            }
        }
    }

    /**
     * @Author 皮泽培
     * @Created 2020/11/6 10:44
     * @return string
     * @title  删除字符串中的-和_字符串并转换-和_字符串后的第一个字母问大写
     */
    public function transformNamespaceStr($namespaceStr)
    {
        $strlen = strlen($namespaceStr);
        $namespace = '';
        #处理大小写和下划线
        $strtoupper = false;
        for ($x=0; $x<=$strlen-1; $x++)
        {
            $str =ord($namespaceStr[$x]);
            if($str===45 || $str ===95 ){
                $strtoupper = true;
                continue;
            }else{
                if ($strtoupper){
                    $strtoupper = false;
                    $namespace .=strtoupper($namespaceStr[$x]);
                }else{
                    $namespace .=$namespaceStr[$x];
                }
            }
        }
        return $namespace;
    }
}