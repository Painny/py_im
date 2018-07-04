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

new Im(config("host"),config("port"));