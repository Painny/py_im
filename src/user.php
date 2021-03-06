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

    public function info($key=null)
    {
        if($key){
            return $this->$key;
        }
        return array(
            "id"        =>  $this->id,
            "fd"        =>  $this->fd,
            "nickname"  =>  $this->nickname,
            "icon"      =>  $this->icon,
        );
    }

    public static function search(DataBase $db,$name)
    {
        $name="%{$name}%";
        $info=$db->table("user")->where("nickname like ?",[$name])->get();
        return $info;
    }

    public function getFriends(DataBase $db)
    {
        if(isset($this->friends)){
            return $this->friends;
        }
        $tmpA=$db->table("friend")->join("user","friendId","=","id")
            ->field("user.id as id,nickname,icon")
            ->where("userId=? and state=0",[$this->id])->get();

        $tmpB=$db->table("friend")->join("user","userId","=","id")
            ->field("user.id as id,nickname,icon")
            ->where("friendId=? and state=0",[$this->id])->get();

        $this->friends=array_merge($tmpA,$tmpB);
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

    //通过用户id获取在线用户实例
    static public function getOnlineById(Redis $redis,$id)
    {
        $user=$redis->hGet("online_user",$id);
        if($user){
            $user=swoole_serialize::unpack($user);
        }
        return $user;
    }

    //通过用户连接fd获取在线用户实例
    static public function getOnlineByFd(Redis $redis,$fd)
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

    //加群
    public function joinGroup(DataBase $db,Redis $redis,Group $group)
    {
        $userInfo=$this->info();
        $result=$group->join($db,$redis,$userInfo);
        if($result){
            $groupInfo=$group->info();
            unset($groupInfo["userList"]);
            //更新群列表信息
            $this->groups[]=$groupInfo;
        }
        $this->save($redis);
        return $result;
    }

    //退群
    public function quitGroup(DataBase $db,Redis $redis,Group $group)
    {
        $userInfo=$this->info();
        $result=$group->quit($db,$redis,$userInfo);
        if($result){
            $groupInfo=$group->info();
            unset($groupInfo["userList"]);
            //更新群列表信息
            $index=array_search($groupInfo,$this->groups);
            array_splice($this->groups,$index,1);
        }
        $this->save($redis);
        return $result;
    }

    //通过id获取用户信息
    static public function getById(DataBase $db,$userId)
    {
        $user=$db->table("user")->where("id = ?",[$userId])->field("id,nickname,icon")->find();
        return $user;
    }

    //添加好友
    public function addUser(DataBase $db,Redis $redis,$userId)
    {
        //获取用户信息
        $user=self::getById($db,$userId);
        if(!$user){
            return false;
        }
        //是否已添加
        if(in_array($user,$this->friends)){
            return false;
        }
        $result=$db->table("friend")->insert([
            "userId"    =>  $this->id,
            "friendId"  =>  $userId
        ]);
        if(!$result){
            return false;
        }
        $this->friends[]=$user;
        $this->save($redis);
        return true;
    }



}