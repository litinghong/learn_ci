<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Created by PhpStorm.
 * User: user1011
 * Date: 2015/7/2
 * Time: 12:43
 */

class Player {
    /**
     * @var string 玩家姓名
     */
    public $fullName;

    /**
     * @var string 钱包
     */
    private $wallet;

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