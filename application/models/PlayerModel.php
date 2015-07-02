<?php
/**
 * Created by PhpStorm.
 * User: user1011
 * Date: 2015/7/2
 * Time: 12:48
 */

class PlayerModel extends CI_Model{

    function __construct()
    {
        parent::__construct();
        $this->load->library('Player');
    }

    /**
     * @param $fullname string 用户名
     * @return array
     */
    function select_user_by_fullname($fullname)
    {
        $rows = $this->db->get_where("players","fullname='$fullname'");
        $player = new Player();

        if(count($rows) > 0){
            $player->fullName = $rows[0]["fullname"];
            $player->setWallet($rows[0]["wallet"]);
        }
        return NULL;
    }
}