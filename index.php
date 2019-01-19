<?php
/**
 * Created by PhpStorm.
 * User: pengyu
 * Date: 2018/7/3
 * Time: 14:56
 */

//启动文件
//require_once __DIR__."/src/server.php";
//require_once __DIR__."/src/helper.php";
require_once __DIR__."/vendor/autoload.php";

$CONFIG=require_once __DIR__."/config.php";

new Im(config("host"),config("port"),config("swoole"));
