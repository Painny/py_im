<?php
/**
 * Created by PhpStorm.
 * User: pengyu
 * Date: 2018/7/3
 * Time: 15:00
 */
/**
 * 所有在线用户: online_user(hash key) id => $user实例
 * 用户id和fd映射关系： fd_user(hash key) fd => id
 * 群组信息: group_info(hash key) id => $group实例
 *
 *********************************************************************
 *推送消息格式:["type":"msg","data":"xx","code":0,"msg":"xx"]
 *
 *单人聊天消息格式:type => msg, data => ["from":user_id,"type":"user","msg":"xx","time":"2018/07/06 16:00"]
 *群组连天消息格式:type => msg, data => ["from":group_id,"type":"group","user":user_id,"msg":"xx","time":"2018/07/06 16:00"]
 *
 * 连接成功消息格式:type => connect, data => ["info":自己信息,"friends":好友列表,"groups":群列表]
 * ********************************************************************
 * 接受消息格式:["type":"msg","data":"xx"]
 *
 * 聊天消息格式:type => msg, data => ["type":"user|group","to":user_id|group_id,"msg":"xx"]
 * 操作请求格式:type => act, data => ["act":"xx","data":"xx"]
 *
 */

//主服务文件
class Im{
    private $server;

    public function __construct($host,$port,$options=[])
    {
        $this->server=new swoole_websocket_server($host,$port);
        if($options && is_array($options)){
            $this->server->set($options);
        }

        $this->server->on("start",array($this,"onStart"));
        $this->server->on("workerStart",array($this,"onWorkerStart"));
        $this->server->on("open",array($this,"onOpen"));
        $this->server->on("message",array($this,"onMessage"));
        $this->server->on("task",array($this,"onTask"));
        $this->server->on("finish",array($this,"onFinish"));
        $this->server->on("close",array($this,"onClose"));

        $this->server->start();
    }

    public function onStart()
    {
        try{
            $redis=new Redis();
            $redis->connect(config("redis.host"),config("redis.port"));
            $redis->auth(config("redis.pwd"));
            $redis->select(config("redis.db_index"));
            $redis->flushDB();
        }catch (Exception $e){
            echo $e->getMessage();exit;
        }

    }

    public function onWorkerStart(swoole_server $serv, $worker_id)
    {
        try{
            require_once __DIR__."/helper.php";
            require_once __DIR__."/db.php";
            require_once __DIR__."/user.php";
            require_once __DIR__."/group.php";

            //每个工作进程分配单独数据库和redis连接
            $dsn='mysql:dbname='.config("mysql.database").';host='.config("mysql.host").';port='.
                config("mysql.port").';charset='.config("mysql.charset");

            $serv->db=new DataBase($dsn,config("mysql.user"),config("mysql.pwd"));

            $redis=new Redis();
            $redis->connect(config("redis.host"),config("redis.port"));
            $redis->auth(config("redis.pwd"));
            $redis->select(config("redis.db_index"));

            $serv->redis=$redis;
            if($worker_id==0){
                $serv->task(["type"=>"initGroups","data"=>null]);
            }
        }catch (Exception $e){
            echo $e->getMessage();exit;
        }

    }

    public function onOpen(swoole_websocket_server $serv, swoole_http_request $req)
    {
        $get=$req->get;
        $data=decodeJwt($get["token"]);
        if(!$data){
            $this->push($serv,[$req->fd],makeMsg("error",null,1,"缺少参数"));
            $serv->close($req->fd);
            return;
        }

        //返回基本信息，好友列表，群列表
        $user=new User($req->fd,$data);
        $response=array(
            "info"      =>  $user->info(),
            "friends"   =>  $user->getFriends($serv->db),
            "groups"    =>  $user->getGroups($serv->db),
            "msg"       =>  $user->getOfflineMsg($serv->db)
        );
        $response=makeMsg("connect",$response);
        $this->push($serv,[$req->fd],$response);

        //暂存用户实例
        $user->save($serv->redis);
        //上线处理
        $serv->task(array(
            "type"  =>  "online",
            "data"  =>  ["fd"=>$req->fd]
        ));
    }

    public function onMessage(swoole_server $serv, swoole_websocket_frame $frame)
    {
        $fromFd=$frame->fd;
        $msg=json_decode($frame->data,true);
        if(!isset($msg["type"]) || !isset($msg["data"])){
            $errInfo=makeMsg("error",null,1,"缺少参数");
            $this->push($serv,[$fromFd],$errInfo);
            return;
        }
        switch ($msg["type"]){
            case "msg":
                if($msg["data"]["type"]=="user"){  //单人消息
                    $user=User::getByFd($serv->redis,$fromFd);
                    if(!$user){
                        $errInfo=makeMsg("error",null,1,"发送者用户实例不存在");
                        $this->push($serv,[$fromFd],$errInfo);
                        return;
                    }
                    if(User::isOnline($serv->redis,$msg["data"]["to"])){
                        $toUser=User::getById($serv->redis,$msg["data"]["to"]);
                        $response=makeMsg("msg",$user->talkMsg($msg["data"]["msg"]));
                        $this->push($serv,[($toUser->info())["id"]],$response);
                    }else{  //存离线消息
                        $user->SaveOfflineMsg($serv->db,$msg["data"]["to"],$msg["data"]["msg"]);
                    }
                }else{  //群组消息

                }
                break;
            default:
        }

    }

    public function onTask(swoole_server $serv,$task_id,$src_worker_id,$data)
    {
        if(!is_array($data) || !isset($data["type"])){
            return;
        }
        switch ($data["type"]){
            case "push":  //推送群消息
                $this->pushTask($serv,$data["data"]);
                break;
            case "initGroups":  //初始化所有群
                Group::initAll($serv->db,$serv->redis);
                break;
            case "online":  //上线的一些处理
                $user=User::getByFd($serv->redis,$data["data"]["fd"]);
                $user->online($serv->db,$serv->redis);
                break;
            case "offline":  //下线的一些处理
                $user=User::getByFd($serv->redis,$data["data"]["fd"]);
                $user->offline($serv->db,$serv->redis);
                break;
            default:
        }
    }

    public function onFinish(swoole_server $serv, $task_id, $data)
    {

    }

    public function onClose(swoole_server $serv, $fd, $reactorId)
    {
        $user=User::getByFd($serv->redis,$fd);
        if($user){
            $serv->redis->hDel("online_user",($user->info())["id"]);
            unset($user);
        }
        $serv->redis->hDel("fd_user",$fd);
    }

    private function push(swoole_server $serv,$fds,$msg)
    {
        if(count($fds)==1){
            $serv->push($fds[0],$msg);
            return;
        }
        $data=array(
            "type"  =>  "push",
            "data"  =>  ["fds"=>$fds,"data"=>$msg]
        );
        $serv->task($data);
    }

    private function pushTask(swoole_server $serv,$data)
    {
        if(isset($data["fds"]) || !count($data["fds"])){
            return;
        }
        foreach ($data["fds"] as $fd){
            $serv->push($fd,$data["data"]);
        }
    }


}