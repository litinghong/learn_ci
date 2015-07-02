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


}