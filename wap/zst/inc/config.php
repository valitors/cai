﻿<?php
//
header('content-Type: text/html;charset=utf-8');
//header("Cache-Control:no-cache");
error_reporting(E_ERROR & ~E_NOTICE);
date_default_timezone_set('PRC'); //标准化时间
error_reporting(0);
ini_set('display_errors', 'Off');

//数据库配置
$dbconf = array(
    "conn" => "127.0.0.1",    //数据库主机
    "user" => "dadi",        //数据库帐号
    "pwd" => "dadi163.com",        //数据库密码
    "db" => "dadicp" //数据库名称
);
$conf['db']['prename']         = 'blast_'; //表前缀
$conf['cache']['expire']       = 0;
$conf['cache']['dir']          = '/_blast__buffer/';  //缓冲
$conf['member']['sessionTime'] = 15 * 60; // 用户有效时长
?>