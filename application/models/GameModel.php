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

    //当前押注圈 0=未开始 1= 底牌圈 2=翻牌圈 3=转牌圈 4=河牌圈
    public $bettingRounds =0;

    //当前局游戏历史
    public $gameHistory = array();

    //当前牌局彩池
    public $jackpot = 0;

    //庄家手中未发的牌
    public $bankerPoker = array();

    //台面  泛指桌上的五张公共牌
    public $board = array();

    //玩家手中的牌面
    public $playersPoker = array();

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
    public function init(PlaceModel $placeModel){
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
            }

            //如果可进行游戏则马上开始
            if($this->canPlay() == TRUE) $this->newGame();
            //var_dump($this->bankerPoker);
            var_dump($this->playersPoker);

            return TRUE;
        }

        return FALSE;
    }

    /**
     * 缓存游戏信息
     */
    function saveGame(){
        $this->cache->save("game_".$this->place->placeId."_".$this->place->sceneId,$this,3600);
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
    }

    /**
     * 下注
     * @param PlayerModel $playerModel
     * @param $money
     * @throws Exception
     */
    public function bid(PlayerModel $playerModel,$money){
        //检测用户钱包够不够钱
        if($playerModel->wallet < $money){
            throw new Exception("钱不够了");
        }

        //将钱扣掉
        $playerModel->wallet -= $money;     //TODO 直接存入数据库

        //将钱放入奖池
        $this->jackpot += $money;
    }

    /**
     * 完成下注
     */
    public function finishBid(){
        //如果还有下一圈，推到下一圈
        if($this->bettingRounds < 4) {
            $this->bettingRounds++;
        }

        return $this->sendPoker();
    }

    /**
     * 按规则发牌
     * @return array|void
     */
    private function sendPoker(){
        // 0=未开始 1= 底牌圈 2=翻牌圈 3=转牌圈 4=河牌圈
        switch($this->bettingRounds){
            case 2:
                return $this->sendPublicPoker_one();
                break;
            case 3:
                return $this->sendPublicPoker_two();
                break;
            case 4:
                return $this->sendPublicPoker_tree();
                break;
            default:
                return array();
        }
    }

    /**
     * C 发放三张公牌
     */
    private function sendPublicPoker_one(){
        //TODO
    }

    /**
     * D 第四张公共牌
     */
    private function sendPublicPoker_two(){
        //TODO
    }

    /**
     * D 第五张公共牌
     */
    private function sendPublicPoker_tree(){
        //TODO
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
}