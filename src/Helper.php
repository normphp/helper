<?php
/**
 * Created by PhpStorm.
 * User: pizepei
 * Date: 2019/07/15
 * Time: 16:24
 * @title Helper基础类
 */
declare(strict_types=1);

namespace normphp\helper;

use app\HelperClass;
use Closure;
use mysql_xdevapi\CrudOperationBindable;
use normphp\container\Container;
use normphp\staging\App;

/**
 * Class Helper
 * @package normphp\helper
 * @method  File                file(bool $new = false):File 文件类
 * @method  ArrayList           arrayList(bool$new = false):ArrayList  数组类
 * @method  Str                 str(bool$new = false):Str 字符串类
 * @method  Date                Date(bool $new = false):Date 日期时间类
 * @method  Form                Form(bool $new = false):Form 自定义表单处理类
 * @method  ProgressBar         ProgressBar(bool $new = false):ProgressBar 进度条类
 */
class Helper extends Container
{
    /**
     * 容器名称
     */
    const CONTAINER_NAME = 'Helper';
    /**
     * 容器绑定标识(父)
     * @var array
     */
    protected $baseBind = [
        'file'                     => File::class,
        'str'                      => Str::class,
        'helper'                   => Helper::class,
        'arrayList'                => ArrayList::class,
        'Date'                     =>Date::class,
        'ProgressBar'              =>ProgressBar::class,
    ];


    /**
     * @Author 皮泽培
     * @Created 2019/7/23 11:49
     * @param \Redis $redis
     * @param array $name  Lock名请自己分类管理 ['name','name',...]不超过10个
     * @param bool $operation 默认设置Lock  false解除Lock
     * @param string $group 分组在设置Lock时设置，如果其他的任务出现异常可以使用分组删除分组下的所有Lock
     * @param int $usleep 默认300毫秒(三分之三秒)     1秒 = 1000毫秒
     * @param int $ttl 默认有效期问120s 超过ttl自动解除Lock 为了系统稳定不可设置0 超过600
     * @title  syncLock 同步Lock
     * @explain Lock默认有效期问120s 超过ttl自动解除Lock  特别注意一般情况设置Lock放在读取缓存前（判断操作前）解除Lock在获取结果后
     * @return bool|int|string
     */
    public  function syncLock(\Redis $redis,array $name,bool $operation=true,string $group='',int $usleep=300,$ttl = 10)
    {
        # 本方法解决的问题：
        # 同步lock 如 在需要请求第三方接口获取一个token缓存到本地30分钟时
        # 当本地缓存的token过期时同时有4个请求触发的业务逻辑都需要使用token时
        #   正常情况下在在第一个请求第三方接口获取到token并缓存到本地前的所有请求都会触发去请求第三方接口导致重复获取第三方token
        #       假如有4个请求触发了，那么就有概率出现最后一个请求的响应以及获取到并且缓存成功，然后第三个请求结果成响应然后再次缓存导致本地缓存token为无效token
        # 同时请不要忽略http请求的时间差

        # 在本地本地缓存是否存在前 使用syncLock($redis,$name,true)
        #   会判断当前syncLock是否锁
        #       如果是就按照配置usleep()
        #       如果不是就设置Lock锁并且不休眠马上return 进行业务逻辑获取信息并且缓存（这个操作后的其他请求会按照配置usleep()）
        # 在获取到对应内并且缓存成功后 使用syncLock($redis,$name,false)
        #
        # 关于多个Lock 任务嵌套 其中一个任务出现异常 导致死Lock 时可以通过group解决

        if ($ttl == 0){throw new \Exception('TTL cannot be 0');}
        if ($ttl >= 600){throw new \Exception('TTL cannot be greater than 600');}
        if ($usleep === 0){throw new \Exception('Usleep cannot be 0');}
        if (count($name) < 2){throw new \Exception('Name must be at least two');};
        if (count($name) > 10){throw new \Exception(' Names cannot exceed 10');}
        $group = $group==''?'':$group.':';

        $name = implode(':',$name);
        # 判断是 解除Lock  还是设置Lock
        if ($operation){
            $microtime = microtime(true);
            $time = $microtime+$ttl+($usleep*1000)+0.2;
            # 先读取是否有缓存
            $syncLock = $redis->get('helper:syncLock:'.$name);
            if ($syncLock == null || $syncLock==false){
                return $redis->setex('helper:syncLock:'.$group.$name,$ttl,20);# 没有Lock通过  可以执行下面的业务逻辑
            }
            if ($syncLock == 20){
                $usleep = $usleep*1000;
                for ($i=1;(time()-$time)<=0;$i++){
                    usleep($usleep);//缓解系统压力进行休眠
                    $syncLock = $redis->get('helper:syncLock:'.$group.$name);
                    if ($syncLock == null || $syncLock==false){
                        return  ['startTime'=>$microtime,'theTime'=>microtime(true),'i'=>$i];# 没有Lock通过
                    }
                }
                # 意外情况
                return false;
            }
        }else{
            # 解锁
            if ($group ==''){
                return $redis->del('helper:syncLock:'.$group.$name);
            }else{
                $keys = $redis->keys('helper:syncLock:'.$group.'*');
                if (!empty($keys)){return $redis->del($keys);
                }
            }

        }
    }

