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

    public function info($key=null)
    {
        if($key){
            return $this->$key;
        }
        return array(
            "id"        => $this->id,
            "name"      => $this->name,
            "userCount" => $this->userCount,
            "userList"  => $this->userList,
        );
    }

    //搜索群
    public static function search(DataBase $db,$name)
    {
        $name="%{$name}%";
        $info=$db->table("groups")->where("state=0 and name like ?",[$name])->field("id,name,userCount")->get();
        return $info;
    }

    private function init(DataBase $db,$id)
    {
        $info=$db->table("groups")->where("id=? and state=0",[$id])->field("name,userCount")->find();
        if(!isset($info["name"])){
            throw new Exception("群组不存在");
        }
        $this->name=$info["name"];
        $this->userCount=$info["userCount"];
        $this->onlineFd=[];
        $this->userList($db);
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

    static public function initAll(DataBase $db,Redis $redis)
    {
        $list=$db->table("groups")->where("state=0")->field("id")->get();
        foreach ($list as $group){
            $group=new self($db,$group["id"]);
            $group->save($redis);
        }
    }

    static public function getById(Redis $redis,$id)
    {
        $group=$redis->hGet("group_info",$id);
        if($group){
            $group=swoole_serialize::unpack($group);
        }
        return $group;
    }

    //群成员上线
    public function online(Redis $redis,$fd)
    {
        array_push($this->onlineFd,$fd);
        $this->save($redis);
    }

    //群成员下线
    public function offline(Redis $redis,$fd)
    {
        $index=array_search($fd,$this->onlineFd);
        if($index!==false){
            array_splice($this->onlineFd,$index,1);
            $this->save($redis);
        }
    }

    //在线成员fd
    public function onlineFd(){
        return $this->onlineFd;
    }

    //加入群
    public function join(DataBase $db,Redis $redis,$userInfo)
    {
        $fd=$userInfo["fd"];
        unset($userInfo["fd"]);
        //是否已加入
        if(in_array($userInfo,$this->userList)){
            return true;
        }
        $res=$db->table("group_user")->insert([
            "groupId"   =>  $this->id,
            "userId"    =>  $userInfo["id"]
        ]);
        if(!$res){
            return false;
        }
        $db->table("groups")->where("id=?",[$this->id])->incr("userCount");
        //更新群成员列表
        $this->userList[]=$userInfo;
        $this->userCount++;
        //上线
        $this->online($redis,$fd);
        return true;
    }

    //退出群
    public function quit(DataBase $db,Redis $redis,$userInfo)
    {
        $fd=$userInfo["fd"];
        unset($userInfo["fd"]);
        //是否是群成员
        if(!in_array($userInfo,$this->userList)){
            return true;
        }
        $res=$db->table("group_user")->where("groupId = ? and userId = ?",[$this->id,$userInfo["id"]])->update([
           "state"  =>  1
        ]);
        if(!$res){
            return false;
        }
        $db->table("groups")->where("id = ?",[$this->id])->decr("userCount");
        //更新成员列表
        $index=array_search($userInfo,$this->userList);
        array_splice($this->userList,$index,1);
        $this->userCount--;
        //从在线fd列表里删除
        $this->offline($redis,$fd);
        return true;
    }

}