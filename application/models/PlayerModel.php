<?php
/**
 * Created by PhpStorm.
 * 玩家模型
 * User: user1011
 * Date: 2015/7/2
 * Time: 12:48
 */

class PlayerModel extends CI_Model{

    /**
     * @var string 玩家id
     */
    public $playerId;
    /**
     * @var string 玩家姓名
     */
    public $fullName;

    /**
     * @var string 钱包
     */
    public $wallet;

    /**
     * @var int 当前场地ID
     */
    public $currentPlaceId = 0;

    /**
     * @var int 当前场次ID
     */
    public $currentSceneId = 0;

    /**
     * @var bool 是否正在玩游戏
     */
    public $isPlaying = false;


    function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->driver('cache', array('adapter' => 'apc', 'backup' => 'file'));
    }

    /**
     * 通过玩家ID初始化玩家的信息
     * @param $playerId int 玩家ID
     * @return array
     */
    function init($playerId){
        if($playerId > 0){
            $this->playerId = $playerId;
            //读取缓存中当前登录用户
            $playerInfo = $this->cache->get("player_".$playerId);
            //判断对象是否属于PlayerModel类
            if($playerInfo instanceof PlayerModel){
                $this->fullName = $playerInfo->fullName;
                $this->wallet = $playerInfo->wallet;
                $this->currentPlaceId = $playerInfo->currentPlaceId;
                $this->currentSceneId = $playerInfo->currentSceneId;
                $this->isPlaying = $playerInfo->isPlaying;

                return TRUE;
            }
        }
        return FALSE;
    }

    /**
     * 执行用户登录
     * @param $fullName
     * @return bool|null
     */
    function do_login($fullName)
    {
        $query = $this->db->get_where("players","fullName='$fullName'");
        $rows = $query->result_array();
        if(count($rows) > 0){
            $row = array_pop($rows);
            $this->playerId = $row["id"];
            $this->fullName = $row["fullName"];
            $this->wallet = $row["wallet"];
            $this->currentPlaceId = 0;
            $this->currentSceneId = 0;
            $this->isPlaying = FALSE;

            return TRUE;
        }
        return NULL;
    }

    /**
     * 缓存玩家信息
     */
    public function savePlayer(){
        //缓存用户登录信息
        $this->cache->save("player_".$this->playerId,$this,3600);
    }


    /**
     * 获取一个用户
     * 注意：因为此程序只在缓存中获取
     * 有可能出现获取不到用户的情况
     * @param $playerId
     * @return PlayerModel
     */
    public function get_player($playerId){
        //读取缓存中的用户信息
        $playerInfo = $this->cache->get("player_".$playerId);
        //判断对象是否属于PlayerModel类
        if($playerInfo instanceof PlayerModel){
            return $playerInfo;
        }else{
            return NULL;    //没有用户
        }

    }
}