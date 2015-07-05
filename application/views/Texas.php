<?php error_reporting(0) ?>
<!DOCTYPE html>
<html>
<head lang="en">
    <meta charset="UTF-8">
    <title></title>
    <script language="JavaScript" src="/public/js/jquery-2.1.4.min.js"></script>
    <style>
        #board{
            height:300px;
        }
        #bankerArea{
            margin-bottom: 20px;
        }
        .pokerCard{
            width: 50px;
            height: 70px;
            font-size: 20px;
            margin: 2px;
        }
        .normalButton{
            height: 25px;
            font-size: 18px;
        }
    </style>
</head>
<body>
    <form name="form1" action="/index.php/Texas" method="post" >
        <div id="bankerArea">
            庄家操作：
            <input type="button" value="开始新游戏" class="normalButton" onclick="newDeal();">
            <input type="button" value="发牌" class="normalButton">
            <input type="button" value="测试桌上牌面" class="normalButton" onclick="testBordPoker()">
            <input type="button" value="清空桌面" class="normalButton" onclick="testEmptyBord()">
        </div>
        <div id="board">
            <!--场景信息-->

            <div id="scene">
                <ul>
                    <li >当前场：<span id="placeId"><?php echo $placeId;?></span></li>
                    <li >当前次：<span id="sceneId"><?php echo $sceneId;?></span></li>
                    <li >当前押注圈（ 0=未开始 1= 底牌圈 2=翻牌圈 3=转牌圈 4=河牌圈）：<span id="bettingRounds"><?php echo $bettingRounds;?></span></li>
                    <li >当前奖池：<span id="jackpot"><?php echo $jackpot;?></span></li>
                </ul>
                <div>
                    我的信息：
                    <span id="fullName"><?php if(!empty($player))echo $player->fullName;?></span>
                    钱包：
                    <span id="wallet"><?php echo $player->wallet?></span>
                    在玩：
                    <span id="isPlaying"><?php echo $player->isPlaying==TRUE?"是":"否"?></span>
                </div>
            </div>

            <!--放扑克的位置-->
            <div id="pokersPlace">

            </div>
            <!--检测结题-->
            <div id="checkResult">

            </div>

        </div>
        <div id="playerArea">
            <input type="button" value="进场 entryPlace" class="normalButton" onclick="entryPlace()">
            <input type="button" value="登录 login" class="normalButton" onclick="login()">
            <input type="button" value="退出登录" class="normalButton" onclick="logout()">
            <input type="button" value="坐下" class="normalButton">
            <input type="button" value="下注" class="normalButton" onclick="bet()">
            <input type="button" value="完成下注" class="normalButton" onclick="finishBet()">
            <input type="button" value="刷新公共牌" class="normalButton" onclick="loadBoard()">
            <input type="button" value="刷新我的底牌" class="normalButton" onclick="loadMyPokers()">
        </div>
        <!--电脑的底牌-->
        <div id="computerPokers" class="pokerRow">
            <div>电脑的底牌</div>
            <div id="computerDesk"></div>
        </div>
        <!--测试区域-->
        <div id="testArea" style="display: none">
            <div id="poker1" class="pokerRow">
                <input type="button" value="♦2" tag="18" class="pokerCard">
                <input type="button" value="♦3" tag="19" class="pokerCard">
                <input type="button" value="♦4" tag="20" class="pokerCard">
                <input type="button" value="♦5" tag="21" class="pokerCard">
                <input type="button" value="♦6" tag="22" class="pokerCard">
                <input type="button" value="♦7" tag="23" class="pokerCard">
                <input type="button" value="♦8" tag="24" class="pokerCard">
                <input type="button" value="♦9" tag="25" class="pokerCard">
                <input type="button" value="♦10" tag="26" class="pokerCard">
                <input type="button" value="♦J" tag="27" class="pokerCard">
                <input type="button" value="♦Q" tag="28" class="pokerCard">
                <input type="button" value="♦K" tag="29" class="pokerCard">
                <input type="button" value="♦A" tag="30" class="pokerCard">
            </div>
            <div id="poker2" class="pokerRow">

                <input type="button" value="♣2" tag="34" class="pokerCard">
                <input type="button" value="♣3" tag="35" class="pokerCard">
                <input type="button" value="♣4" tag="36" class="pokerCard">
                <input type="button" value="♣5" tag="37" class="pokerCard">
                <input type="button" value="♣6" tag="38" class="pokerCard">
                <input type="button" value="♣7" tag="39" class="pokerCard">
                <input type="button" value="♣8" tag="40" class="pokerCard">
                <input type="button" value="♣9" tag="41" class="pokerCard">
                <input type="button" value="♣10" tag="42" class="pokerCard">
                <input type="button" value="♣J" tag="43" class="pokerCard">
                <input type="button" value="♣Q" tag="44" class="pokerCard">
                <input type="button" value="♣K" tag="45" class="pokerCard">
                <input type="button" value="♣A" tag="46" class="pokerCard">
            </div>
            <div id="poker3" class="pokerRow">
                <input type="button" value="♥2" tag="66" class="pokerCard">
                <input type="button" value="♥3" tag="67" class="pokerCard">
                <input type="button" value="♥4" tag="68" class="pokerCard">
                <input type="button" value="♥5" tag="69" class="pokerCard">
                <input type="button" value="♥6" tag="70" class="pokerCard">
                <input type="button" value="♥7" tag="71" class="pokerCard">
                <input type="button" value="♥8" tag="72" class="pokerCard">
                <input type="button" value="♥9" tag="73" class="pokerCard">
                <input type="button" value="♥10" tag="74" class="pokerCard">
                <input type="button" value="♥J" tag="75" class="pokerCard">
                <input type="button" value="♥Q" tag="76" class="pokerCard">
                <input type="button" value="♥K" tag="77" class="pokerCard">
                <input type="button" value="♥A" tag="78" class="pokerCard">
            </div>
            <div id="poke4" class="pokerRow">
                <input type="button" value="♠2" tag="130" class="pokerCard">
                <input type="button" value="♠3" tag="131" class="pokerCard">
                <input type="button" value="♠4" tag="132" class="pokerCard">
                <input type="button" value="♠5" tag="133" class="pokerCard">
                <input type="button" value="♠6" tag="134" class="pokerCard">
                <input type="button" value="♠7" tag="135" class="pokerCard">
                <input type="button" value="♠8" tag="136" class="pokerCard">
                <input type="button" value="♠9" tag="137" class="pokerCard">
                <input type="button" value="♠10" tag="138" class="pokerCard">
                <input type="button" value="♠J" tag="139" class="pokerCard">
                <input type="button" value="♠Q" tag="140" class="pokerCard">
                <input type="button" value="♠K" tag="141" class="pokerCard">
                <input type="button" value="♠A" tag="142" class="pokerCard">
            </div>
        </div>

        <!--我的底牌-->
        <div id="myPokers" class="pokerRow">
            <div>我的底牌</div>
            <div id="myDesk"></div>
        </div>
    </form>
