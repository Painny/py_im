<?php
/**
 * Created by PhpStorm.
 * User: pengyu
 * Date: 2018/7/3
 * Time: 15:00
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

        $this->server->on("open",array($this,"onOpen"));
        $this->server->on("message",array($this,"onMessage"));
        $this->server->on("task",array($this,"onTask"));
        $this->server->on("finish",array($this,"onFinish"));
        $this->server->on("close",array($this,"onClose"));

        $this->server->start();
    }

    private function onOpen(swoole_websocket_server $serv, swoole_http_request $req)
    {
        $get=$req->get;
        $data=decodeJwt($get["token"]);
        if(!$data){
            $serv->close($req->fd);
            return;
        }
        $user=new User($req->fd,$data);
    }

    private function onMessage(swoole_server $serv, swoole_websocket_frame $frame)
    {

    }

    private function onTask(swoole_server $serv,$task_id,$src_worker_id,$data)
    {

    }

    private function onFinish(swoole_server $serv, $task_id, $data)
    {

    }

    private function onClose(swoole_server $server, $fd, $reactorId)
    {

    }

}