<?php

/**
 * 生成随机字符串
 * @param  integer $num 生成字符串的长度
 * @return string       返回生成的随机字符串
 */
function get_random_str($num = 8)
{
    $pattern = 'AaZzBb0YyCc9XxDd8Ww7EeVvF6fUuG5gTtHhS4sIiRr3JjQqKkP2pLlO1oMmNn';
    $str     = '';
    for ($i = 0; $i < $num; $i++) {
        $str .= $pattern{mt_rand(0, 35)}; //生成 php 随机数
    }
    return $str;
}

/**
 * 把用户状态转换成文字
 * @param  integer $status 用户状态值
 * @return string          返回用户状态
 */
function get_user_status($status = 0)
{
    switch ($status) {
        case 1:
            $status_txt = '禁用';
            break;
        default:
            $status_txt = '启用';
            break;
    }
    return $status_txt;
}

/**
 * 获取毫秒数
 */
function get_millisecond()
{
    list($microsecond, $time) = explode(' ', microtime()); //' '中间是一个空格
    return (float) sprintf('%.0f', (floatval($microsecond) + floatval($time)) * 1000);
}
