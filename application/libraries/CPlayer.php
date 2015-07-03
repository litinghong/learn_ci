<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Created by PhpStorm.
 * User: user1011
 * Date: 2015/7/2
 * Time: 12:43
 */

class CPlayer extends CI_Controller{
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
    private $wallet;

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

    /**
     * 通过玩家ID初始化玩家的信息
     * @param $playerId int 玩家ID
     */
    function __construct($playerId)
    {
        parent::__construct();
        if($playerId > 0){
            $this->load->driver('cache', array('adapter' => 'apc', 'backup' => 'file'));
            $this->playerId = $playerId;
            //读取缓存中当前登录用户
            $playerInfo = $this->cache->get("player_".$playerId);
            $this->fullName = $playerInfo["fullName"];
            $this->wallet = $playerInfo["wallet"];
            $this->currentPlaceId = $playerInfo["currentPlaceId"];
            $this->currentSceneId = $playerInfo["currentSceneId"];
            $this->isPlaying = $playerInfo["isPlaying"];
        }
    }




}