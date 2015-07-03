<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * 德州扑克游戏
 * Class Texas
 */
class Texas extends CI_Controller {


    /**
     * @var GameModel;
     */
    public $GameModel;

    /**
     * @var PlayerModel
     */
    public $PlayerModel;

    /**
     * @var PlaceModel;
     */
    public $PlaceModel;

    //当前在哪个场地
    private $placeId = 0;

    //当前牌局号（场次控制）
    private $sceneId = 0;

    //当前牌局玩家下注信息
    private $playersBetLogs = array();

    //当前押注圈 0=未开始 1= 底牌圈 2=翻牌圈 3=转牌圈 4=河牌圈
    private $BettingRounds =0;

    //当前局游戏历史
    private $gameHistory = array();

    //当前牌局彩池
    private $jackpot = 0;

    //庄家手中未发的牌
    private $bankerPoker = array();

    //台面  泛指桌上的五张公共牌
    private $board = array();

    //当前所有玩家（数组）
    private $players = array();

    //坐下但未进入游戏的玩家（数组）
    private $players_hold = array();

    //玩家手中的牌面
    private $playersPoker = array();

    /**
     * @var PlayerModel 电脑玩家对象
     */
    private $computerPlayer;


    public function __construct()
    {
        parent::__construct();
        $this->load->model('PlayerModel');
        $this->load->model('PlaceModel');
        $this->load->model('GameModel');
        $this->load->library('session');
        $this->load->driver('cache', array('adapter' => 'apc', 'backup' => 'file'));    //TODO windows下的memcached没安装成功，先用着这个

        //$this->cleanGame(); //TODO 清理所有缓存和session

        /**1、玩家信息（我）**/
        //获取session中当前登录用户id到
        $playerId = $this->session->userdata('playerId');

        if($playerId > 0){
            $this->PlayerModel->init($playerId);
        }else{
            return;
        }

        /**2、场地信息**/
        if(!$this->PlaceModel->init($this->PlayerModel)){
            return;
        }

        /**3、游戏信息**/
        if(!$this->GameModel->init($this->PlaceModel)){
            return;
        }
    }

    function __destruct()
    {

        //保存场地信息
        $this->PlaceModel->savePlace();
        //保存玩家信息
        $this->PlayerModel->savePlayer();
        //保存游戏信息
        $this->GameModel->saveGame();

    }

    /**
     * 清理游戏所有缓存和session
     * 注意：给调试用的
     */
    public function cleanGame(){
        $this->cache->clean();
        $this->session->sess_destroy();
    }


    public function index()
    {
        //输出场景数据
        $scene = NULL;
        if(isset($this->PlayerModel)){
            $scene =array(
                "placeId" =>$this->PlaceModel->placeId,                     //当前牌局号（场次控制）
                "sceneId" =>$this->PlaceModel->sceneId,                     //当前牌局号（场次控制）
                "placeStatus" => $this->PlaceModel->placeStatus,            //场地可用状态 1=可用 0=不可用
                "sceneStatus" => $this->PlaceModel->sceneStatus,            //场次状态 0=未开始 1=进行中 2=已结束
                "player" =>$this->PlaceModel->currentPlayer,                       //当前登录的玩家（我）信息
                "players" =>$this->PlaceModel->players,                     //当前场地所有玩家（数组）
                "players_hold" =>$this->PlaceModel->players_hold,           //围观中的玩家
                "playersBetLogs" => $this->PlaceModel->playersBetLogs,      //当前牌局玩家下注信息
                "bettingRounds" => $this->GameModel->bettingRounds,      //当前押注圈 0=未开始 1= 底牌圈 2=翻牌圈 3=转牌圈 4=河牌圈
                "jackpot" => $this->GameModel->jackpot,      //当前牌局彩池
                "board" => $this->GameModel->board,                        //台面  泛指桌上的五张公共牌
            );
        }

        $this->load->view('texas',$scene);
    }

    /**
     * 登录，默认登录后马上坐下（进入游戏玩家池）
     */
    public function login(){
        $fullname = $this->input->post("fullname");
        $this->load->model('PlaceModel');
        $result = $this->PlayerModel->do_login($fullname);

        if($result == TRUE){
            //保存session
            $this->session->set_userdata('playerId',$this->PlayerModel->playerId);
            $this->processResult(null,"ok",null);
        }else{
            $this->processResult(null,"error",null);
        }

    }

    /**
     * 退出登录
     */
    public function logout(){
        //TODO 退出登录
    }



