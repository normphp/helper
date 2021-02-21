<?php
/**
 * 时间相关方法
 * to_char(sysdate,'yyyy-mm-dd hh24:mi:ss')
 */
declare(strict_types=1);

namespace normphp\helper;


class Date
{
    /**
     * 24小时
     */
    const HH24 = [
        '00'=>[], '01'=>[], '02'=>[], '03'=>[], '04'=>[], '05'=>[], '06'=>[], '07'=>[], '08'=>[], '09'=>[], '10'=>[],'11'=>[],'12'=>[],
        '13'=>[], '14'=>[], '15'=>[], '16'=>[], '17'=>[], '18'=>[], '19'=>[], '20'=>[], '21'=>[], '22'=>[], '23'=>[]
    ];
    /**
     * 一天的秒
     */
    const day_time = 86400;
    /**
     * 一周的秒
     */
    const week_time = 604800;
    /**
     * 一小时的秒
     */
    const h_time = 3600;

    /**
     * @Author 皮泽培
     * @Created 2019/8/29 9:39
     * @param string $format  需要的时间格式
     * @param string $start 开始时间  按照时间发生的先后顺序  2019-07-01    2019-01-01     开始时间是2019-01-01
     * @param string $over  结束    按照时间发生的先后顺序   2019-07-01    2019-01-01     结束时间是2019-07-01
     * @param string $dateType year  month  day  hour($format 需要有H)  minute 周期单位
     * @param string $arrayType key  or value  配置是时间为key还是value
     * @param int $limitation  获取的列表极限
     * @title  获取一个时间区间内的所有时间
     * @return array
     */
    public function intDate(string $format,string $start,string $over,string $dateType = 'day',string $arrayType='key',int $limitation = 1000):array
    {
        if ($start > $over){
            throw new \Exception(' The start time cannot be greater than the end time ');
        }
        $over =  date($format,(strtotime($over)));
        $start = date($format,(strtotime($start))) ;
        $resData[$start] =[];
        $i = 0;
        while($start < $over ){
            ++$i;
            $start = date($format,strtotime('+ 1 '.$dateType,strtotime($start)));
            if ($arrayType === 'key'){
                $resData[$start] = [];
            }elseif ($arrayType === 'value') {
                $resData[] = [$start];
            }
            if ($i>=$limitation){
                return $resData;
            }
        }
        return $resData;
    }

}