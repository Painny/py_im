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
    private $friends;   //好友列表
    private $groups;    //所在的群

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

    public function getFriends(DataBase $db)
    {
        if(isset($this->friends)){
            return $this->friends;
        }
        $this->friends=$db->table("friend")->join("user","friendId","=","id")->
            field("user.id as id,nickname,icon")->where("userId=? and state=0",[$this->id])->get();
        return $this->friends;
    }

    public function getGroups(DataBase $db)
    {
        if(isset($this->groups)){
            return $this->groups;
        }
        $this->groups=$db->table("groups")->join("group_user","id","=","groupId")->
            field("groups.id as id,name,userCount")->where("userId=? and state=0")->get();
    }


}