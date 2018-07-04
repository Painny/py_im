<?php
/**
 * Created by PhpStorm.
 * User: pengyu
 * Date: 2018/7/3
 * Time: 14:56
 */

//启动文件
require_once "./src/server.php";
require_once "./src/helper.php";

new Im(config("host"),config("port"));