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
            <input type="button" value="手动开局" class="normalButton" onclick="testStartScene();">
            <input type="button" value="发牌" class="normalButton">
            <input type="button" value="测试桌上牌面" class="normalButton" onclick="testBordPoker()">
            <input type="button" value="清空桌面" class="normalButton" onclick="testEmptyBord()">
        </div>
        <div id="board">
            <!--场景信息-->
            <?php if(isset($player)){ ?>
            <div id="scene">
                <dl>
                    <dt >当前场：</dt><dd><?php echo $placeId;?></dd>
                    <dt >当前次：</dt><dd><?php echo $sceneId;?></dd>
                    <dt >玩家人数：</dt><dd><?php echo count($players);?></dd>
                    <dt >围观人数：</dt><dd><?php echo count($players_hold);?></dd>
                    <dt >当前押注圈（ 0=未开始 1= 底牌圈 2=翻牌圈 3=转牌圈 4=河牌圈）：</dt><dd><?php echo $bettingRounds;?></dd>
                    <dt >当前奖池：</dt><dd id="jackpot"><?php echo $jackpot;?></span></dd>
                </dl>
                <div>
                    我的信息：
                    <font style="color:red;"><?php if(!empty($player))echo $player->fullName;?></font>
                    钱包：<?php echo $player->wallet?>
                    场地：<?php echo $player->currentPlaceId?>
                    场次：<?php echo $player->currentSceneId?>
                    在玩：<?php echo $player->isPlaying==TRUE?"是":"否"?>
                </div>
            </div>
            <?php } ?>
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
            <input type="button" value="坐下" class="normalButton">
            <input type="button" value="下注" class="normalButton" onclick="bid()">
        </div>
        <!--测试区域-->
        <div id="testArea">
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

    /**绑定扑克的点击操作**/
    $(document).ready(function(){
        //非桌面的扑克点击事件
        $("#testArea .pokerCard").click(function(){
            testPushPokerOnDesk(this);
        });
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

        //向服务器发送请求
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
        var fullname=prompt("请输入您的姓名","user1");

        //向服务器发送请求
        $.post("/index.php/Texas/login",{"fullname":fullname},function(result){
            $(checkResult).text(result);
        },"text");
    }

    /**进场**/
    function entryPlace(){
        //向服务器发送请求
        $.post("/index.php/Texas/entryPlace",null,function(result){
            $(checkResult).text(result);
        },"text");
    }

    /**点击手动开场 TODO 测试用**/
    function testStartScene(){
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

    /**下注**/
    function bid(){
        var bidMoney=prompt("请输入下流金额","10");

        //向服务器发送请求
        $.post("/index.php/Texas/bid",{"money":bidMoney},function(result){
            $(checkResult).text(result);
        },"json");
    }

    /**错误处理**/
    function showError(errorMessage){
        alert(errorMessage);
    }
</script>
</html>