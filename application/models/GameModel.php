<?php
/**
 * Created by PhpStorm.
 * 游戏模型
 * User: user1011
 * Date: 2015/7/2
 * Time: 12:48
 */

class GameModel extends CI_Model{

    /**
     * @var PlaceModel 场地类
     */
    private $place;

    //当前押注圈 0=未开始 1= 底牌圈 2=底牌圈已下注 3=翻牌圈 4=翻牌圈已下注 5=转牌圈 6=转牌圈已下注 7=河牌圈 8=河牌圈已下注 9=显示结果和发奖金 10=游戏结束
    public $bettingRounds =0;

    /**
     * @var array 当前局游戏历史
     */
    public $gameHistory = array();

    /**
     * @var int 当前牌局彩池
     */
    public $jackpot = 0;

    /**
     * @var array 庄家手中未发的牌
     */
    public $bankerPoker = array();

    /**
     * @var array 台面  泛指桌上的五张公共牌
     */
    public $board = array();

    /**
     * @var array 玩家手中的牌面
     */
    public $playersPoker = array();

    /**
     * @var array 玩家押注历史信息
     */
    public $betLog = array();

    /**
     * @var array 弃牌的玩家ID
     */
    public $foldPlayer = array();

    /**
     * @var int 当前游戏赢家获得的奖金数量
     */
    public $winnerBonus = 0;

