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
        //如果玩家处于登录状态，则保存场地、玩家、游戏信息，否则清除它们
        if($this->PlayerModel->isLogin){
            //保存场地信息
            $this->PlaceModel->savePlace();
            //保存玩家信息
            $this->PlayerModel->savePlayer();
            //保存游戏信息
            $this->GameModel->saveGame();
        }else{
            $this->cache->clean();
        }


        //输出游戏信息
        //var_dump( $this->statusReport());

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

            //单机游戏直接进场
            $this->PlaceModel->init($this->PlayerModel);
            $this->PlaceModel->assignPlace();

            $this->processResult(null,"ok",null);
        }else{
            $this->processResult(null,"error",null);
        }

    }

    /**
     * 退出登录
     */
    public function logout(){
        $this->cleanGame(); //清理所有缓存和session
    }

    /**
     * 清理游戏所有缓存和session
     * 注意：给调试用的
     */
    public function cleanGame(){
        $this->session->set_userdata('playerId',null);
        $this->PlayerModel->isLogin = false;
    }


    /**
     * 测试桌面上的牌面
     */
    public function testBordPoker(){
        $result = $this->GameModel->showPKResult();
        $this->processResult($result);
    }

    /**
     * A 开始新牌局
     */
    public function newDeal(){
        $this->GameModel->newGame();
        $this->processResult("true");
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
     * 5、当前押注圈 bettingRounds
     * 6、押注信息 betLog
     * 7、当前牌局彩池 jackpot
     * 8、公牌 board
     * 9、玩家手上的牌 playersPoker
     *
     * ### 玩家信息 ###
     * 10、剩余金额 wallet
     * @param null $statusName 状态名，如果为空显示所有状态
     * @return array
     */
    public function statusReport($statusName = NULL,$json = FALSE){
        $data = array(
            /* 场地信息类 */
            "placeId" => $this->PlaceModel->placeId,
            "placeStatus" => $this->PlaceModel->placeStatus,
            "sceneId" => $this->PlaceModel->sceneId,
            "sceneStatus" => $this->PlaceModel->sceneStatus,
            "player" => $this->PlayerModel,
            /* 游戏信息类 */
            "bettingRounds" => $this->GameModel->bettingRounds,
            "betLog" => $this->GameModel->betLog,
            "jackpot" => $this->GameModel->jackpot,
            "board" => $this->GameModel->board,
            "playersPoker" => isset($this->GameModel->playersPoker)?$this->GameModel->playersPoker:null,        //TODO 电脑和登录用户手上的牌，正式环境需要删除
            "computerPoker" => isset($this->GameModel->playersPoker[1])?$this->GameModel->playersPoker[1]:null,        //TODO 电脑手上的牌，正式环境需要删除
            "playerPoker" => isset($this->GameModel->playersPoker[$this->PlayerModel->playerId])?$this->GameModel->playersPoker[$this->PlayerModel->playerId]:null,        //当前登录用户手上的牌

            /* 玩家信息 */
            "wallet" => $this->PlayerModel->wallet,
        );


        if($statusName == NULL || $statusName =="false"){
            $returnData = $data;
            if($json == TRUE){
                $this->processResult($returnData);
            }
        }else{
            $returnData = $data[$statusName];
            if($json == TRUE){
                $this->processResult(array("$statusName"=>$returnData));
            }
        }



        return $returnData;
    }

    /**
     * 玩家下注
     */
    public function bet(){
        $money = $this->input->post("money");
        $this->GameModel->bet($this->PlayerModel,$money);
        echo json_encode($money);
    }

    /**
     * 完成下注并返回牌面信息
     */
    public function finishBet(){
        $result = $this->GameModel->finishBet();
        $this->processResult($result);
    }

    /**
     * 检测所有玩家手上的牌
     * 以及比较结果
     */
    public function showPKResult(){
        $result = $this->GameModel->showPKResult();
        var_dump($result);
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
