<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Created by PhpStorm.
 * 场地类
 * User: user1011
 * Date: 2015/7/2
 * Time: 12:43
 */

class CPlace {
    /**
     * @var PlaceModel 场地模型
     */
    private $placeModel;
    /**
     * @var int 场地ID
     */
    public $placeId;
    /**
     * @var array 当前场地中的玩家
     */
    private $players;

    /**
     * @var int 场地开设时间
     */
    public $start_dateline;

    /**
     * @var int 场地关闭时间
     */
    public $end_dateline;

    /**
     * @var int 最大允许参加游戏的人数
     */
    public $maxPlayer;

    /**
     * @var int 最少开局人数
     */
    public $minPlayer;

    /**
     * @var int 场地可用状态 1=可用 0=不可用
     */
    public $status;

    /**
     * @var int 场次ID
     */
    public $sceneId;


    /**
     * @param PlaceModel $placeModel
     */
    public function setPlaceModel($placeModel)
    {
        $this->placeModel = $placeModel;
    }



    /**
     * 获取新的场地
     * @return $this
     */
    public function get_new_place(){
        //是否有空闲的场地
        $newPlaceData = $this->placeModel->get_new_place();
        $this->placeId = $newPlaceData["id"];
        $this->start_dateline = $newPlaceData["start_dateline"];
        $this->end_dateline = $newPlaceData["end_dateline"];
        $this->maxPlayer = $newPlaceData["maxPlayer"];
        $this->minPlayer = $newPlaceData["minPlayer"];
        $this->status = $newPlaceData["status"];
        return $this;
    }

    /**
     * 进入一个场地
     * 注：进入场地将离开上一个场地
     * @param CPlayer $player 玩家
     */
    public function entry_place(CPlayer $player){


    }

}