    /**
     * 进场
     * 注意：未登录的用户不能进场
     */
    public function entryPlace(){
        if($this->PlayerModel->playerId > 0){
            $this->PlaceModel->assignPlace();
            $this->processResult($this->PlayerModel);
        }

    }

    /**
     * 进入游戏玩家池
     * 必须是在牌局未开始前
     * @param CPlayer $player
     */
    function addPlayer(CPlayer $player){
        $this->players[$player->playerId] = $player;
        //保存当前牌局的所有用户到缓存中
        $this->cache->save("scene_".$this->sceneId."_players",$this->players,3600);
    }

    /**
     * 进入等待池
     * @param CPlayer $player
     */
    function addHoldPlayer(CPlayer $player){
        /**退出（清除）游戏玩家池**/
        unset($this->players[$player->playerId]);
        //保存当前牌局的所有用户到缓存中
        $this->cache->save("scene_".$this->sceneId."_players",$this->players,3600);
    }

    /**
     * 玩家坐下
     */
    public function playerSeat(){

    }

    /**
     * 测试桌面上的牌面
     */
    public function testBordPoker(){
        error_reporting(0); //抑制错误输出
        $this->benchmark->mark('code_start');   //基准测试开始
        $testPokers = $this->input->post("pokers");

        //检测牌数量是否大于2张
        if(count($testPokers) <2 ) {
            $this->benchmark->mark('code_end');
            echo " 用时：".$this->benchmark->elapsed_time('code_start', 'code_end');
            exit("牌数量不足");
        }

        //检测牌面是否【同花大顺】
        $isRoyalFlush = $this->isRoyalFlush($testPokers);
        if($isRoyalFlush["result"] == true){
            echo "同花大顺";
            $this->benchmark->mark('code_end');
            echo " 用时：".$this->benchmark->elapsed_time('code_start', 'code_end');
            exit();
        }

        //检测牌面是否【同花顺】
        $isStraightFlush = $this->isStraightFlush($testPokers);
        if($isStraightFlush["result"] == true){
            echo "同花顺";
            $this->benchmark->mark('code_end');
            echo " 用时：".$this->benchmark->elapsed_time('code_start', 'code_end');
            exit();
        }

        //检测牌面是否【四条】
        $isFourOfaKind = $this->isFourOfaKind($testPokers);
        if($isFourOfaKind["result"] == true){
            echo "四条";
            $this->benchmark->mark('code_end');
            echo " 用时：".$this->benchmark->elapsed_time('code_start', 'code_end');
            exit();
        }

        //检测牌面是否【满堂红】
        $isFullHouse = $this->isFullHouse($testPokers);
        if($isFullHouse["result"] == true){
            echo "满堂红";
            $this->benchmark->mark('code_end');
            echo " 用时：".$this->benchmark->elapsed_time('code_start', 'code_end');
            exit();
        }

        //检测牌面是否【同花】
        $isFlush = $this->isFlush($testPokers);
        if($isFlush["result"] == true){
            echo "同花";
            $this->benchmark->mark('code_end');
            echo " 用时：".$this->benchmark->elapsed_time('code_start', 'code_end');
            exit();
        }

        //检测牌面是否【顺子】
        $isStraight = $this->isStraight($testPokers);
        if($isStraight["result"] == true){
            echo "顺子";
            $this->benchmark->mark('code_end');
            echo " 用时：".$this->benchmark->elapsed_time('code_start', 'code_end');
            exit();
        }

        //检测牌面是否【三条】
        $isThreeOfaKind = $this->isThreeOfaKind($testPokers);
        if($isThreeOfaKind["result"] == true){
            echo "三条";
            $this->benchmark->mark('code_end');
            echo " 用时：".$this->benchmark->elapsed_time('code_start', 'code_end');
            exit();
        }

        //检测牌面是否【两对】
        $isTwoPairs = $this->isTwoPairs($testPokers);
        if($isTwoPairs["result"] == true){
            echo "两对";
            $this->benchmark->mark('code_end');
            echo " 用时：".$this->benchmark->elapsed_time('code_start', 'code_end');
            exit();
        }

        //检测牌面是否【一对】
        $isOnePair = $this->isOnePair($testPokers);
        if($isOnePair["result"] == true){
            echo "一对";
            $this->benchmark->mark('code_end');
            echo " 用时：".$this->benchmark->elapsed_time('code_start', 'code_end');
            exit();
        }

        //检测牌面是否【高牌】
        $isHighCard = $this->isHighCard($testPokers);
        if($isHighCard["result"] == true){
            echo "高牌";
            $this->benchmark->mark('code_end');
            echo " 用时：".$this->benchmark->elapsed_time('code_start', 'code_end');
            exit();
        }


    }

