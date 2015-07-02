<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Created by PhpStorm.
 * User: user1011
 * Date: 2015/7/2
 * Time: 12:43
 */

class CPlayer {
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
     * @var int 当前场次id
     */
    private $currentSceneId = 0;

    /**
     * @return string
     */
    public function getWallet()
    {
        return $this->wallet;
    }

    /**
     * @param string $wallet
     */
    public function setWallet($wallet)
    {
        $this->wallet = $wallet;
    }


}