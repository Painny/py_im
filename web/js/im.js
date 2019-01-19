
var Im={
    userId:null,
    token:null,
    ws:null,

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
        }
    },

    onClose:function () {
        console.log("断开连接...");
        $('#msg-box').css("border-color","red");
        Im.ws=null;
        Im.token=null;
        Im.userId=null;
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
        this.userId=data.info.id;
    }

};

