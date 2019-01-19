
var Im={

    ws:null,
    info:null,
    token:null,
    friends:null,
    groups:null,

    init:function (token) {
        this.token=token;
    },

    connect:function () {
        if(!this.token){
            alert("请登陆账号");
            return;
        }

        this.ws=new WebSocket("ws://120.79.61.205:9501/?token="+this.token);
        this.ws.onopen=this.onOpen;
        this.ws.onmessage=this.onMessage;
        this.ws.onclose=this.onClose;
        this.ws.onerror=this.onError;
    },

    send:function (data) {
        this.ws.send(JSON.stringify(data));
    },

    close:function () {
        this.ws.close();
    },

    onOpen:function () {
        console.log("连接系统成功...");
        $('#msg-box').css("border-color","green");
    },

    onMessage:function (event) {
        var data=event.data;
        console.log(data);
        data=JSON.parse(data);

        switch (data.type){
            case "connect":
                Im.selfInfo(data.data);
                break;
            case "msg"    :
                Im.showMsg(data.data);
        }
    },

    onClose:function () {
        console.log("断开连接...");
        $('#msg-box').css("border-color","red");

        Im.ws=null;
        Im.token=null;
        Im.userId=null;
        Im.friends=null;
        Im.groups=null;

        $('#user-img').attr("src",null);
        $('#user-nickname').text("");
    },

    onError:function () {
        console.log("系统错误...")
    },

    //展示自己信息
    selfInfo:function (data) {
        $('#user-img').attr("src",data.info.icon);
        $('#user-nickname').text(data.info.nickname);
        this.info=data.info;
        this.friends=data.friends;
        this.groups=data.groups;
    },

    //发送聊天信息
    sendMsg:function (type,id,msg) {
        var data={
            type:"msg",
            data:{
                "type":type,
                "to":id,
                "msg":msg
            }
        };
        this.send(data);

        var info={
            msg:msg,
            nickname:this.info.nickname,
            icon:this.info.icon
        };
        this.msgDom(info,true);
    },

    //展示聊天信息
    showMsg:function (data) {
        var info={
            msg:data.msg,
            nickname:"未知用户",
            icon:""
        };
        //私聊信息
        if(data.type == "user"){
            var user=this.getUser(data.from);
            if(user){
                info.nickname=user.nickname;
                info.icon=user.icon;
            }
        }
        this.msgDom(info,false);
    },

    //生成聊天信息dom
    msgDom:function (data,self) {
        var dom;
        if(self){
            dom='<div class="msg-box-right msg-box-line">' +
                '            <div class="msg-user-icon"></div>' +
                '            <div class="msg-user-message">' +
                '                <div class="msg-user-nickname">&nbsp;</div>' +
                '                <div class="msg-content">'+data.msg+'</div>' +
                '            </div>' +
                '            <div class="msg-user-icon">' +
                '                <img src="'+data.icon+'" />' +
                '           </div>' +
                '        </div>';
        }else{
            dom='<div class="msg-box-left msg-box-line">' +
                '            <div class="msg-user-icon">' +
                '                <img src="'+data.icon+'" />' +
                '            </div>' +
                '            <div class="msg-user-message">' +
                '                <div class="msg-user-nickname">'+data.nickname+'</div>' +
                '                <div class="msg-content">'+data.msg+'</div>' +
                '            </div>' +
                '            <div class="msg-user-icon"></div>' +
                '        </div>';
        }
        $('#msg-box').append(dom);
    },

    //通过用户id获取本地用户信息
    getUser:function (id) {
        for(var i in this.friends){
            if(this.friends[i].id == id){
                return this.friends[i];
            }
        }
        return false;
    }

};

