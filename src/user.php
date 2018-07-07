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
            field("groups.id as id,name,userCount")->where("userId=? and group_user.state=0",[$this->id])->get();
        return $this->groups;
    }

    //暂存用户实例
    public function save(Redis $redis)
    {
        $redis->hSet("online_user",$this->id,swoole_serialize::pack($this));
        $redis->hSet("fd_user",$this->fd,$this->id);
    }

    static public function getById(Redis $redis,$id)
    {
        $user=$redis->hGet("online_user",$id);
        if($user){
            $user=swoole_serialize::unpack($user);
        }
        return $user;
    }

    static public function getByFd(Redis $redis,$fd)
    {
        $id=$redis->hGet("fd_user",$fd);
        if(!$id){
            return false;
        }
        $user=$redis->hGet("online_user",$id);
        if($user){
            $user=swoole_serialize::unpack($user);
        }
        return $user;
    }

    //生成单人聊天消息
    public function talkMsg($msg)
    {
        return array(
            "from"  =>  $this->id,
            "type"  =>  "user",
            "msg"   =>  $msg,
            "time"  =>  date("Y/m/d H:i")
        );
    }

    //用户是否在线
    static public function isOnline(Redis $redis,$id)
    {
        return $redis->hGet("online_user",$id)?true:false;
    }

    //暂存离线消息
    public function SaveOfflineMsg(DataBase $db,$toUserId,$msg)
    {
        $db->table("offline_msg")->insert(array(
            "fromUser"  =>  $this->id,
            "toUser"    =>  $toUserId,
            "msg"       =>  $msg,
            "time"      =>  time()
        ));
    }

    //获取离线消息
    public function getOfflineMsg(DataBase $db)
    {
        $msg=$db->table("offline_msg")->where("toUser=? and state=0",[$this->id])->field("fromUser,msg,time")->
        order("time")->get();
        $data=array();
        foreach ($msg as $key => $val){
            $data[$key]=array(
                "from"  =>  $val["fromUser"],
                "type"  =>  "user",
                "msg"   =>  $val["msg"],
                "time"  =>  date("Y/m/d H:i",$val["time"])
            );
        }
        return $data;
    }

    //上线的一些处理
    public function online(DataBase $db,Redis $redis)
    {
        foreach ($this->getGroups($db) as $val){
            $group=Group::getById($redis,$val["id"]);
            $group->online($redis,$this->fd);
        }
    }

    //下线的一些处理
    public function offline(DataBase $db,Redis $redis)
    {
        foreach ($this->getGroups($db) as $val){
            $group=Group::getById($redis,$val["id"]);
            $group->offline($redis,$this->fd);
        }
    }



}