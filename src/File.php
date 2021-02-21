<?php
/**
 * Created by PhpStorm.
 * User: pizepei
 * Date: 2019/07/15
 * Time: 16:24
 * @title Helper文件类
 */
declare(strict_types=1);

namespace normphp\helper;


use Stringy\StaticStringy;
use ZipArchive;

class File
{

    /**
     *  判断目录是否存在
     * 不存在创建
     * @param $dir
     * @param int $mode
     * @return bool
     */
    public  function createDir(string $dir, int $mode = 0777):bool
    {
        if (is_dir($dir) || @mkdir($dir, $mode,true)) return TRUE;
        if (!$this->createDir(dirname($dir), $mode)) return FALSE;
        return @mkdir($dir, $mode,true);
    }

    protected $findFileArr = array();

    /**
     * @Author 皮泽培
     * @Created 2019/7/17 15:03
     * @param $flodername  在指定的目录查找
     * @param $filename 需要查找的文件 [模糊查询时为正则表达式]
     * @param $fuzzy 是否模糊查询[正则表达式]
     * @title  在指定的目录查找指定的文件
     * @explain 路由功能说明
     * @return array
     * @throws \Exception
     */
    public function findFile($flodername, $filename,bool $fuzzy=false)
    {
        if (!is_dir($flodername)) {
            throw new \Exception('Not a directory');
        }
        if ($fd = opendir($flodername)) {
            while($file = readdir($fd)) {
                if ($file != "." && $file != "..") {
                    $newPath = $flodername.'/'.$file;
                    if (is_dir($newPath)) {
                        $this->findFile($newPath, $filename);
                    }
                    if ($fuzzy){
                        preg_match($filename,$file,$fileStr);
                        if (isset($fileStr[0]) && !empty($fileStr[0])){
                            $this->findFileArr[] = $newPath;
                        }
                    }else{
                        if ($file == $filename) {
                            $this->findFileArr[] = $newPath;
                        }
                    }
                }
            }
        }
        return $this->findFileArr;
    }

    /**
     * @Author pizepei
     * @Created 2019/7/7 8:58
     * @param $path
     * @title  删除文件夹以及文件夹下的所有文件
     * @explain 清空文件夹函数和清空文件夹后删除空文件夹函数的处理
     */
    public function deldir(string $path)
    {
        //如果是目录则继续
        if(is_dir($path)){
            //扫描一个文件夹内的所有文件夹和文件并返回数组
            $p = scandir($path);
            foreach($p as $val){
                //排除目录中的.和..
                if($val !="." && $val !=".."){
                    //如果是目录则递归子目录，继续操作
                    if(is_dir($path.$val)){
                        //子目录中操作删除文件夹和文件
                        $this->deldir($path.$val.'/');
                        //目录清空后删除空文件夹
                        @rmdir($path.$val.'/');
                    }else{
                        //如果是文件直接删除
                        unlink($path.$val);
                    }
                }
            }
        }
    }

    /**
     * @Author 皮泽培
     * @Created 2019/9/24 17:11
     * @param string $path
     * @title  获取文件大小
     * @explain 获取文件大小
     * @return string
     * @router get
     */
    public function getFileSize(string $path)
    {
        return number_format(filesize($path) / (1024 * 1024), 2);//去小数点后两位
    }