    /**
     * A 开始新牌局
     */
    public function newDeal(){
        //TODO 需要增加牌局控制的逻辑，只有未开始或已经结束的牌局才能开始新牌局

        //生成新的扑克牌（4 x 13 的二维数组）
        /**
         * 采用二进制位来表示牌面和花色
         * 黑桃 = 128 = 1000 0000
         * 红桃 = 64  = 0100 0000
         * 梅花 = 32  = 0010 0000
         * 方块 = 16  = 0001 0000
         *
         * 后四位按顺序表面牌值，并与前四位组合
         * 黑桃 2 = 128 + 1 = 1000 0010
         * 1 与 ace 在检测顺子的情况下相等
         * 依此类推
         */
        $newPoker = array(
            17,18,19,20,21,22,23,24,25,26,27,28,29,30,                 //方块 1 - A
            33,34,35,36,37,38,39,40,41,42,43,44,45,46,                 //梅花 1 - A
            65,66,67,68,69,70,71,72,73,74,75,76,77,78,                 //红桃 1 - A
            129,130,131,132,133,134,135,136,137,138,139,140,141,142     //黑桃 1 - A
        );

        //随机生成庄家手中的牌序
        shuffle($newPoker);
        $this->bankerPoker = $newPoker;

        //向所有玩家发放底牌
        $this->preFlop();

    }

    /**
     * 响应用户端请求进行状态汇报
     * 汇报内容如下：
     * #### 场地信息类 ####
     * 1、所在场地id spaceId
     * 3、场地状态 placeStatus 场地可用状态 1=可用 0=不可用
     * 2、当前场次id sceneId
     * 3、场次状态 sceneStatus 0=未开始 1=进行中 2=已结束
     * 4、其它玩家 players 数组
     *
     * #### 游戏信息类 ####
     * 5、当前押注圈 BettingRounds
     * 6、押注信息 playersBetLogs
     * 7、当前牌局彩池 jackpot
     * 8、当前局游戏历史
     */
    public function statusReport(){

    }

    /**
     * 玩家下注
     */
    public function bid(){
        $money = $this->input->post("money");
        $this->GameModel->bid($this->PlayerModel,$money);
        echo json_encode($money);
    }

    public function finishBid(){
        $this->GameModel->finishBid();
    }


    /**
     * C 押注圈 - 每一个牌局可分为四个押注圈。每一圈押注由按钮（庄家）左侧的玩家开始叫注。
     */
    public function bettingRounds(){

    }

    /**
     * D 翻牌圈 - 首三张公共牌出现以后的押注圈
     * 此轮出三张牌
     */
    public function flopRound(){
        //TODO 判断当前台面上有多少张牌
        //向台面发出三张公共牌

    }


    /**
     * 将数值转换为牌面描述
     * 测试地址 http://cidemo.my.com/index.php/Texas/numberToPokerFace/141
     * @param $num
     * @return string
     */
    public function numberToPokerFace($num){
        $flowerType = ""; //花色
        //花色计算
        $flower = $num >> 4;
        switch($flower){
            case 1:
                $flowerType="♦";
                break;
            case 2:
                $flowerType="♣";
                break;
            case 4:
                $flowerType="♥";
                break;
            case 8:
                $flowerType="♥";
                break;
        }

        //牌值计算
        $pokerNum =  $num & 15; //高四位置0
        if($pokerNum<10){
            $pokerNum += 1;
        }else{
            switch($pokerNum){
                case 10:
                    $pokerNum = "J";
                    break;
                case 11:
                    $pokerNum = "Q";
                    break;
                case 12:
                    $pokerNum = "K";
                    break;
                case 13:
                    $pokerNum = "A";
                    break;
            }
        }

        //返回牌面
        return $flowerType . $pokerNum;
    }


