<?php
/**
 * Created by PhpStorm.
 * User: pengyu
 * Date: 2018/7/3
 * Time: 14:56
 */

require_once __DIR__."/vendor/autoload.php";

$CONFIG=require_once __DIR__."/config.php";

new Im(config("host"),config("port"),config("swoole"));
