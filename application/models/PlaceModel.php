<?php
/**
 * Created by PhpStorm.
 * 场地控制模型
 * User: user1011
 * Date: 2015/7/2
 * Time: 12:48
 */

class PlaceModel extends CI_Model{

    /**
     * @var int 场地ID
     */
    public $placeId = 0;

    /**
     * @var PlayerModel 当前玩家
     */
    public $currentPlayer;

    /**
     * @var array 当前场地中的其它玩家
     */
    public $players = array();

    /**
     * @var int 当前场地中的玩家人数
     */
    public $playersCount = 0;

    /**
     * @var array 围观中的玩家
     */
    public $players_hold = array();

    /**
     * @var array 当前牌局玩家下注信息
     */
    public $playersBetLogs = array();


    /**
     * @var int 场地开设时间
     */
    public $start_dateline = 0;

    /**
     * @var int 场地关闭时间
     */
    public $end_dateline = 0;

    /**
     * @var int 最大允许参加游戏的人数
     */
    public $maxPlayer = 0;

    /**
     * @var int 最少开局人数
     */
    public $minPlayer = 0;

    /**
     * @var int 场地可用状态 1=可用 0=不可用
     */
    public $placeStatus = 0;

    /**
     * @var int  场次状态 0=未开始 1=进行中 2=已结束
     */
    public $sceneStatus = 0;

    /**
     * @var int 当前场次号
     */
    public $sceneId = 0;


    function __construct()
    {
        parent::__construct();
        $this->load->database();
    }


    /**
     * 初始化玩家所在的场地信息
     * @param PlayerModel $playerModel
     * @return bool
     * @throws Exception
     */
    public function init(PlayerModel $playerModel){
        //必须是玩家模型
        if(!$playerModel instanceof PlayerModel){
            throw new Exception("传入的对象不是玩家模型!");
        }
        $this->currentPlayer = $playerModel;

        //读取缓存中当前登录用户
        $placeInfo = $this->cache->get("place_".$playerModel->currentPlaceId);
        if($placeInfo instanceof PlaceModel){
            $this->placeId = $placeInfo->placeId;
            $this->start_dateline = $placeInfo->start_dateline;
            $this->end_dateline = $placeInfo->end_dateline;
            $this->maxPlayer = $placeInfo->maxPlayer;
            $this->minPlayer = $placeInfo->minPlayer;
            $this->placeStatus = $placeInfo->placeStatus;
            $this->sceneStatus = $placeInfo->sceneStatus;
            $this->sceneId = $placeInfo->sceneId;
            $this->players = $placeInfo->players;
            $this->playersCount = $placeInfo->playersCount;

            $this->players_hold = $placeInfo->players_hold;

            return TRUE;
        }else{
            //缓存中读取不到，从数据库中读取 TODO 可能也不需要了？因为一个小时后没有数据，用户也属于离场状态了

        }
        return FALSE;
    }

    /**
     * 缓存场地信息
     */
    public function savePlace(){
        $this->cache->save("place_".$this->placeId,$this,3600);
    }



    /**
     * 分配一个场地
     * 1、如果玩家已在某个场地，则不分配
     * 2、优先分配未开始的场地
     * 3、如果没有则开设新的场地
     * 4、默认配置一个电脑玩家，电脑玩家的payerId号为1
     */
    public function assignPlace(){
        if($this->placeId == 0){
            //从数据库中获取可用的场地列表
            $this->db->order_by('sceneStatus DESC,playersCount DESC');
            $query = $this->db->get_where("place_queue","placeStatus = 1 AND playersCount < maxPlayer");
            $rows = $query->result_array();

            //如果有多个可分配的场地，优先向用户分配第一个场地
            if(count($rows) > 0){
                $row = $rows[0];
            }else{
                //没有可分配的场地，需新建一个场地
                $row = $this->get_new_place();
            }

            //设置场地信息
            $this->placeId = $row["id"];
            $this->start_dateline =  $row["start_dateline"];
            $this->end_dateline =  $row["end_dateline"];
            $this->maxPlayer =  $row["maxPlayer"];
            $this->minPlayer =  $row["minPlayer"];
            $this->placeStatus =  $row["placeStatus"];
            $this->sceneStatus =  $row["sceneStatus"];
            $this->sceneId =  $row["sceneId"];

            //从场地表中读取所有玩家的id号，并转换为玩家对象，存入场地的玩家数组中
            //MARK 由于采用单机游戏模式，不需要从数据表中读取玩家了
            /*
            $playerIds = array_diff(explode(",",$row["players"]),array(""));
            foreach($playerIds as $playerId){
                $otherPlayer =  $this->currentPlayer->get_player($playerId);
                if($otherPlayer != NULL){
                    $this->players[$playerId] = $otherPlayer;
                }
            }
            */

            //添加玩家对象到数组
            if(!in_array($this->currentPlayer,$this->players)){
                $this->players[] = $this->currentPlayer;        //添加新玩家
            }

            //添加一个电脑玩家到数组中
            $computerPlayer =  $this->currentPlayer->get_player(1);
            $this->players[] = $computerPlayer;


            //添加玩家id号到数组
            $playerIds = array_keys($this->players);



            //玩家数量统计
            $this->playersCount =  count($this->players);

            //设置玩家信息
            $this->currentPlayer->currentPlaceId = $row["id"];
            $this->currentPlayer->currentSceneId = $row["sceneId"];

            //更新场地表中的玩家信息
            $updateData = array(
                "players" => implode(",",$playerIds),
                "playersCount" => $this->playersCount
            );
            $this->db->where('id', $this->placeId);
            $this->db->update('place_queue', $updateData);


        }
    }


    /**
     * 获得新场地号，并返回场地信息
     * @return mixed
     */
    private function get_new_place()
    {
        //TODO 场地的默认配置需要从配置文件中设置
        $newPlace = array(
            "start_dateline"=>time(),
            "end_dateline"=>null,
            "maxPlayer"=>12,
            "minPlayer"=>2,
            "placeStatus"=>1,   //场地可用状态 1=可用 0=不可用
            "sceneStatus"=>0,   //场次状态 0=未开始 1=进行中 2=已结束
            "sceneId"=>1,
            "players"=>"",
            "playersCount"=>0
        );
        $result = $this->db->insert('place_queue', $newPlace);
        if($result == true){
            $insertId = $this->db->insert_id();
            $newPlace["id"] = $insertId;
            return $newPlace;
        }

        return false;
    }

}