    /**
     * 检测牌面是否【同花大顺】
     * @param $pokers array 共7张牌
     * @return array|bool 返回的数组结构
     * {
     *      "confirms":[67,68,69,70,71],       //命中的同花顺牌
     *      "result":true                      //是否命中
     * }
     */
    function isRoyalFlush($pokers){
        //TODO 测试
        /*
        $pokers = $_GET["pokers"];
        if(!empty($pokers)){
            $pokers = explode(",",$pokers);
        }else{
            $pokers=array(23,24,41,42,43,44,45);
        }
        */

        /**规则： 花色相同 + 连号 + 有一张A **/

        /**可能的组合**/
        $possible1 = array(26,27,28,29,30);
        $possible2 = array(42,43,44,45,46);
        $possible3 = array(74,75,76,77,78);
        $possible4 = array(138,139,140,141,142);

        $p1 = array_values(array_intersect($pokers,$possible1));
        if(count($p1) == 5){
            $returnArray = array(
                "confirms"=>$p1,
                "result" => true
            );
            return $returnArray;
        }
        $p2 = array_values(array_intersect($pokers,$possible2));
        if(count($p2) == 5){
            $returnArray = array(
                "confirms"=>$p2,
                "result" => true
            );
            return $returnArray;
        }
        $p3 = array_values(array_intersect($pokers,$possible3));
        if(count($p3) == 5){
            $returnArray = array(
                "confirms"=>$p3,
                "result" => true
            );
            return $returnArray;
        }
        $p4 = array_values(array_intersect($pokers,$possible4));
        if(count($p4) == 5){
            $returnArray = array(
                "confirms"=>$p4,
                "result" => true
            );
            return $returnArray;
        }

        $returnArray = array(
            "confirms"=>null,
            "result" => false
        );

        return $returnArray;
    }

    /**
     * 检测牌面是否【同花顺】
     * @param $pokers
     * @return array
     */
    function isStraightFlush($pokers){
        //TODO 测试
        //if($pokers == null) $pokers=array(17,34,68,67,69,71,70);
        //有Ace的情况下，Ace当1
        if(in_array(30,$pokers)) $pokers[] = "17";
        if(in_array(46,$pokers)) $pokers[] = "33";
        if(in_array(78,$pokers)) $pokers[] = "65";
        if(in_array(142,$pokers)) $pokers[] = "129";

        /**规则： 花色相同 + 连号**/
        $pokerTemp = array();   //用于临时存放计算
        /**第一轮：抽取所有花色相同的牌**/
        foreach($pokers as $poker){
            if(($poker & 16) > 0){
                //方块
                $pokerTemp[16][] = $poker;
            }elseif(($poker & 32) > 0){
                //梅花
                $pokerTemp[32][] = $poker;
            }elseif(($poker & 64) > 0){
                //黑桃
                $pokerTemp[64][] = $poker;
            }elseif(($poker & 128) > 0){
                //黑桃
                $pokerTemp[128][] = $poker;
            }
        }


        /**第二轮：计算同一花色中有无达到5张的**/
        $possibleTemp = array();  //有可能是同花顺的，可进入第三轮
        $confirms = array();      //确定为同花顺的牌
        foreach($pokerTemp as $temp){
            if(count($temp) >= 5){
                $possibleTemp = $temp;
            }
        }
        if(empty($possibleTemp)) return false;

        /**第三轮：连号计算**/
        // 5个数连接计算公式 (n1+n2+n3+n4+n5)-(min(n)-1)*5 == 15 ? true : false ;

        //将数组进行顺序
        sort($possibleTemp);

        //考虑到有可能超过5个达到7个的情况，需要进行3次计算
        $result = false;
        for($i=0;$i<3;$i++){
            if($result == false){
                //取5个
                $temp = array();
                for($j=$i;$j<$i+5;$j++){
                    $temp[] = $possibleTemp[$j];
                }
                $min = $temp[0];    //最小值
                $max = $temp[count($temp) - 1];      //最大值
                //连号情况下最大与最小差4
                $chckeDiff = $max - $min;
                if($chckeDiff ===4 ){
                    $result = (array_sum($temp)- ($min-1)*5) === 15 ? true : false;
                    if($result == true) $confirms = $temp;
                }

            }

        }

        $returnArray = array(
            "confirms"=>$confirms,
            "result" => $result
        );
        //echo json_encode($returnArray);
        return $returnArray;
    }

