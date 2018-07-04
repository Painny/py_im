<?php
/**
 * Created by PhpStorm.
 * User: pengyu
 * Date: 2018/7/3
 * Time: 16:03
 */

//读取配置(支持多级读取)
function config($key)
{
    require_once __DIR__."/../config.php";
    global $CONFIG;
    var_dump($CONFIG);
    if(strpos($key,".")===false){
        return $CONFIG[$key];
    }
    $value=$CONFIG;
    foreach (explode(".",$key) as $item){
        $value=$value[$item];
    }
    return $value;
}

//生成用户登陆jwt凭证
function makeJwt($info)
{
    $header=array(
        "typ"   =>  "jwt",
        "alg"   =>  config("im.jwt_alg")
    );
    $playload=$info;
    $playload["iss"]="py_im";
    $playload["exp"]=time();

    $encode_str=base64_encode(json_encode($header)).".".base64_encode(json_encode($playload));
    $signature=sha1($encode_str.config("im.jwt_salt"));

    return $encode_str.".".$signature;
}

//解析验证jwt
function decodeJwt($jwt)
{
    $arr=explode(".",$jwt);
    if(count($arr)!=3){
        return false;
    }

    list($header_encode,$playload_encode,$signature)=$arr;

    //解析头信息
    $header_info=json_decode(base64_decode($header_encode),true);
    //加密方式
    $alg=$header_info["alg"];
    if(!in_array($alg,hash_algos())){
        return false;
    }

    //验证jwt完整性
    $endcode_str=$header_encode.".".$playload_encode;
    if(hash($alg,$endcode_str.config("im.jwt_salt")) != $signature){
        return false;
    }

    //解析负载数据
    $info=json_decode(base64_decode($playload_encode),true);
    //是否过期
    if($info["exp"]<=time()){
        return false;
    }

    return $info;
}

//生成响应数据
function makeMsg($type,$data,$code=0,$msg="")
{
    $msg=array(
        "type"  =>  $type,
        "data"  =>  $data,
        "code"  =>  $code?$code:0,
        "msg"   =>  $msg?$msg:"success"
    );
    return json_encode($msg);
}