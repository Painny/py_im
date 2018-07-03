<?php
/**
 * Created by PhpStorm.
 * User: pengyu
 * Date: 2018/7/3
 * Time: 14:56
 */

//启动文件
require_once "./src/db.php";

$dsn='mysql:dbname=im;host=127.0.0.1';
$user='root';
$pwd='root';
try{
    $DB=new DataBase($dsn,$user,$pwd);
}catch (Exception $e){
    echo $e->getMessage();exit;
}

$res=$DB->table("user")->where("id>?",[0])->count();