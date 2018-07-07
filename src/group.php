<?php
/**
 * Created by PhpStorm.
 * User: pengyu
 * Date: 2018/7/7
 * Time: 10:48
 */

class Group{
    private $id;
    private $name;
    private $userCount;
    private $userList;
    private $onlineFd;

    public function __construct(DataBase $db,$id)
    {
        $this->id=$id;
        $this->init($db,$id);
    }

    public function init(DataBase $db,$id)
    {
        $info=$db->table("groups")->where("id=? and state=0",[$id])->field("name,userCount")->find();
        if(!isset($info["name"])){
            throw new Exception("群组不存在");
        }
        $this->name=$info["name"];
        $this->userCount=$info["userCount"];
        $this->onlineFd=[];
        $this->userList();
    }

    public function userList(DataBase $db)
    {
        if($this->userList!==null){
            return $this->userList;
        }
        $this->userList=$db->table("group_user")->join("user","userId","=","id")->
        where("group_user.state=0 and groupId=?",[$this->id])->field("user.id as id,nickname,icon")->get();
        return $this->userList;
    }

    public function talkMsg($msg,$userId)
    {
        return array(
            "from"  =>  $this->id,
            "type"  =>  "group",
            "user"  =>  $userId,
            "msg"   =>  $msg,
            "time"  =>  date("Y/m/d H:i")
        );
    }

    public function save(Redis $redis)
    {
        $redis->hSet("group_info",$this->id,swoole_serialize::pack($this));
    }

    static public function allGroups(DataBase $db)
    {
        $list=$db->table("groups")->where("state=0")->field("id")->get();
        return $list;
    }

}