    function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->driver('cache', array('adapter' => 'apc', 'backup' => 'file'));
    }

    /**
     * 通过场地信息初始化游戏类
     * @param PlaceModel $placeModel
     */
    public function init(PlaceModel &$placeModel){
        $this->place = $placeModel;
        if($placeModel->placeId >0){
            //读取缓存中当前游戏信息
            $gameInfo = $this->cache->get("game_".$placeModel->placeId."_".$placeModel->sceneId);

            //如果有缓存则读取缓存（表明有正在进行的游戏），如果没有则初始化
            if($gameInfo instanceof GameModel){
                $this->bettingRounds = $gameInfo->bettingRounds;
                $this->gameHistory = $gameInfo->gameHistory;
                $this->jackpot = $gameInfo->jackpot;
                $this->bankerPoker = $gameInfo->bankerPoker;
                $this->board = $gameInfo->board;
                $this->playersPoker = $gameInfo->playersPoker;
                $this->betLog = $gameInfo->betLog;
                $this->winnerBonus = $gameInfo->winnerBonus;
            }

            //如果可进行游戏则马上开始
            if($this->canPlay() == TRUE) $this->newGame();

            return TRUE;
        }

        return FALSE;
    }

    /**
     * 缓存游戏信息
     */
    function saveGame(){
        if(isset($this->place)){
            $this->cache->save("game_".$this->place->placeId."_".$this->place->sceneId,$this,3600);
        }
    }


    /**
     * 是否可以开始游戏
     * TODO 如果游戏玩家少于1人，则强行中止游戏
     * @return bool
     */
    public function canPlay(){
        if($this->bettingRounds ===0){
            return TRUE;
        }
        return FALSE;
    }

    /**
     * 开始新的游戏
     */
    function newGame(){
        //清理奖池
        $this->jackpot = 0;

        //清理桌面（公共牌和玩家牌）
        $this->bankerPoker = array();
        $this->playersPoker = array();
        $this->board = array();

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

        //设置玩家在玩的状态
        $this->place->currentPlayer->isPlaying = true;

        //设置当前游戏进入【底牌圈】
        $this->bettingRounds = 1;
    }

    /**
     * B 底牌圈 / 前翻牌圈 - 公共牌出现以前的第一轮叫注。
     */
    public function preFlop(){
        //TODO 检测游戏人数是否大于1人
        //初始化玩家手上的牌
        $this->playersPoker = array();
        //每人发两张牌
        for($i=0;$i<2;$i++){
            foreach($this->place->players as $player){

                $poker = array_pop($this->bankerPoker);
                $pickPoker = array();
                $pickPoker["name"] = $this->numberToPokerFace($poker);     //将牌面值转为牌面描述方便看
                $pickPoker["num"] = $poker;    //牌值

                $this->playersPoker[$player->playerId][] = $pickPoker;
            }
        }
        //设置游戏进入底牌圈
        $this->bettingRounds = 1;
    }

    /**
     * 下注
     * @param PlayerModel $playerModel
     * @param $money
     * @throws Exception
     */
    public function bet(PlayerModel $playerModel,$money){
        //检测是否可下注 // 0=未开始 1= 底牌圈 2=底牌圈已下注 3=翻牌圈 4=翻牌圈已下注 5=转牌圈 6=转牌圈已下注 7=河牌圈 8=河牌圈已下注 9=显示结果和发奖金 10=游戏结束
        if($this->bettingRounds === 1 || $this->bettingRounds === 3 || $this->bettingRounds === 5 || $this->bettingRounds === 7){
            //检测用户钱包够不够钱
            if($playerModel->wallet < $money){
                throw new Exception("钱不够了");
            }

            //将钱扣掉
            $playerModel->wallet -= $money;     //TODO 直接存入数据库

            //将钱放入押注历史(以押注圈为主键)
            $this->betLog[$this->bettingRounds] = $money;

            //将钱放入奖池
            $this->jackpot += $money;

            //押注后推到已下注状态
            $this->bettingRounds += 1;

        }else{
            throw new Exception("现在不能下注");
        }
    }

    /**
     * 完成下注
     */
    public function finishBet(){

        return $this->sendPoker();
    }

    /**
     * 玩家（包括电话）弃牌
     */
    public function playerFold(){

    }

    /**
     * 电脑玩家检测是否需要弃牌
     * @return bool 是否放弃 true=放弃
     */
    private function foldCheck(){
        //添加电脑玩家的牌
        foreach($this->playersPoker[1] as $poker){
            $computerPokers[] = $poker["num"];
        }

        //附加上桌面公共牌
        foreach($this->board as $pokerNum){
            $playerPokers[] = $pokerNum;
            $computerPokers[] = $pokerNum;
        }

        //检测电脑玩家的牌
        $computerResult =  $this->testBordPoker($computerPokers);

        //检测牌中是否有对子或以上
        $hasPokerPair = false;
        if($computerResult["result"] > 2){
            $hasPokerPair = true;
        }

        //牌中是否有Q以上的
        $hasPokerQ = false;
        foreach($computerPokers as $poker){
            if($poker & 15 > 12){
                $hasPokerQ = true;
            }
        }

        //是否放弃
        if( ($hasPokerPair || $hasPokerQ) == false ){
            return true;
        }

        return false;

    }

    /**
     * 按规则发牌
     * @return array|void
     */
    private function sendPoker(){
        // 0=未开始 1= 底牌圈 2=底牌圈已下注 3=翻牌圈 4=翻牌圈已下注 5=转牌圈 6=转牌圈已下注 7=河牌圈 8=河牌圈已下注 9=显示结果和发奖金 10=游戏结束
        switch($this->bettingRounds){
            case 2:
                $this->sendPublicPoker_one();    //如果底牌圈已下注，发桌面三张公牌，并进入翻牌圈（3）
                //TODO 公牌出来后电脑玩家决定是否弃牌
                return $this->board;
                break;
            case 4:
                return $this->sendPublicPoker_two();    //如果翻牌圈已下注，发桌面第四张公牌，并进入转翻牌圈（5）
                break;
            case 6:
                return $this->sendPublicPoker_tree();   //如果转牌圈已下注，发桌面第五张公牌，并进入转翻牌圈（7）
                break;
            case 8:
                $this->showPKResult();                   //显示结果和发奖金
                break;
            default:
                return array();
        }
    }

    /**
     * C 发放三张公牌
     */
    private function sendPublicPoker_one(){
        $this->board = array();     //清空

        //从庄家用中抽出三张
        for($i=0;$i<3;$i++){
            $poker = array_pop($this->bankerPoker);
            $this->board[] = $poker;
        }

        //发牌后进入翻牌圈
        $this->bettingRounds = 3;

        //返回公牌
        return $this->board;
    }

    /**
     * D 第四张公共牌
     */
    private function sendPublicPoker_two(){
        //从庄家用中抽出一张
        $poker = array_pop($this->bankerPoker);
        $this->board[] = $poker;

        //发牌后进入转牌圈
        $this->bettingRounds = 5;

        //返回公牌
        return $this->board;
    }

    /**
     * D 第五张公共牌
     */
    private function sendPublicPoker_tree(){
        //从庄家用中抽出一张
        $poker = array_pop($this->bankerPoker);
        $this->board[] = $poker;

        //发牌后进入河牌圈
        $this->bettingRounds = 7;

        //返回公牌
        return $this->board;
    }

    /**
     * E 结束一轮游戏
     */
    private function gameOver(){
        //重设游戏圈
        $this->bettingRounds = 10;
    }


    /**
     * 检测所有玩家手上的牌以及比较结果
     * 如果当前局处于游戏结束时，根据结果对玩家进行奖罚
     */
    public function showPKResult(){
        error_reporting(0);
        $playerPokers = array();
        $computerPokers = array();

        //添加电脑玩家的牌
        foreach($this->playersPoker[1] as $poker){
            $computerPokers[] = $poker["num"];
        }

        //添加游戏玩家的牌
        foreach($this->playersPoker[$this->place->currentPlayer->playerId] as $poker){
            $playerPokers[] = $poker["num"];
        }

        //附加上桌面公共牌
        foreach($this->board as $pokerNum){
            $playerPokers[] = $pokerNum;
            $computerPokers[] = $pokerNum;
        }

        //检测电脑玩家的牌
        $computerResult =  $this->testBordPoker($computerPokers);

        //检测游戏玩家的牌
        $playerResult =  $this->testBordPoker($playerPokers);

        //比较两个的pk结果
        $winner = "";
        //如果两个的牌型一样
        if($playerResult["result"] == $computerResult["result"]){
            //比较对子或三条中最大的
            if($playerResult["maxPair"] == $computerResult["maxPair"]){
                //如果相等，比较牌点
                if($playerResult["maxPoker"] == $computerResult["maxPoker"]){
                    $winner = "both";
                }elseif($playerResult["maxPoker"] > $computerResult["maxPoker"]){
                    $winner = "player";
                }else{
                    $winner = "computer";
                }
            }elseif($playerResult["maxPair"] > $computerResult["maxPair"]){
                $winner = "player";
            }else{
                $winner = "computer";
            }
        }elseif($playerResult["result"] > $computerResult["result"]){
            $winner = "player";
        }else{
            $winner = "computer";
        }


        //如果当前局处于游戏结束时，根据结果对玩家进行奖罚
        if($this->bettingRounds == 8){
            if($winner === "player"){
                if($this->jackpot > 0){
                    $money = $this->jackpot * 2;
                    $this->winnerBonus = $money;
                    $this->place->currentPlayer->receiveBonus($money);
                }
            }

            $this->gameOver();
        }

        $returnArray = array(
            "bettingRounds" => $this->bettingRounds,
            "playerResult" => $playerResult,
            "computerResult" => $computerResult,
            "winner" => $winner,
            "bonus" => $this->winnerBonus
        );

        return $returnArray;
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
     * ########################################
     * #            牌面检测程序              #
     * ########################################
     */

    /**
     * 测试桌面上的牌面
     * 返回检测结果：
     * result -1 无结果 10=同花大顺 9=同花顺 8=四条 7=满堂红 6=同花 5=顺子 4=三条 3=两对 2=一对 1=高牌
     * @param $testPokers
     * @return array
     */
    public function testBordPoker($testPokers){

        //检测牌数量是否大于2张
        if(count($testPokers) <2 ) {
            return array(
                "result" => -1,
                "message" => "牌数量不足",
                "confirm" => array(),
                "error" => 1
            );
        }

        //检测牌面是否【同花大顺】
        $isRoyalFlush = $this->isRoyalFlush($testPokers);
        if($isRoyalFlush["result"] == true){
            return array(
                "result" => 10,
                "message" => "同花大顺",
                "confirms" => $isRoyalFlush["confirm"],
                "maxPoker"=>$isRoyalFlush["maxPoker"],
                "maxPair"=>$isRoyalFlush["maxPair"],
                "error" => 0
            );
        }

        //检测牌面是否【同花顺】
        $isStraightFlush = $this->isStraightFlush($testPokers);
        if($isStraightFlush["result"] == true){
            return array(
                "result" => 9,
                "message" => "同花顺",
                "confirms" => $isStraightFlush["confirm"],
                "maxPoker"=>$isStraightFlush["maxPoker"],
                "maxPair"=>$isStraightFlush["maxPair"],
                "error" => 0
            );
        }

        //检测牌面是否【四条】
        $isFourOfaKind = $this->isFourOfaKind($testPokers);
        if($isFourOfaKind["result"] == true){
            return array(
                "result" => 8,
                "message" => "四条",
                "confirms" => $isFourOfaKind["confirm"],
                "maxPoker"=>$isFourOfaKind["maxPoker"],
                "maxPair"=>$isFourOfaKind["maxPair"],
                "error" => 0
            );
        }

        //检测牌面是否【满堂红】
        $isFullHouse = $this->isFullHouse($testPokers);
        if($isFullHouse["result"] == true){
            return array(
                "result" => 7,
                "message" => "满堂红",
                "confirms" => $isFullHouse["confirm"],
                "maxPoker"=>$isFullHouse["maxPoker"],
                "maxPair"=>$isFullHouse["maxPair"],
                "error" => 0
            );

        }

        //检测牌面是否【同花】
        $isFlush = $this->isFlush($testPokers);
        if($isFlush["result"] == true){
            return array(
                "result" => 6,
                "message" => "同花",
                "confirms" => $isFlush["confirm"],
                "maxPoker"=>$isFlush["maxPoker"],
                "maxPair"=>$isFlush["maxPair"],
                "error" => 0
            );
        }

        //检测牌面是否【顺子】
        $isStraight = $this->isStraight($testPokers);
        if($isStraight["result"] == true){
            return array(
                "result" => 5,
                "message" => "顺子",
                "confirms" => $isStraight["confirm"],
                "maxPoker"=>$isStraight["maxPoker"],
                "maxPair"=>$isStraight["maxPair"],
                "error" => 0
            );
        }

        //检测牌面是否【三条】
        $isThreeOfaKind = $this->isThreeOfaKind($testPokers);
        if($isThreeOfaKind["result"] == true){
            return array(
                "result" => 4,
                "message" => "三条",
                "confirms" => $isThreeOfaKind["confirm"],
                "maxPoker"=>$isThreeOfaKind["maxPoker"],
                "maxPair"=>$isThreeOfaKind["maxPair"],
                "error" => 0
            );

        }

        //检测牌面是否【两对】
        $isTwoPairs = $this->isTwoPairs($testPokers);
        if($isTwoPairs["result"] == true){
            return array(
                "result" => 3,
                "message" => "两对",
                "confirms" => $isTwoPairs["confirm"],
                "maxPoker"=>$isTwoPairs["maxPoker"],
                "maxPair"=>$isTwoPairs["maxPair"],
                "error" => 0
            );
        }

        //检测牌面是否【一对】
        $isOnePair = $this->isOnePair($testPokers);
        if($isOnePair["result"] == true){
            return array(
                "result" => 2,
                "message" => "一对",
                "confirms" => $isOnePair["confirm"],
                "maxPoker"=>$isOnePair["maxPoker"],
                "maxPair"=>$isOnePair["maxPair"],
                "error" => 0
            );

        }

        //检测牌面是否【高牌】
        $isHighCard = $this->isHighCard($testPokers);
        if($isHighCard["result"] == true){
            return array(
                "result" => 1,
                "message" => "高牌",
                "confirms" => $isHighCard["confirm"],
                "maxPoker"=>$isHighCard["maxPoker"],
                "maxPair"=>$isHighCard["maxPair"],
                "error" => 0
            );
        }


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

        $maxPoker = null;
        $maxPair = null;
        if($result == true){
            //组合中最大的一张牌（用于在对方也是同花顺相同的情况下比牌）
            rsort($pokers);
            $maxPoker = $pokers[0];
        }

        $returnArray = array(
            "confirms"=>$confirms,
            "maxPoker"=>$maxPoker,
            "maxPair"=>$maxPair,
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

        $maxPoker = null;
        $maxPair = null;
        if($result == true){
            //四条中最大的点数（用于在对方也是四条的情况下比牌）
            $pairs = $confirms;
            rsort($pairs);
            $maxPair = $pairs[0];

            //组合中最大的一张牌（用于在对方三条相同的情况下比牌）
            rsort($pokers);
            $maxPoker = $pokers[0];
        }

        //返回
        $returnArray = array(
            "confirms"=>$confirms,
            "maxPoker"=>$maxPoker,
            "maxPair"=>$maxPair,
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

        //合并
        $confirms = array_merge($confirms3 ,$confirms2);

        $maxPoker = null;
        $maxPair = null;
        if($result == true){
            //满堂红中最大的三条点数（用于在对方也是三条的情况下比牌）
            $pairs = $confirms3;
            rsort($pairs);
            $maxPair = $pairs[0];

            //组合中最大的一张牌（用于在对方三条相同的情况下比牌）
            rsort($pokers);
            $maxPoker = $pokers[0];
        }

        //返回
        $returnArray = array(
            "confirms"=>$confirms,
            "maxPoker"=>$maxPoker,
            "maxPair"=>$maxPair,
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

        $maxPoker = null;
        $maxPair = null;
        if($result == true){
            //组合中最大的一张牌（用于在对方也是同花的情况下比牌）
            rsort($pokers);
            $maxPoker = $pokers[0];
        }


        //返回
        $returnArray = array(
            "confirms"=>$confirms,
            "maxPoker"=>$maxPoker,
            "maxPair"=>$maxPair,
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

        $maxPoker = null;
        $maxPair = null;
        if($result == true){
            //组合中最大的一张牌（用于在对方顺子相同的情况下比牌）
            rsort($pokers);
            $maxPoker = $pokers[0];
        }

        //返回
        $returnArray = array(
            "confirms"=>$confirms,
            "maxPoker"=>$maxPoker,
            "maxPair"=>$maxPair,
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

        $maxPoker = null;
        $maxPair = null;
        if($result == true){
            //最大的三条点数（用于在对方也是三条的情况下比牌）
            $pairs = $confirms;
            rsort($pairs);
            $maxPair = $pairs[0];

            //组合中最大的一张牌（用于在对方三条相同的情况下比牌）
            rsort($pokers);
            $maxPoker = $pokers[0];
        }

        //返回
        $returnArray = array(
            "confirms"=>$confirms,
            "maxPoker"=>$maxPoker,
            "maxPair"=>$maxPair,
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

        $maxPoker = null;
        $maxPair = null;
        if($result == true){
            //最大的对子点数（用于在对方也是对子的情况下比牌）
            $pairs = $confirms;
            rsort($pairs);
            $maxPair = $pairs[0];

            //组合中最大的一张牌（用于在对方对子相同的情况下比牌）
            rsort($pokers);
            $maxPoker = $pokers[0];

        }

        //返回
        $returnArray = array(
            "confirms"=>$confirms,
            "maxPoker"=>$maxPoker,
            "maxPair"=>$maxPair,
            "result" => $result
        );
        //echo json_encode($returnArray);
        return $returnArray;
    }

    /**
     * 检测牌面是否【一对】
     * @param $pokers
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


        $maxPoker = null;
        $maxPair = null;
        if($result == true){
            //最大的对子点数（用于在对方也是对子的情况下比牌）
            $pairs = $confirms;
            rsort($pairs);
            $maxPair = $pairs[0];

            //组合中最大的一张牌（用于在对方对子相同的情况下比牌）
            rsort($pokers);
            $maxPoker = $pokers[0];

        }


        //返回
        $returnArray = array(
            "confirms"=>$confirms,
            "maxPoker"=>$maxPoker,
            "maxPair"=>$maxPair,
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
        //选择五张最大的
        $confirms = array();
        $i=0;
        foreach($pokers as $poker){
            if($i < 5){
                $confirms[] = $poker;
                $i++;
            }
        }
        $maxPoker = array_pop($pokers); // 高牌中最大的一张

        //返回
        $returnArray = array(
            "confirms"=>$confirms,
            "maxPair"=>$maxPoker,
            "maxPoker"=>$maxPoker,
            "result" => true
        );
        //echo json_encode($returnArray);
        return $returnArray;
    }


}