    /**
     * 检测牌面是否【四条】
     * @param $pokers
     * @return array
     */
    function isFourOfaKind($pokers){
        //TODO 测试
        //$pokers=array(18,34,66,130,38,24,27);

        //去除高四位
        $newPokers = array();
        $pokersGroup = array(); //以低四位进行分组
        foreach($pokers as $poker){
            $low4 = $poker & 15;
            $newPokers[$poker] = $low4;
            $pokersGroup[$low4][]=$poker;
        }

        //判断牌中是否有4张一样的
        $confirms = array();      //确定为【四条】的牌
        $result = false;
        foreach($pokersGroup as $group){
            if($result == false){
                if(count($group) >= 4){
                    $confirms = $group;
                    $result = true;
                }
            }
        }

        //返回
        $returnArray = array(
            "confirms"=>$confirms,
            "result" => $result
        );
        //echo json_encode($returnArray);
        return $returnArray;

    }

    /**
     * 检测牌面是否【满堂红】 三张同一点数的牌，加一对其他点数的牌。
     * @param $pokers
     * @return array
     */
    function isFullHouse($pokers){
        //TODO 测试
        //$pokers=array(18,34,66,26,42,28,77);

        //去除高四位
        $newPokers = array();
        $pokersGroup = array(); //以低四位进行分组
        foreach($pokers as $poker){
            $low4 = $poker & 15;
            $newPokers[$poker] = $low4;
            $pokersGroup[$low4][]=$poker;
        }

        //判断牌中是否有3张一样的
        $confirms3 = array();      //确定为有三张的牌
        $confirms2 = array();      //确定为有两张的牌
        $result = false;
        foreach($pokersGroup as $group){
            if($result == false){
                if(count($group) === 3){
                    $confirms3 = $group;
                }elseif(count($group) === 2){
                    $confirms2 = $group;
                }
                if(!empty($confirms2) && !empty($confirms3)){
                    $result = true;
                }
            }
        }



        //返回
        $returnArray = array(
            "confirms"=>array_merge($confirms3 ,$confirms2),
            "result" => $result
        );
        //echo json_encode($returnArray);
        return $returnArray;
    }

    /**
     * 检测牌面是否【同花】
     * 同花（Flush，简称“花”：五张同一花色的牌。
     * @param $pokers array
     * @return array
     */
    function isFlush($pokers){
        //TODO 测试
        //$pokers=array(17,19,20,22,27,28,29);

        //以花色（高四位）作为分组
        $group = array();
        foreach($pokers as $poker){
            if(($poker & 128) > 0){
                $group[128][] = $poker;
            }elseif(($poker & 64) > 0){
                $group[64][] = $poker;
            }elseif(($poker & 32) > 0){
                $group[32][] = $poker;
            }elseif(($poker & 16) > 0){
                $group[16][] = $poker;
            }
        }

        //分析是否有五张或以上同一花色的牌
        $result = false;
        $confirms = array();
        foreach($group as $pokerItems){
            if(count($pokerItems) >= 5){
                $result = true;
                $confirms = $pokerItems;
            }
        }

        //返回
        $returnArray = array(
            "confirms"=>$confirms,
            "result" => $result
        );
        //echo json_encode($returnArray);
        return $returnArray;
    }

    /**
     * 检测牌面是否【顺子】
     * @param $pokers
     * @return array
     */
    function isStraight($pokers){
        //TODO 测试
        //$pokers=array(18,35,68,133,22,76,34);
        //有Ace的情况下，Ace当1
        if(in_array(30,$pokers)) $pokers[] = "17";
        if(in_array(46,$pokers)) $pokers[] = "33";
        if(in_array(78,$pokers)) $pokers[] = "65";
        if(in_array(142,$pokers)) $pokers[] = "129";

        //去除高四位，并忽略低位值相同的，如 2 3 4 4 5 6
        $newPokers = array();
        foreach($pokers as $poker){
            $low4 = $poker & 15;
            if(!in_array($low4,$newPokers)){
                $newPokers[$poker] = $low4;
            }
        }

        $confirms = array();      //确定为顺子的牌
        /**第三轮：连号计算**/
        // 5个数连接计算公式 (n1+n2+n3+n4+n5)-(min(n)-1)*5 == 15 ? true : false ;
        //将数组进行顺序
        asort($newPokers);

        //考虑到有可能超过5个达到7个的情况，需要进行3次计算
        $result = false;
        $newPokersKeys = array_keys($newPokers);    //获取键值
        for($i=0;$i<3;$i++){
            if($result == false){
                //取5个
                $temp = array();
                for($j=$i;$j<$i+5;$j++){
                    $key = $newPokersKeys[$j];
                    $temp[$key] = $newPokers[$key];
                }
                $min = $temp[$newPokersKeys[$i]];    //最小值
                $max = $temp[$newPokersKeys[4 + $i]];      //最大值

                //连号情况下最大与最小差4
                $chckeDiff = $max - $min;
                if($chckeDiff ===4 ){
                    $result = (array_sum($temp)- ($min-1)*5) === 15 ? true : false;
                    if($result == true){
                        //替换回原来的牌(有高四位的)
                        $confirms = array_keys($temp);

                    }
                }
            }

        }

        //返回
        $returnArray = array(
            "confirms"=>$confirms,
            "result" => $result
        );
        //echo json_encode($returnArray);
        return $returnArray;
    }