    /**
     * @Author 皮泽培
     * @Created 2020/10/24 10:52
     * @param string $path
     * @return array [json] 定义输出返回数据
     * @title  获取文件完整信息
     */
    public function getFileInfo(string $path)
    {
        if(!is_file($path)) {
            return  [
                'fileType'=>'',
                'fileSize'=>'',
                'filemTime'=>'',
            ];
        }
        $filemtime = filemtime($path);
        return  [
            'fileType'=>filetype($path),
            'fileSize'=>$this->getFileSize($path),
            'filemTime'=>$filemtime,
            'filemDate'=>date('Y-m-d H:i:s',$filemtime),
        ];
    }
    /**
     * @Author 皮泽培
     * @Created 2019/9/24 16:46
     * @param string $path 不包括base路径的 文件路径(包括文件名)
     * @param string $name 下载时显示的名称包括扩展名的文件名称
     * @param int $buffer  下载速度
     * @param string $base 基础路径
     * @return string
     * @title  提供下载
     * @explain 对外通过简单的下载
     * @throws \Exception
     */
    public function provideDownloads(string $path,string $name,int $buffer=1024,string $base='..'.DIRECTORY_SEPARATOR.'tmp'.DIRECTORY_SEPARATOR)
    {
        # 安全起见 路径有一个base路径  $path路径不能包含. ..
        if (strpos($path,'..'.DIRECTORY_SEPARATOR) !== false || strpos($path,'.'.DIRECTORY_SEPARATOR)!== false ){
            return 'Speed illegal path';
        }
        $filePath = $base.$path;
        if(!file_exists($filePath)){
            return $name.' There is no';
        }
        $fileSize = $this->getFileSize($filePath);
        //打开文件
        $file = fopen($filePath, "r");
        //返回的文件类型
        Header("Content-type: application/octet-stream");
        //按照字节大小返回
        Header("Accept-Ranges: bytes");
        //返回文件的大小
        Header("Accept-Length: ".filesize($filePath));
        //这里对客户端的弹出对话框，对应的文件名
        Header("Content-Disposition: attachment; filename=".$name);
        //修改之前，一次性将数据传输给客户端
        echo fread($file, filesize($filePath));
        //修改之后，一次只传输1024个字节的数据给客户端
        //向客户端回送数据
        //判断文件是否读完
        while(!feof($file)){
            //将文件读入内存
            $file_data = fread($file, $buffer);
            //每次向客户端回送$buffer个字节的数据
            echo $file_data;
        }
        fclose($file);
    }

    /**
     * @Author 皮泽培
     * @Created 2020/11/23 10:48
     * @param string $fileName
     * @param \Closure $object 闭包函数：进行详细的业务逻辑处理
     * @param array $cellList 设置表格表头信息 例：[['field'=>'编号','width'=>6,'center'=>1]] field为必须
     * @param \Closure $styleObject 闭包函数：处理样式逻辑
     * @throws \Exception
     * @title  导出表格
     * @explain 导出表格
     */
    public function officeImport(string $fileName,\Closure $object,$cellList=[],$styleObject=null)
    {
        # ------------示例（normphp框架）-----------
        #-------------路由需设置 @return array [xlsx] 非normphp框架可能需要在officeImport()后使用exit()---------
        # 取出来数据
        #$yieldData =  XXXXXXModex::table()->where(['status'=>4])->order('creation_time','asc')->yieldFetchAllPage([],1,10);
        # center
        #Helper()->file()->officeImport('信息'.date('His'),[['field'=>'编号','center'=>1],['field'=>'SN','center'=>1]],function (\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet) use ($yieldData,&$index){
        #    $index = $index??1;
        #     foreach ($yieldData as $value){
        #        if (!empty($value['data'])){
        #            foreach ($value['data'] as $v){
        #               $sheet->setCellValueByColumnAndRow(1, $index+1, $v['id']);//编号
        #               $sheet->setCellValueByColumnAndRow(2, $index+1, $v['sn']);//编号
        #                $index++;
        #            }
        #        }
        #    }
        #},function (\PhpOffice\PhpSpreadsheet\Spreadsheet $spreadsheet){
        #    # 将A7至B7两单元格设置为粗体字，Arial字体，10号字
        #    $spreadsheet->getActiveSheet()->getStyle('A7:B7')->getFont()->setBold(true)->setName('Arial')
        #        ->setSize(10);
        #     # 将B1单元格设置为粗体字。
        #     $spreadsheet->getActiveSheet()->getStyle('A1')->getFont()->setBold(true);
        #     # 将文字颜色设置为红色。
        #    $spreadsheet->getActiveSheet()->getStyle('A4')
        #        ->getFont()->getColor()->setARGB(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_RED);
        #    # 如果需要自动计算列宽，可以这样：
        #   $spreadsheet->getActiveSheet()->getColumnDimension('A')->setAutoSize(true);
        #});

        # 初始化PhpSpreadsheet
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $spreadsheet->getProperties()
            ->setCreator("System Auto Create")
            ->setLastModifiedBy("Siro")
            ->setTitle("Office 2003 XLS Document")
            ->setSubject("Office 2003 XLS Document")
            ->setDescription("document for PHPExcel, generated using PHP classes.")
            ->setKeywords("office PHPExcel php")
            ->setCategory("Report file");
        # 获取活动工作薄
        $sheet = $spreadsheet->getActiveSheet();
        # 设置表样式
        if ($styleObject !==null){
            $styleObject($spreadsheet);
        }
        # 设置表头部（列）
        if ($cellList==[]){throw new \Exception('表头不能为空');}
        $sheet->fromArray(array_column($cellList, 'field'));
        # 数据处理
        $object($sheet);
        #echo ((memory_get_usage()/1024)/1024).PHP_EOL;
        # 设置表列宽
        foreach($cellList as $index => $item){
            if (isset($item['width'])){
                $coordinate = $spreadsheet->getActiveSheet()->getColumnDimension(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index + 1));
                $coordinate->setWidth($item['width']);
            }
        }
        $maxLine = $sheet->getHighestRow();
        # 设置是否居中
        foreach($cellList as $index => $item){
            if(!empty($item['center'])){
                $indexName = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index + 1);
                $spreadsheet->getActiveSheet()->getStyle("{$indexName}1:{$indexName}{$maxLine}")
                    ->getAlignment()->applyFromArray(['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]);
            }
        }
        # 导出
        $this->officeImportFileOutput($spreadsheet,$fileName);
    }

