<?php
/**
 * Created by PhpStorm.
 * User: pengyu
 * Date: 2018/7/3
 * Time: 14:56
 */

//启动文件
require_once __DIR__."/src/server.php";
require_once __DIR__."/src/helper.php";

new Im(config("host"),config("port"),config("swoole"));
//$data=makeJwt(array("id"=>1,"nickname"=>"test1","icon"=>"test1.png"));
//echo $data;