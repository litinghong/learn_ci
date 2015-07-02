<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * 德州扑克游戏
 * Class Texas
 */
class Texas extends CI_Controller {

    //彩池
    private $jackpot = 0;

    //庄家手中未发的牌
    private $bankerPoker = array();

    //台面  泛指桌上的五张公共牌
    private $board = array();

    //当前玩家(数组)
    private $players = array();

    //玩家手中的牌面
    private $playersPoker = array();

    //当前牌局玩家下注信息

    //牌局开始状态 0=未开始 1=进行中 2=已结束
    private $startStatus = 0;


	public function __construct()
	{
        //TODO 假定有2名玩家,钱包里分别有 3000和4000元
        $this->players = array(
            "1001"=>array("fullname"=>"player 1","wallet"=>3000),
            "1002"=>array("fullname"=>"player 2","wallet"=>4000),
        );
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
         * 黑桃 2 = 128 + 1 = 1000 0001
         * 依此类推
         */
        $newPoker = array(
            17,18,19,20,21,22,23,24,25,26,27,28,29,                 //方块 2 - A
            33,34,35,36,37,38,39,40,41,42,43,44,45,                 //梅花 2 - A
            65,66,67,68,69,70,71,72,73,74,75,76,77,                 //红桃 2 - A
            129,130,131,132,133,134,135,136,137,138,139,140,141     //黑桃 2 - A
        );

        //随机生成庄家手中的牌序
        shuffle($newPoker);
        $this->bankerPoker = $newPoker;

        //向所有玩家发放底牌
        $this->holeCards();

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
            foreach($this->players as $playerId => $player){
                $this->playersPoker[$playerId][] = $this->numberToPokerFace(array_pop($this->bankerPoker));     //将牌面值转为牌面描述方便看
            }
        }

        //返回信息
        $returnArray =array(
            "playersPoker" => $this->playersPoker   //TODO 这里应只出当前用户的牌面
        );
        $this->processResult($returnArray);
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
                $flowerType="方块";
                break;
            case 2:
                $flowerType="梅花";
                break;
            case 4:
                $flowerType="红桃";
                break;
            case 8:
                $flowerType="黑桃";
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
        $pokers=array(17,34,68,67,69,71,70);
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
        $possibleTemp = array();  //有可能是同花大顺的，可进入第三轮
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
                $result = (array_sum($temp)- ($min-1)*5) === 15 ? true : false;
                if($result == true) $confirms = $temp;
            }

        }

        $returnArray = array(
            "confirms"=>$confirms,
            "result" => $result
        );
        echo json_encode($returnArray);
        return $returnArray;
    }

    /**
     * 检测牌面是否【同花顺】
     * @param $pokers
     * @return array
     */
    function isStraightFlush($pokers){
        //TODO 测试
        $pokers=array(18,35,68,133,22,76,34);

        //去除高四位
        $newPokers = array();
        $newPokersIndex = array();  //去除高四位后需要知道原来是什么
        foreach($pokers as $poker){
            $low4 = $poker & 15;
            $newPokers[$poker] = $low4;
        }

        $confirms = array();      //确定为同花顺的牌
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
                $result = (array_sum($temp)- ($min-1)*5) === 15 ? true : false;
                if($result == true){
                    //替换回原来的牌(有高四位的)
                    $confirms = array_keys($temp);

                }
            }

        }

        //返回
        $returnArray = array(
            "confirms"=>$confirms,
            "result" => $result
        );
        echo json_encode($returnArray);
        return $returnArray;
    }

    /**
     * 检测牌面是否【四条】
     * @param $pokers
     * @return array
     */
    function isFourOfaKind($pokers){
        //TODO 测试
        $pokers=array(18,34,66,130,38,24,27);

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
                if(count($group) === 4){
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
        echo json_encode($returnArray);
        return $returnArray;

    }

    /**
     * 检测牌面是否【满堂红】 三张同一点数的牌，加一对其他点数的牌。
     * @param $pokers
     * @return array
     */
    function isFullHouse($pokers){
        //TODO 测试
        $pokers=array(18,34,66,26,42,28,77);

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
        echo json_encode($returnArray);
        return $returnArray;
    }

    /**
     * 检测牌面是否【同花】
     * @param $poker
     */
    function isFlush($poker){

    }

    /**
     * 检测牌面是否【顺子】
     * @param $poker
     */
    function isStraight($poker){

    }

    /**
     * 检测牌面是否【三条】
     * @param $poker
     */
    function isThreeOfaKind($poker){

    }

    /**
     * 检测牌面是否【两对】
     * @param $poker
     */
    function isTwoPairs($poker){

    }

    /**
     * 检测牌面是否【一对】
     * @param $poker
     */
    function isOnePair($poker){

    }


    /**
     * 检测牌面是否【高牌】
     * @param $poker
     */
    function isHighCard($poker){

    }

    /**
     * 处理并返回资料
     * @param $data
     */
    function processResult($data){
        $returnData=array(
            "game" => $data,
            "status" => "ok",
            "error" => null,
            "debug" => array(),
            "user" => $this->players["1001"]         //TODO 通过session获取当前用户
        );
        echo json_encode($returnData);
        exit();
    }

}