    /**
     * @Author 皮泽培
     * @Created 2020/9/12 15:20
     * @param Spreadsheet $spreadsheet 对象
     * @param string $filename 文件名称
     * @param string $type 文件类型
     * @title  路由标题
     * @router get
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    function officeImportFileOutput( \PhpOffice\PhpSpreadsheet\Spreadsheet $spreadsheet,$filename='默认',$type='xlsx')
    {
        if ($type ==='xlsx'){
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        }else if ($type ==='xls'){
            header('Content-Type: application/vnd.ms-excel');
        }
        header('Cache-Control: max-age=0');
        header('Content-Disposition: attachment;filename="'.$filename.'.'.$type.'"');//告诉浏览器输出浏览器名称
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
    }

    /**
     * @Author 皮泽培
     * @Created 2020/11/20 18:03
     * @param string $file 处理文件地址
     * @param \Closure $function 处理函数,需要注意处理函数的性能处理、内存处理
     * @param float $size 文件大小限制单位MB 默认1 MB 经过测试1MB文件读取需要消耗大概100MB的内存
     * @title  读取表格（第一行标题也会被读取）
     * @throws Exception
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     * @throws \Exception
     */
    function officeRead(string $file,\Closure $function,float $size=5.00)
    {
        # 判断文件大小 默认不支持超过1MB
        $fileSize = $this->getFileSize($file);
        if ($fileSize > $size){ throw new \Exception('读取文件为'.$fileSize.'/MB 超过大小限制：'.$size.'/MB');}
        ini_set('memory_limit',number_format(($fileSize*120),0).'M');    // 经过测试1MB文件读取需要消耗大概100MB的内存
        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($file);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($file);
        $sheet = $spreadsheet->getActiveSheet();

        $highestRow = $sheet->getHighestRow(); // 总行数
        $highestColumn = $sheet->getHighestColumn(); // 总列数
        $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn); // e.g. 5
        $lines = $highestRow - 2;
        if ($lines <= 0) {throw new \Exception('Excel表格中没有数据');}
        for ($row = 1; $row <= $highestRow; ++$row) {
            $function($sheet,$row);
            # 函数编写显例：
            # Helper()->file()->officeRead($this->app->DOCUMENT_ROOT.$file,function ($sheet,$row) use (&$errorData){
            #   # 每一行会执行一次本函数
            #   # 如果不需要标题
            #   if ($row ===1){
            #         return [];
            #   }
            #   #函数编写显例： 获取行的数据  第一个参数代表列 第二个是行
            #   $data['title'] = $sheet->getCellByColumnAndRow(0, $row)->getValue();#1列
            #   $data['name'] = $sheet->getCellByColumnAndRow(1, $row)->getValue();#2列
            #   # 这里可以使用$data进行数据库操作
            #
            #   # 也可以可以把函数内的数据统一放入一个变量$errorData中（不推荐）
            #   $errorData[$row] = $data;
            #})

        }
    }

    /**
     * zip解压方法
     * @param string $filePath 压缩包所在地址 【绝对文件地址】d:/test/123.zip
     * @param string $path 解压路径 【绝对文件目录路径】/test
     * @return bool
     */
    function unzip(string $filePath, string $path):bool
    {
        # 判断文件是否存在
        if (!is_file($filePath)){
            return false;
        }
        $zip = new ZipArchive();
        if ($zip->open($filePath) === true) {
            $zip->extractTo($path);
            $zip->close();
            return true;
        } else {
            return false;
        }
    }

    /**
     * 模式
     * @var string
     */
    public $progressBarPattern = 'cli';
    /**
     * @var string
     */
    public $progressBar = '';

    /**
     * CURL下载文件 成功返回文件名，失败返回false
     * @param string $url 下载地址
     * @param string $fileName 保存的文件名
     * @param string $savePath 下载保存路径
     * @param array $header 请求头
     * @param string $pattern
     * @param bool $restart 是否是重新开始下载
     * @return bool|string
     * @throws Exception
     * @author Zou Yiliang
     */
    public  function downloadFile(string $url, string $fileName,$savePath = '',$header=[],$pattern='cli',bool$restart=false)
    {
        $this->progressBarPattern = $pattern;
        $this->downloaded  = false;
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // 跳过证书验证（https）的网站无法跳过，会报错
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 跳过证书验证
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

        # 开启进度条
        curl_setopt($ch, CURLOPT_NOPROGRESS, 0);
        # 进度条的触发函数
        curl_setopt($ch, CURLOPT_PROGRESSFUNCTION, array($this, 'downloadProgress'));
        curl_setopt($ch, CURLOPT_HEADER, TRUE);  //需要response header
        curl_setopt($ch, CURLOPT_NOBODY, FALSE);  //需要response body

        curl_setopt($ch, CURLOPT_AUTOREFERER, TRUE);  //当根据Location:重定向时，自动设置header中的Referer:信息。
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);  //跟随重定向

        $output = curl_exec($ch);
        $getinfo = curl_getinfo($ch); //获取请求信息
        $error = curl_error($ch);
        if (!empty($error) && !$restart ){
            $this->downloadFile($url,$fileName,$savePath ,$header,$pattern,true);
        }else if (!empty($error)&& $restart) {
            throw new \Exception($error);
        }
        $output = str_replace('HTTP/1.1 100 Continue'.PHP_EOL,'',$output);
        /**
         * 分类$output 获取获取头部信息和主体信息
         */
        if (empty($output)){
            $header = '';
            $body = '';
        }else{
            $outputArr = explode("\r\n\r\n", $output);
            $header = $outputArr[0]??'';
            $body = isset($outputArr[2])?$outputArr[2]:$outputArr[1];
        }
        $header = explode("\n", $header);
        curl_close($ch);
        if (empty($body??'')){return false;}
        //处理目录
        if (!is_dir($savePath)) {
            @mkdir($savePath, 0777, true);
            @chmod($savePath, 0777);
        }
        if (file_put_contents($savePath.$fileName, $body)) {
            return $savePath.$fileName;
        }
        return false;
    }

    /**
     * 是否在显示下载进度条
     * @var bool
     */
    protected  $startTheDownload = false;
    protected  $startTheDownloadEOL = false;
    protected $currentDownloadSize = 0;
    /**
     * 进度条下载.
     * @param $ch
     * @param int $countDownloadSize 总下载量
     * @param int $currentDownloadSize 当前下载量
     * @param $countUploadSize
     * @param $currentUploadSize
     * @return false
     */
    public function  downloadProgress($ch,int $countDownloadSize,int $currentDownloadSize, $countUploadSize, $currentUploadSize)
    {
        try {
            if ($countDownloadSize !==0){
                if ($this->progressBarPattern ==='cli'){
                    Helper()->ProgressBar()->init(60,'B')->output($countDownloadSize,$currentDownloadSize);
                }else{
                    Helper()->ProgressBar()->init(60,'B')->percentageOutput($countDownloadSize,$currentDownloadSize);
                }
                $this->startTheDownload = true;
                $this->startTheDownloadEOL= true;
            }else{
                if ($this->startTheDownload && $this->startTheDownloadEOL){
                    $this->startTheDownloadEOL=false;
                    if ($this->progressBarPattern ==='cli'){
                        echo PHP_EOL;
                    }
                }else{
                    if ($this->progressBarPattern ==='cli'){
                        echo "\033[500D 正在 请求\重定向 下载地址";
                    }
                }
            }
        }catch (\Exception $e){
            echo $e->getMessage().PHP_EOL;
            return false;
        }
        return false;
    }

    /**
     * 复制目录
     * @param $source
     * @param $dest
     */
    public function copyDir($source,$dest){
        if (!file_exists($dest)) mkdir($dest);
        $handle = opendir($source);
        while (($item = readdir($handle)) !==false){
            if ($item =='.' || $item =='..') continue;
            $_source = $source . '/' . $item;
            $_dest = $dest .'/'.$item;
            if (is_file($_source)) copy($_source,$_dest);
            if (is_dir($_source)) $this->copyDir($_source,$_dest);
        }
        closedir($handle);
    }
}