</body>

<script language="JavaScript">
    //桌面上的扑克
    var deskPokers = [];
    var myPokers = [];
    var comPokers =[];

    /**绑定扑克的点击操作**/
    $(document).ready(function(){
        //非桌面的扑克点击事件
        $("#testArea .pokerCard").click(function(){
            testPushPokerOnDesk(this);
        });
        //尝试读取状态
        statusReport();
    });

    /**放置扑克到桌面上**/
    function testPushPokerOnDesk(pokerHElement){
        var tag= $(pokerHElement).attr("tag");
        var value= $(pokerHElement).val();
        $newPokerHElement = $(pokerHElement).clone();
        $("#pokersPlace").append($newPokerHElement);
        //加到桌面上的扑克数组
        deskPokers.push(tag);
    }

    /**测试桌面上的牌面**/
    function testBordPoker(){
        console.log(deskPokers);
        /** 测试我的牌面 **/

        $.post("/index.php/Texas/testBordPoker",{"pokers":deskPokers},function(result){
            $(checkResult).text(result);
        },"text");
    }

    /**清空桌面**/
    function testEmptyBord(){
        deskPokers = [];
        $("#pokersPlace").empty();
    }

    /**点击登录**/
    function login(){
        var fullname=prompt("请输入您的姓名","user2");

        //向服务器发送请求
        $.post("/index.php/Texas/login",{"fullname":fullname},function(result){
           if(result.status == "ok"){
                statusReport();
           }
        },"json");
    }

    /** 退出登录 **/
    function logout(){
        //向服务器发送请求
        $.post("/index.php/Texas/logout",null,function(){
            location.reload();
        },"text");
    }

    /**进场**/
    function entryPlace(){
        //向服务器发送请求
        $.post("/index.php/Texas/entryPlace",null,function(result){
            var statusReport = result.game;
            console.log(statusReport);
        },"json");
    }

    /**点击手动开场 TODO 测试用**/
    function newDeal(){
        //向服务器发送请求
        $.post("/index.php/Texas/newDeal",null,function(result) {
            //TODO 检测服务器返回是否异常
            var playersPoker  = result.game.playersPoker;

            for(var i = 0;i<playersPoker.length;i++){
                var poker = playersPoker[i];
                $("#myDesk").append('<input type="button" value="'+ poker.name +'" tag="'+ poker.num +'" class="pokerCard">');
            }

        },"json");
    }

    /** 获取状态设置游戏场景 **/
    function statusReport(){

        //向服务器发送请求
        $.post("/index.php/Texas/statusReport/false/true",null,function(result){
            var game = result.game;
            /* 场地信息类 */
            $("#placeId").html(game.placeId);
            $("#sceneId").html(game.sceneId);
            $("#bettingRounds").html(game.bettingRounds);
            $("#jackpot").html(game.jackpot);
            /* 游戏信息类 */
            //载入桌面公共牌
            var board =  result.game.board;
            for(var i=0;i<board.length;i++){
                var tag = board[i];
                var poker = $(".pokerCard[tag='"+ tag +"']").clone();
                $("#pokersPlace").append(poker);
                //加到桌面上的扑克数组
                deskPokers.push(tag);
            }

            //载入我的底牌
            var playerPoker =  result.game.playerPoker;
            for(var i=0;i<playerPoker.length;i++){
                var pokerCard = playerPoker[i];
                var pokerElement = $(".pokerCard[tag='"+ pokerCard.num +"']").clone();
                $("#myDesk").append(pokerElement);

            }

            //载入电脑的底牌
            var computerPoker =  result.game.computerPoker;
            for(var i=0;i<computerPoker.length;i++){
                var pokerCard = computerPoker[i];
                var pokerElement = $(".pokerCard[tag='"+ pokerCard.num +"']").clone();
                $("#computerDesk").append(pokerElement);
            }

            /* 玩家信息 */
            $("#fullName").html(game.player.fullName);
            $("#wallet").html(game.player.wallet);
            if(game.player.isPlaying == true){
                $("#isPlaying").html("是");
            }else{
                $("#isPlaying").html("否");
            }




        },"json");
    }

    /** 载入桌面公共牌 **/
    function loadBoard(){
        //向服务器发送请求
        $.post("/index.php/Texas/statusReport/board/true",null,function(result){
            var board =  result.game.board;
            for(var i=0;i<board.length;i++){
                var tag = board[i];
                var poker = $(".pokerCard[tag='"+ tag +"']").clone();
                $("#pokersPlace").append(poker);
                //加到桌面上的扑克数组
                deskPokers.push(tag);
            }

        },"json");
    }

    /** 载入我的底牌 **/
    function loadMyPokers(){
        $("#myDesk").empty();
        //向服务器发送请求
        $.post("/index.php/Texas/statusReport/playerPoker/true",null,function(result){
            var playerPoker =  result.game.playerPoker;
            for(var i=0;i<playerPoker.length;i++){
                var pokerCard = playerPoker[i];
                var pokerElement = $(".pokerCard[tag='"+ pokerCard.num +"']").clone();
                $("#myDesk").append(pokerElement);

            }

        },"json");

        //顺便载入电脑的底牌
        loadComputerPokers();
    }

    /** 载入电脑的底牌 **/
    function loadComputerPokers(){
        $("#computerDesk").empty();
        //向服务器发送请求
        $.post("/index.php/Texas/statusReport/computerPoker/true",null,function(result){
            var playerPoker =  result.game.computerPoker;
            for(var i=0;i<playerPoker.length;i++){
                var pokerCard = playerPoker[i];
                var pokerElement = $(".pokerCard[tag='"+ pokerCard.num +"']").clone();
                $("#computerDesk").append(pokerElement);

            }

        },"json");
    }
    /**下注**/
    function bet(){
        var bidMoney=prompt("请输入下流金额","10");

        //向服务器发送请求
        $.post("/index.php/Texas/bet",{"money":bidMoney},function(result){
            $(checkResult).text(result);
        },"json");
    }

    /**完成下注**/
    function finishBet(){
        //向服务器发送请求
        $.post("/index.php/Texas/finishBet",null,function(result){
            $(checkResult).text(result);

            //刷新状态
            //尝试读取状态
            statusReport();
        },"json");
    }

    /**错误处理**/
    function showError(errorMessage){
        alert(errorMessage);
    }
</script>
</html>