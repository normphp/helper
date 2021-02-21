<?php


namespace normphp\helper;

/**
 * 自定义表单处理类
 * Class Form
 * @package normphp\helper
 */
class Form
{
    /**
     * 支持的输入框类型
     */
    const INPUT_TYPE = ['radio','checkbox','text','select','password','textarea'];
    /**
     * 层级限制
     * @var int
     */
    private $tier = 3;

    const STRUCTURE = [
        'type'=>'title|input',//标题性质|表单输入框
        'name'=>'',//表单名
        'title'=>'',//输入框前的标题
        'word'=>'',//辅助文字
        'tips'=>'',//提示
        'input-type'=>'',//INPUT_TYPE
        'select-data'=>[
            'type'=>'url|data',//数据类型
            'data'=>'',//数组或者url
        ],//下拉框是否选中
        'radio-data'=>[
            'type'=>'url|data',//数据类型
            'data'=>'',//数组或者url
        ],
        'checkbox-data'=>[
            'type'=>'url|data',//数据类型
            'data'=>'',//数组或者url
        ],
        'data'=>[ //通用数据来源
            'type'=>'url|data',//数据类型
            'data'=>'',//数组或者url
        ],
        'disabled'=>'',//是否禁用
        'selected'=>'',//下拉框是否选中
        'checked'=>'',//checkbox 是否选中
        'autocomplete'=>'',//自动填写地址off|on
        'placeholder'=>'',//表单占位字符
        'verify'=>[
            'required'=>'',//是否必须
            'lay-verify'=>'',
            'RegExp'=>'',//正则表达式
        ],//验证
        'junior'=>[],#下级
    ];

}