    /**
     * 检测牌面是否【三条】
     * 有三张同一点数的牌
     * @param $pokers
     * @return array
     */
    function isThreeOfaKind($pokers){
        //TODO 测试
        //$pokers=array(18,34,66,131,38,24,27);

        //去除高四位
        $newPokers = array();
        $pokersGroup = array(); //以低四位进行分组
        foreach($pokers as $poker){
            $low4 = $poker & 15;
            $newPokers[$poker] = $low4;
            $pokersGroup[$low4][]=$poker;
        }

        //判断牌中是否有3张一样的
        $confirms = array();      //确定为【三条】的牌
        $result = false;
        foreach($pokersGroup as $group){
            if($result == false){
                if(count($group) >= 3){
                    $confirms = $group;
                    $result = true;
                }
            }
        }

        //返回
        $returnArray = array(
            "confirms"=>$confirms,
            "result" => $result
        );
        //echo json_encode($returnArray);
        return $returnArray;
    }

    /**
     * 检测牌面是否【两对】
     * @param $pokers
     * @return array
     */
    function isTwoPairs($pokers){
        //TODO 测试
        //$pokers=array(18,34,67,26,42,28,77);

        //去除高四位
        $newPokers = array();
        $pokersGroup = array(); //以低四位进行分组
        foreach($pokers as $poker){
            $low4 = $poker & 15;
            $newPokers[$poker] = $low4;
            $pokersGroup[$low4][]=$poker;
        }

        //判断牌中是否有2对一样的
        $confirms = array();      //确定为有两对牌
        $result = false;
        foreach($pokersGroup as $group){
            if($result == false){
                if(count($group) === 2){
                    $confirms[] = $group;
                }
                if(count($confirms) === 2 ){
                    $result = true;
                }
            }
        }

        //返回
        $returnArray = array(
            "confirms"=>$confirms,
            "result" => $result
        );
        //echo json_encode($returnArray);
        return $returnArray;
    }

    /**
     * 检测牌面是否【一对】
     * @param $poker
     * @return array
     */
    function isOnePair($pokers){
        //TODO 测试
        //$pokers=array(18,34,67,26,42,28,77);

        //去除高四位
        $newPokers = array();
        $pokersGroup = array(); //以低四位进行分组
        foreach($pokers as $poker){
            $low4 = $poker & 15;
            $newPokers[$poker] = $low4;
            $pokersGroup[$low4][]=$poker;
        }

        //判断牌中是否有1对一样的
        $confirms = array();      //确定为有一对的牌
        $result = false;
        foreach($pokersGroup as $group){
            if($result == false){
                if(count($group) === 2){
                    $confirms = $group;
                    $result = true;
                }
            }
        }

        //返回
        $returnArray = array(
            "confirms"=>$confirms,
            "result" => $result
        );
        //echo json_encode($returnArray);
        return $returnArray;
    }


    /**
     * 检测牌面是否【高牌】
     * 注：此函数需在以上牌面检测完后才使用，因为这个函数不参与检测，只返回最高点数
     * 不符合上面任何一种牌型的牌型，由单牌且不连续不同花的组成，以点数决定大小
     * @param $pokers
     * @return array
     */
    function isHighCard($pokers){
        //TODO 测试
        //$pokers=array(140,18,26,27,33,38,40);
        sort($pokers);
        $maxPoker = array_pop($pokers);

        //返回
        $returnArray = array(
            "confirms"=>$maxPoker,
            "result" => true
        );
        //echo json_encode($returnArray);
        return $returnArray;
    }

    /**
     * 处理并返回资料
     * @param $data
     * @param string $status
     * @param null $error
     */
    function processResult($data,$status="ok",$error=null){
        $returnData=array(
            "game" => $data,
            "status" => $status,
            "error" => $error,
            "debug" => array(),
            "user" => $this->PlayerModel
        );
        echo json_encode($returnData);
        exit();
    }

}
