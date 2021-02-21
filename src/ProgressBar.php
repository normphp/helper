<?php

declare(strict_types=1);
namespace normphp\helper;

/**
 * 进度条类
 * Class ProgressBar
 * @package normphp\helper
 */
class ProgressBar
{

    /**
     * cli重定向标识符
     * @var string
     */
    protected $redirect = "\033[560D";

    /**
     * 单位类型
     */
    const UNIT_TYPE = ['B','KB','MB','GB','TB'];

    protected $blank = ' ';
    /**
     * 未选择输出
     * @var string
     */
    protected $charEmpty = '░';

    /**
     * 已下载输出
     * @var string
     */
    protected $charFull = '▓';
    /**
     * 进度条长度
     * @var int
     */
    protected $length = 66;
    /**
     * 输入数据单位 暂时只支持 比特单位[B|KB|MB|GB|TB] 或者为空不显示单位
     * @var string
     */
    protected $importUnit = '';

    /**
     * 输出模式 cli|percentage
     * @param int $length 进度条长度 建议10-100
     * @param string $importUnit 输入数据单位 暂时只支持 比特单位[B|KB|MB|GB|TB] 或者为空不显示单位
     * @param string $pattern 输出模式 cli|percentage
     * @throws \Exception
     */
    public function init(int $length=66,string $importUnit='',string $pattern ='cli'):ProgressBar
    {
        if ($length>100 || $length<10)throw new \Exception('length进度条长度 建议10-100');
        $this->length = $length;
        $this->importUnit = $importUnit;

        if (!in_array($pattern,['cli','percentage']))throw new \Exception('pattern 输出模式 cli|percentage');
        $this->pattern = $pattern;
        return $this;
    }

    /**
     * 标准输出
     * @param int $now
     * @param int $sum
     */
    public function output($now=100,$sum=66)
    {
        if ($this->pattern === 'percentage'){
            $this->percentageOutput($now,$sum);
        }elseif ($this->pattern === 'cli'){
            $this->cliOutput($now,$sum);
        }
    }

    /**
     * 命令行输出数据处理
     * @param $now
     * @param $sum
     * @throws \Exception
     */
    public function cliOutput($now,$sum)
    {
        $divisor = round($this->length/100,3);
        $resUnit = $this->setUnit($now,$sum,$this->importUnit);
        $str = '';
        $percentage = $this->percentage($now,$sum);
        for ($i=1;$i<$this->length;$i++){
            # 拼接处理进度条
            $str .= ($percentage*$divisor)<$i ? $this->charEmpty : $this->charFull;
        }
        $str .=$this->blank.$percentage.'%'.$this->blank.'['.$resUnit['sum'].'/'.$resUnit['unitCount'].']'.$resUnit['unit'];
        echo $this->redirect.$this->blank.$str;
    }

    /**
     * 以数组形式返回
     * @param $now
     * @param $sum
     * @return array
     * @throws \Exception
     */
    public function percentageOutput($now,$sum)
    {
        $divisor = round($this->length/100,3);
        $resUnit = $this->setUnit($now,$sum,$this->importUnit);
        $percentage = $this->percentage($now,$sum);
        $resUnit['percentage']=$percentage;
        return $resUnit;
    }

    /**
     * 简单设置单位
     * @param $now
     * @param $sum
     * @param $importUnit
     * @return array
     * @throws \Exception
     */
    public function setUnit($now,$sum,$importUnit)
    {
        if ($importUnit ===''){return ['unit'=>'','unitCount'=>$now,'now'=>$now,'sum'=>$sum];}
        if (!in_array($importUnit,['B','KB','MB','GB','TB'])){throw new \Exception('Unit B KB MB GB TB');}
        if ($importUnit ==='B')
        {
            $B = $now;
            $unit = ($KB = $this->unitCount($now))<1?'B':(
                ($MB = $this->unitCount($KB))<1?'KB':(
                    ($GB = $this->unitCount($MB))<1?'MB':(
                        ($TB = $this->unitCount($GB))<1?'GB':'TB'
                    )
                )
            );

        }
        else if ($importUnit ==='KB'){
            $KB = $now;
            $unit = ($MB = $this->unitCount($now))<1?'KB':(
                        ($GB = $this->unitCount($MB))<1?'MB':(
                            ($TB = $this->unitCount($GB))<1?'GB':'TB'
                        )
                );
        }
        else if ($importUnit ==='MB'){
            $MB = $now;
            $unit = ($GB = $this->unitCount($now))<1?'MB':(
                ($TB = $this->unitCount($GB))<1?'GB':'TB'
            );
        }
        else if ($importUnit ==='GB'){
            $GB = $now;
            var_dump( $this->unitCount($GB));
            $unit = ($TB = $this->unitCount($GB))<1?'GB':'TB';
        }
        else if ($importUnit ==='TB'){
            $TB = $now;
            $unit = $importUnit;
        }
        /**
         * 结束当前值
         */
        $importUnitKey = array_search($importUnit,self::UNIT_TYPE);
        $UnitKey = array_search($unit,self::UNIT_TYPE);
        $divisor = ($UnitKey-$importUnitKey);

        $sum = $divisor===0?$sum:$this->unitCount($sum,$divisor);
        return ['unit'=>$unit,'unitCount'=>$$unit,'now'=>$now,'sum'=>$sum];
    }
    /**
     * @return bool
     */
    public function unitCount($count ,int $divisor=1):float
    {
        for ($i=0;$i<$divisor;$i++){
            $count = $count/1024;
        }
        return  round($count,2);
    }
    /**
     * 计算百分比
     * @param int $now
     * @param int $sum
     * @return float
     */
    public function percentage(int$now=100,int$sum=10)
    {
        if ($sum > $now){throw new \Exception('进度条数据异常，读取进度'.$sum.'超过总数'.$now);}
        /**
         * 进度数据四舍五入
         */
        return round($sum/$now*100,2);
    }
}