    /***********************************函数方法*****************************************************/

    /**
     *
     * @title  生成uuid方法
     * @param bool $strtoupper 是否大小写 true为大写
     * @param int  $separator 分隔符  45 -       0 空字符串
     * @param bool $parameter true 是否使用空间配置分布式时不同机器上使用不同的值
     * @return string
     */
    public  function getUuid(bool $strtoupper=false,int $separator=45,string $parameter=''):string
    {
        $charid = md5(($parameter==''?$parameter:mt_rand(10000,99999)).uniqid((string)mt_rand(), true));
        if($strtoupper){$charid = strtoupper($charid);}
        $hyphen = chr($separator);// "-"
        $uuid = substr($charid, 0, 8).$hyphen
            .substr($charid, 8, 4).$hyphen
            .substr($charid,12, 4).$hyphen
            .substr($charid,16, 4).$hyphen
            .substr($charid,20,12);
        return $uuid;
    }

    /**
     * @Author 皮泽培
     * @Created 2019/7/17 16:10
     * @param $data
     * @param $name
     * @title  判断是否为空
     * @explain 如果为int 0  string 0 array [] 都会返回 true
     * @return bool
     * @throws \Exception
     */
    public function is_empty($data,$name=''):bool
    {
        if ($name == ''){
            if (empty($data) || $data === 0 || $data === '0' || $data === '' || $data===[]){
                return true;
            }
        }else if (is_array($data) && $name !== ''){
            # 判断是否存在
            if (!is_array($name)){
                $name = [$name];
            }
            foreach ($name as $key=>$value)
            {
                if (isset($data[$value])){
                    if (empty($data[$value]) || $data[$value] === 0 || $data[$value] === '0' || $data[$value] === '' || $data[$value] === []){
                        return true;
                    }
                }else{
                    return true;
                }
            }
        }else{
            throw new \Exception('参数错误');
        }
        return false;
    }

