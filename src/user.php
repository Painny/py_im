<?php
/**
 * Created by PhpStorm.
 * User: pengyu
 * Date: 2018/7/3
 * Time: 15:38
 */

class User{
    private $id;        //用户id
    private $fd;        //连接标识
    private $nickname;  //昵称
    private $icon;      //头像

    public function __construct($fd,$data)
    {
        $this->fd=$fd;
        $this->id=$data["id"];
        $this->nickname=$data["nickname"];
        $this->icon=$data["icon"];

    }

    public function info()
    {
        return array(
            "id"        =>  $this->id,
            "fd"        =>  $this->fd,
            "nickname"  =>  $this->nickname,
            "icon"      =>  $this->icon,
        );
    }

    public function getFriends($db)
    {

    }

    public function getGroups($db)
    {

    }


}