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
            <input type="button" value="发牌" class="normalButton">
            <input type="button" value="测试桌上牌面" class="normalButton" onclick="testBordPoker()">
            <input type="button" value="清空桌面" class="normalButton" onclick="testEmptyBord()">
        </div>
        <div id="board">
            <!--场景信息-->
            <div id="scene">
                当前场：xx厅
                玩家人数：<?php echo count($players);?>
            </div>
            <!--放扑克的位置-->
            <div id="pokersPlace">

            </div>
            <!--检测结题-->
            <div id="checkResult">

            </div>

        </div>
        <div id="playerArea">
            <input type="button" value="进场" class="normalButton">
            <input type="button" value="坐下" class="normalButton">
            <input type="button" value="下注" class="normalButton">
        </div>
        <div id="poker1" class="pokerRow">
            <input type="button" value="♦2" tag="17" class="pokerCard">
            <input type="button" value="♦3" tag="18" class="pokerCard">
            <input type="button" value="♦4" tag="19" class="pokerCard">
            <input type="button" value="♦5" tag="20" class="pokerCard">
            <input type="button" value="♦6" tag="21" class="pokerCard">
            <input type="button" value="♦7" tag="22" class="pokerCard">
            <input type="button" value="♦8" tag="23" class="pokerCard">
            <input type="button" value="♦9" tag="24" class="pokerCard">
            <input type="button" value="♦10" tag="25" class="pokerCard">
            <input type="button" value="♦J" tag="26" class="pokerCard">
            <input type="button" value="♦Q" tag="27" class="pokerCard">
            <input type="button" value="♦K" tag="28" class="pokerCard">
            <input type="button" value="♦A" tag="29" class="pokerCard">
        </div>
        <div id="poker2" class="pokerRow">
            <input type="button" value="♣2" tag="33" class="pokerCard">
            <input type="button" value="♣3" tag="34" class="pokerCard">
            <input type="button" value="♣4" tag="35" class="pokerCard">
            <input type="button" value="♣5" tag="36" class="pokerCard">
            <input type="button" value="♣6" tag="37" class="pokerCard">
            <input type="button" value="♣7" tag="38" class="pokerCard">
            <input type="button" value="♣8" tag="39" class="pokerCard">
            <input type="button" value="♣9" tag="40" class="pokerCard">
            <input type="button" value="♣10" tag="41" class="pokerCard">
            <input type="button" value="♣J" tag="42" class="pokerCard">
            <input type="button" value="♣Q" tag="43" class="pokerCard">
            <input type="button" value="♣K" tag="44" class="pokerCard">
            <input type="button" value="♣A" tag="45" class="pokerCard">
        </div>
        <div id="poker3" class="pokerRow">
            <input type="button" value="♥2" tag="65" class="pokerCard">
            <input type="button" value="♥3" tag="66" class="pokerCard">
            <input type="button" value="♥4" tag="67" class="pokerCard">
            <input type="button" value="♥5" tag="68" class="pokerCard">
            <input type="button" value="♥6" tag="69" class="pokerCard">
            <input type="button" value="♥7" tag="70" class="pokerCard">
            <input type="button" value="♥8" tag="71" class="pokerCard">
            <input type="button" value="♥9" tag="72" class="pokerCard">
            <input type="button" value="♥10" tag="73" class="pokerCard">
            <input type="button" value="♥J" tag="74" class="pokerCard">
            <input type="button" value="♥Q" tag="75" class="pokerCard">
            <input type="button" value="♥K" tag="76" class="pokerCard">
            <input type="button" value="♥A" tag="77" class="pokerCard">
        </div>
        <div id="poke4" class="pokerRow">
            <input type="button" value="♠2" tag="129" class="pokerCard">
            <input type="button" value="♠3" tag="130" class="pokerCard">
            <input type="button" value="♠4" tag="131" class="pokerCard">
            <input type="button" value="♠5" tag="132" class="pokerCard">
            <input type="button" value="♠6" tag="133" class="pokerCard">
            <input type="button" value="♠7" tag="134" class="pokerCard">
            <input type="button" value="♠8" tag="135" class="pokerCard">
            <input type="button" value="♠9" tag="136" class="pokerCard">
            <input type="button" value="♠10" tag="137" class="pokerCard">
            <input type="button" value="♠J" tag="138" class="pokerCard">
            <input type="button" value="♠Q" tag="139" class="pokerCard">
            <input type="button" value="♠K" tag="140" class="pokerCard">
            <input type="button" value="♠A" tag="141" class="pokerCard">
        </div>
    </form>
</body>

<script language="JavaScript">
    //桌面上的扑克
    var deskPokers = [];

    /**绑定扑克的点击操作**/
    $(document).ready(function(){
        //非桌面的扑克点击事件
        $(".pokerRow .pokerCard").click(function(){
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

    /**错误处理**/
    function showError(errorMessage){
        alert(errorMessage);
    }
</script>
</html>