    /**
     * @title http请求方法
     * @param $url 请求地址（get参数拼接上）
     * @param dtring $data 请求的主体数据
     * @param array $parameter 参数 ssl[0、2]默认2验证https ssl       type [get、put、post、delete] 默认get，有$data自动设置为post        timeout  超时单位秒
     * @return array info 请求信息  body 获取的请求body  error 错误数据   header  响应方的响应header
     */
    public function httpRequest(string $url,string $data='',array $parameter=[]):array
    {
        # 后面考虑是否不使用 throw
        if (empty($url) || $url=='.json'){throw new \Exception('url empty');}
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, $parameter['ssl']??2);//是否忽略证书 默认不忽略
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST,$parameter['ssl']??2);//是否忽略证书 默认不忽略
        #如果在部署过程中代码在此处验证失败，请到 http://curl.haxx.se/ca/cacert.pem 下载新的证书判别文件
        #curl_setopt($curl,CURLOPT_CAINFO,dirname(__FILE__).'/cacert.pem');//这是根据http://curl.haxx.se/ca/cacert.pem 下载的证书，添加这句话之后就运行正常了
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, $parameter['redirect']??false); // 使用自动跳转 默认否
        /**
         * 设置超时
         */
        curl_setopt($curl, CURLOPT_TIMEOUT, $parameter['timeout']??30);//单位 秒，也可以使用
        #curl_setopt($ch, CURLOPT_NOSIGNAL, 1);     //注意，毫秒超时一定要设置这个
        #curl_setopt($ch, CURLOPT_TIMEOUT_MS, 200); //超时毫秒，cURL 7.16.2中被加入。从PHP 5.2.3起可使用
        /**
         * 请求类型
         */
        if (isset($parameter['type'])){
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $parameter['type']); //定义请求类型
            if (!empty($data)){
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            }
        }else if (!empty($data)) {
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        /**
         * 判断是否需要设置header
         */
        $header = ['content-type: application/json'];
        if (isset($parameter['header'])){
            $header = $parameter['header'];
            //定义header
        }
        # 设置不返回HTTP/1.1 100 Continue
        $header[] = 'Expect:';
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);

        /**
         * 设置COOKIE
         */
        if (isset($parameter['cookie'])){
            foreach ($parameter['cookie'] as $value){
                curl_setopt($curl, CURLOPT_COOKIE, $value);
            }
        }
        curl_setopt($curl, CURLOPT_HEADER, 1);//获取头部信息
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
        $output = curl_exec($curl);
        $getinfo = curl_getinfo($curl); //获取请求信息
        $error = curl_error($curl);
        $output = str_replace('HTTP/1.1 100 Continue'.PHP_EOL,'',$output);
        /**
         * 分类$output 获取获取头部信息和主体信息
         */
        if (empty($output)){
            $header = '';
            $body = '';
        }else{
            list($header, $body) = explode("\r\n\r\n", $output);
        }
        $header = explode("\n", $header);
        curl_close($curl);
        return [
            'code' =>$getinfo['http_code']??5000,
            'RequestInfo'  =>  $getinfo??[],//请求详情
            'body' =>$body??'',//响应主体
            'header'=>$header??[],//响应方的响应header 可使用方法分离 Helper::init()::arrayList()->array_explode_value($res['header'],': ',true);
            'error' =>  $error??'',//curl请求错误
            'result'=>  $output??'',//全部结果
        ];
    }

    /**
     * @Author 皮泽培
     * @Created 2019/7/19 14:17
     * @param string $json
     * @param bool $assoc
     * @param int $depth
     * @param int $options
     * @title  json解码 默认不处理大int数据，自动处理可能的失败问题
     * @explain json解码 默认不处理大int数据，自动处理可能的失败问题
     * @return array
     * @throws \Exception
     */
    public function json_decode(string $json,$assoc = true,int $depth=512,$options = JSON_BIGINT_AS_STRING):array
    {
        # 正常解析
        $data = json_decode($json,$assoc,$depth,$options);
        if ($data){
            # json字符串必须是utf8编码
            $encode = mb_detect_encoding($json, array("ASCII",'UTF-8',"GB2312","GBK",'BIG5'));
            if ($encode !== 'UTF-8'){
                $json = mb_convert_encoding($json, 'UTF-8', $encode);
                $data = json_decode($json,$assoc,$depth,$options);
            }
        }
        /**
         * 正常解析失败  编码解析失败
         */
        if (!$data){
            #清除bom头
            $jsonUrl = urlencode($json);
            $data = json_decode(urldecode(trim($jsonUrl, "\xEF\xBB\xBF")),$assoc,$depth,$options);
        }
        #清除bom头失败
        if (!$data){
            #替换' 为"
            $data = json_decode(str_replace("'", '"', $json),$assoc,$depth,$options);
        }
        #替换'失败
        if (!$data){
            #不能有多余的逗号 如：[1,2,]
            $json = preg_replace('/,\s*([\]}])/m', '$1', $json);
            $data = json_decode(str_replace("'", '"', $json),$assoc,$depth,$options);
        }
        #可能是开启get_magic_quotes_gpc  这里就不做处理
        return $data?$data:[];
    }

    /**
     * @Author 皮泽培
     * @Created 2019/7/19 14:19
     * @param array $array
     * @title  array转json字符串 默认不编码中文
     * @explain array转json字符串 默认不编码中文
     * @return string
     * @throws \Exception
     */
    public function json_encode(array $array):string
    {
        return json_encode($array,JSON_UNESCAPED_UNICODE);
    }


    /**
     * [get_ip 不同环境下获取真实的IP]
     * @Effect
     * @return [type] [description]
     */
    public  function get_ip($type = 'direct'){
        /**
         *direct 直连   cdn 官方cnd   代理 agency
         */
        if($type== 'direct'){
            if(isset($_SERVER)){
                $realip = $_SERVER['REMOTE_ADDR'];
            }else{
                $realip = getenv("REMOTE_ADDR");
            }

        }else if($type == 'cdn' || $type == 'agency'){

            //判断服务器是否允许$_SERVER
            if(isset($_SERVER)){
                if(isset($_SERVER['HTTP_X_FORWARDED_FOR'])){
                    $realip = $_SERVER['HTTP_X_FORWARDED_FOR'];
                }elseif(isset($_SERVER['HTTP_CLIENT_IP'])) {
                    $realip = $_SERVER['HTTP_CLIENT_IP'];
                }else{
                    $realip = $_SERVER['REMOTE_ADDR'];
                }
            }else{
                //不允许就使用getenv获取
                if(getenv("HTTP_X_FORWARDED_FOR")){
                    $realip = getenv( "HTTP_X_FORWARDED_FOR");
                }elseif(getenv("HTTP_CLIENT_IP")) {
                    $realip = getenv("HTTP_CLIENT_IP");
                }else{
                    $realip = getenv("REMOTE_ADDR");
                }
            }
        }
        return $realip;
    }

    /**
     * PHP判断当前协议是否为HTTPS
     */
    public function is_https()
    {
        //REQUEST_SCHEME
        if (isset($_SERVER['REQUEST_SCHEME'])){
            return $_SERVER['REQUEST_SCHEME']=='https'?true:false;
        }
        if (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') {
            return true;
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
            return true;
        } elseif (!empty($_SERVER['HTTP_FRONT_END_HTTPS']) && strtolower($_SERVER['HTTP_FRONT_END_HTTPS']) !== 'off') {
            return true;
        }
        return false;
    }

    /**
     * @Author 皮泽培
     * @Created 2019/9/24 17:37
     * @return string
     * @param string $msg  下载速度
     * @title  js alert 效果
     * @explain 路由功能说明
     */
    public function alert($msg)
    {
        echo "<script>alert('".$msg."');</script>";
    }

    /**
     * Get QR-Code URL for image, from google charts.
     * @title  通过谷歌API获取二维码url
     * @param string $name
     * @param string $secret
     * @param string $title
     * @param array  $params
     * @return string
     */
    public function getQRCodeGoogleUrl(string $urlencoded, $level = 'M', int $width = 200,int $height=200):string
    {
        $level = !empty($params['level']) && array_search($params['level'], array('L', 'M', 'Q', 'H')) !== false ? $params['level'] : 'M';
        $urlencoded = urlencode($urlencoded);
        return 'https://chart.googleapis.com/chart?chs='.$width.'x'.$height.'&chld='.$level.'|0&cht=qr&chl='.$urlencoded.'';
    }

    /**
     * 获取所有文件目录地址
     * @param $dir
     * @param $fileData
     */
    public function getFilePathData($dir,&$fileData,string $suffix='.php',string $approve='')
    {
        # 判断是否是目录
        if (is_dir($dir)){
            #  是否是vendor包模式
            if ($approve !=='') {
                $exist = false;
                if ($dh = opendir($dir)){
                    while (($file = readdir($dh)) !== false){
                        if($file != '.' && $file != '..'){
                            # 判断是否是目录
                            if(is_dir($dir.DIRECTORY_SEPARATOR.$file)){
                                $this->getFilePathData($dir.DIRECTORY_SEPARATOR.$file,$fileData,$suffix,$approve);
                            }else{
                                # 判断是否是php文件
                                if($file== $approve){
                                    $approveInfo = file_get_contents($dir.DIRECTORY_SEPARATOR.$file);
                                    $exist = true;
                                }
                            }
                        }
                    }
                    closedir($dh);
                }
                # 有$exist
                if ($exist){
                    if ($dh = opendir($dir)){
                        while (($file = readdir($dh)) !== false){
                            if($file != '.' && $file != '..'){
                                # 判断是否是目录
                                if(is_dir($dir.DIRECTORY_SEPARATOR.$file)){
                                    $this->getFilePathData($dir.DIRECTORY_SEPARATOR.$file,$fileData,$suffix,$approve);
                                }else{
                                    # 判断是否是php文件
                                    if(strrchr($file,$suffix) == $suffix){
                                        if (DIRECTORY_SEPARATOR ==='/'){
                                            $fileData[] = ['path'=>str_replace('..'.DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR,'../',$dir.DIRECTORY_SEPARATOR.$file),'packageInfo'=>$approveInfo];
                                        }else{
                                            $fileData[] = ['path'=>str_replace('/src','',str_replace('..'.DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR,'../',$dir.DIRECTORY_SEPARATOR.$file)),'packageInfo'=>$approveInfo];
                                        }
                                    }
                                }
                            }
                        }
                        closedir($dh);
                    }
                }
            }else{
                if ($dh = opendir($dir)){
                    while (($file = readdir($dh)) !== false){
                        if($file != '.' && $file != '..'){
                            # 判断是否是目录
                            if(is_dir($dir.DIRECTORY_SEPARATOR.$file)){
                                $this->getFilePathData($dir.DIRECTORY_SEPARATOR.$file,$fileData,$suffix,$approve);
                            }else{
                                # 判断是否是php文件
                                if(strrchr($file,$suffix) == $suffix){
                                    $fileData[] = $dir.DIRECTORY_SEPARATOR.$file;
                                }
                            }
                        }
                    }
                    closedir($dh);
                }
            }
        }
    }

    /**
     * 快速迭代器
     * 可以用在大数据的及时处理上比如：有500w的大数据库 可以在闭包中分页一次性返回当前页，foreach每次循环的是一次查询数据库的一页数据处理这样从始至终都不会超过一页数据需要的内存
     * @param Closure $object 闭包
     * @param mixed ...$param 闭包需要的参数
     * @return array|\Generator
     */
    public function yield( Closure $object,...$param)
    {
        /**
         * 简单使用示范
         *  yield($object) 第一个参数$object是一个 闭包函数   如果想多参数可使用...关键字语法   如果想使用引用传参 &引用符号  需要在闭包使用use关键字
         *  use和...关键字语法非必须的
         *  注意：
         *      1、结果$res 必须使用foreach循环才会有返回结果（yield的使用关键）
         *      2、闭包函数的返回值必须是array类型同时，需要有over key 如果没有over key 或者 over为false 就结束循环体
         *      3、闭包函数的返回值一定需要注意，稍有不慎就会导致死循环！！！！
         *   $res = Helper()->yield(function (...$data) use(&$num){
         *      return $data;
         *  },1000,2000);
         *   foreach ($res as $value)
         *   {
         *       var_dump($value);
         *  }
         *
         **/
        while (true)
        {
            # 给中的匿名函数和闭包(closure)参数
            $data = $object(...$param);
            if (!isset($data['over']) || $data['over']){
                yield $data;
                return ;
            }
            yield $data;
        }
    }

    /**
     * @Author 皮泽培
     * @Created 2020/10/24 11:25
     * @param \Redis $redis redis 缓存对象
     * @param string $key key
     * @param $value 值
     * @param int $period 周期
     * @param int $frequency 频率
     * @return bool  true 超过频率  false  没有
     * @title  频率检测
     */
    public function isFrequency(\Redis $redis,string $key,string $value,int$period = 5,int $frequency=15):bool
    {
        $key = $key.':'.$value;
        if (!$redis->exists($key)){
            $redis->setex($key,$period,1);
            return false;
        }else{
            if ($redis->get($key) >$frequency){
                return true;
            }else{
                $redis->incr($key); //次数加一
                return false;
            }
        }
    }

    /**
     * 通过php文件路径获取php 类的命名空间（只支持composer 的vendor目录下的）
     * @param string $socumentRoot
     * @param string $path
     * @param string $useNamespace
     */
    public function getUseNamespace(string $socumentRoot,string $path,string &$useNamespace)
    {
        # 获取基础控制器的命名空间信息
        if (DIRECTORY_SEPARATOR ==='/'){
            $useNamespace = str_replace([$socumentRoot.'vendor','.php','/','..'.DIRECTORY_SEPARATOR,'src','..','\\\\'],['','',"\\",'','','','\\'],$path);
        }else{
            $useNamespace = str_replace([$socumentRoot.'vendor','.php','/','..'.DIRECTORY_SEPARATOR,DIRECTORY_SEPARATOR.'src'.DIRECTORY_SEPARATOR,'..'],['','',"\\",'','\\',''],$path);
        }
        # 判断路径中是否有 - 有就删除- 并且替换-后面的字母为大写
        preg_match_all('/[-][a-z]/s',$useNamespace,$matc);//字段
        if (!empty($matc[0])){
            foreach ($matc as $matcValue){
                foreach ($matcValue as $matcValueS){
                    $useNamespace = str_replace([$matcValueS],[strtoupper(str_replace(['-'],[''],$matcValueS))],$useNamespace);
                }
            }
        }
    }

}