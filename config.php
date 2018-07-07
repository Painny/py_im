<?php
/**
 * Created by PhpStorm.
 * User: pengyu
 * Date: 2018/7/3
 * Time: 14:53
 */

return array(
    //监听地址
    "host"          =>  "0.0.0.0",
    "port"          =>  9501,
    //swoole配置
    "swoole"    =>  array(
        "reactor_num"       =>  2,
        "worker_num"        =>  4,
        "task_worker_num "  =>  4,
        "max_conn"          =>  200,
        "daemonize"         =>  false,
        "dispatch_mode"     =>  5,
//        "log_file"        =>  "/var/log/test-im/im.log",
//        "log_level"       =>  4
    ),
    //redis配置
    "redis"     =>  array(
        "host"    =>  "127.0.0.1",
        "port"    =>  6379,
        "pwd"     =>  "1993918py",
        "db_index"=>  3
    ),
    //mysql配置
    "mysql"     =>  array(
        "host"       =>  "127.0.0.1",
        "port"       =>  3306,
        "user"       =>  "root",
        "pwd"        =>  "1993918py",
        "database"   =>  "im",
        "charset"    =>  "utf8"
    ),
    //聊天配置
    "im"        =>  array(
        "jwt_salt"      =>  "py_im",
        "jwt_iis"       =>  "py_im",
        "jwt_exp"       =>  2*60*60*24,
        "jwt_alg"       =>  "sha1",
    )

);