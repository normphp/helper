<?php
/**
 * Created by PhpStorm.
 * User: pizepei
 * Date: 2019/07/15
 * Time: 16:24
 * @title Helper文件类  数组函数
 */
declare(strict_types=1);
namespace normphp\helper;

class ArrayList
{
    /**
     * @Author 皮泽培
     * @Created 2019/7/19 11:26
     * @param array $array
     * @param string $string
     * @param bool $strim  true 时 删除\r\n\r\n和空格
     * @title  把索引数组value用定义的字符串切割成数组集合
     * @explain  把索引数组value用定义的字符串切割成数组集合 相同的key会合并
     * @return array
     * @throws \Exception
     */
    public function array_explode_value(array $array,string $string,bool $strim=false):array
    {
        if (empty($array)){
            return [];
        }
        foreach ($array as $value){
            $explode = explode($string,$value);
            list($k, $v) = isset($explode[1])?$explode:[$value,$value];
            if ($strim){
                $k = trim(rtrim($k,"\r\n\r\n "),"\r\n\r\n ");
                $v = trim(rtrim($v,"\r\n\r\n "),"\r\n\r\n ");
            }
            /**
             * 如果出现重复的
             */
            if (isset($data[$k])){
                $recursive[$k] = $v;
                $data = array_merge_recursive($data,$recursive);
            }else{
                $data[$k] = $v;
            }
        }
        return $data;
    }

    /**
     * @Author 皮泽培
     * @Created 2019/12/23 11:53
     * @param $arr1
     * @param $arr2
     * @title  深层合并数组
     * @explain 深层合并数组(两个)
     * @return array
     * @throws \Exception
     */
    public function array_merge_deep($arr1,$arr2){
        $merged	= $arr1;

        foreach($arr2 as $key => &$value){
            if(is_array($value) && isset($merged[$key]) && is_array($merged[$key])){
                $merged[$key]	= $this->array_merge_deep($merged[$key], $value);
            }elseif(is_numeric($key)){
                if(!in_array($value, $merged)) {
                    $merged[]	= $value;
                }
            }else{
                $merged[$key]	= $value;
            }
        }

        return $merged;
    }

    /**
     * @Author 皮泽培
     * @Created 2019/12/28 9:30
     * @param mixed ...$arr1
     * @return array [json] 定义输出返回数据
     * @title  多数组批量合并
     * @throws \Exception
     */
    public function array_merge_deep_more(...$arr)
    {
        if (count($arr) <=1){ throw new \Exception('至少两个array');}
        $count = count($arr);
        for ($i=1;$i<$count;$i++)
        {
            $merged = array_shift($arr);
            $merged = $this->array_merge_deep($merged,$arr[0]);
        }
        return $merged;
    }
    /**
     * @Author 皮泽培
     * @Created 2019/12/26 14:54
     * @title  深层数组排序
     * @param $data 需要排序的array
     * @param $condition ['key'=>'SORT_DESC',...]   SORT:SORT_DESC,SORT_ASC
     * @return array
     */
    public function sortMultiArray(&$data, $condition):array
    {
        if (count($data) <= 0 || empty($condition)) {
            return $data;
        }
        $dimension = count($condition);
        $fileds = array_keys($condition);
        $types = array_values($condition);
        switch ($dimension) {
            case 1:
                $data = $this->sort1Dimension($data, $fileds[0], $types[0]);
                break;
            case 2:
                $data = $this->sort2Dimension($data, $fileds[0], $types[0], $fileds[1], $types[1]);
                break;
            default:
                $data = $this->sort3Dimension($data, $fileds[0], $types[0], $fileds[1], $types[1], $fileds[2], $types[2]);
                break;
        }
        return $data;
    }
    public function sort1Dimension(&$data, $filed, $type)
    {
        if (count($data) <= 0) {
            return $data;
        }
        foreach ($data as $key => $value) {
            $temp[$key] = $value[$filed];
        }
        array_multisort($temp, $type, $data);
        return $data;
    }
    public function sort2Dimension(&$data, $filed1, $type1, $filed2, $type2)
    {
        if (count($data) <= 0) {
            return $data;
        }
        foreach ($data as $key => $value) {
            $sort_filed1[$key] = $value[$filed1];
            $sort_filed2[$key] = $value[$filed2];
        }
        array_multisort($sort_filed1, $type1, $sort_filed2, $type2, $data);
        return $data;
    }
    public function sort3Dimension(&$data, $filed1, $type1, $filed2, $type2, $filed3, $type3)
    {
        if (count($data) <= 0) {
            return $data;
        }
        foreach ($data as $key => $value) {
            $sort_filed1[$key] = $value[$filed1];
            $sort_filed2[$key] = $value[$filed2];
            $sort_filed3[$key] = $value[$filed3];
        }
        array_multisort($sort_filed1, $type1, $sort_filed2, $type2, $sort_filed3, $type3, $data);
        return $data;
    }


    /**
     * 拼接array 合并第一层、不合并下级(追加)
     * @param mixed ...$arrayData
     * @return array
     */
    public function arrayAdditional(...$arrayData)
    {
        # 筛选主项目数据出来 在最后合并
        $array =  array_merge(...$arrayData);
        $data = [];
        foreach ($array as $k=>$v)
        {
            array_push($data,...array_values($v));
        }
        return $data;
    }


    /**
     * 快速设置数据: $setData = ['a'=>10,'b'=>50,'c'=>'30','d'=>'data']  $data = ['a'=>100,'b'=>500,'c'=>300]
     *  最后结果 $data = ['a'=>10,'b'=>50,'c'=>'30']
     *  设置的数据如不在$data中时数据不会被处置 设置的数据类型与$data中数据类型不一致是不会被处置数据
     * @param $setData 需要设置的数据
     * @param $data  标准模板数据
     * @return bool
     */
    public function verifyMergeData($setData,&$data)
    {
        foreach ($data as $key=>&$value)
        {
            if (isset($setData[$key])){
                # 判断是否相同的数据类型
                if (!is_array($setData[$key])  && gettype($setData[$key]) === gettype($value))
                {
                    $value = $setData[$key];
                }elseif (is_array($setData[$key])){
                    $this->verifyMergeData($setData[$key],$value);
                }
            }
        }
    }


    public function getLayuiTree(array $data,array$matchup,$param=[],$parent_id='00000000-0000-0000-0000-000000000000')
    {
        if ($data===[] ||$data===null){return [];}
        foreach ($data as $key=>$value){
            $array= [
                'title'=>$matchup['title']($value,$param),//节点标题
                'id'=>$matchup['id']($value,$param),//节点唯一索引值，用于对指定节点进行各类操作
                'field'=>$matchup['field']($value,$param),//节点字段名
                'checked'=>$matchup['checked']($value,$param),//节点是否初始为选中状态（如果开启复选框的话），默认 false
                'spread'=>$matchup['spread']($value,$param),//节点是否初始展开，默认 false
                'disabled'=>$matchup['disabled']($value,$param),//节点是否为禁用状态。默认 false
            ];
            # 如果是顶级
            $foreachParent_id = $value['id'];
            # 判断是否是需要的数据
            if ($value['parent_id'] === $parent_id){
                #提升性能删除不需要的数据
                unset($data[$key]);
                $array['children'] = $this->getLayuiTree($data,$matchup,$param,$foreachParent_id);//子节点。支持设定选项同父节点
                $result[] = $array;

            }
        }
        return $result??[];
    }

}