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
 * 群组已登录成员: group_{id}(set key)  [fd1,fd3]集合
 *
 *
 *
 *
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

            //每个工作进程分配单独数据库和redis连接
            $dsn='mysql:dbname='.config("mysql.database").';host='.config("mysql.host").';port='.
                config("mysql.port").';charset='.config("mysql.charset");

            $serv->db=new DataBase($dsn,config("mysql.user"),config("mysql.pwd"));

            $redis=new Redis();
            $redis->connect(config("redis.host"),config("redis.port"));
            $redis->auth(config("redis.pwd"));
            $redis->select(config("redis.db_index"));

            $serv->redis=$redis;
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
        $response=array("info"=>$user->info(),"friends"=>$user->getFriends($serv->db),"groups"=>$user->getGroups($serv->db));
        $response=makeMsg("connect",$response);
        $this->push($serv,[$req->fd],$response);

        //暂存用户实例
        $user->save($serv->redis);
    }

    public function onMessage(swoole_server $serv, swoole_websocket_frame $frame)
    {

    }

    public function onTask(swoole_server $serv,$task_id,$src_worker_id,$data)
    {
        if(!is_array($data) || isset($data["type"])){
            return;
        }
        switch ($data["type"]){
            case "push":
                break;
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
            "data"  =>  $msg
        );
        $serv->task($data);
    }


}