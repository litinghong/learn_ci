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
        $this->load->database();
    }

    /**
     * @param $fullname string 用户名
     * @return player object 玩家对象
     */
    function select_user_by_fullName($fullName)
    {
        $query = $this->db->get_where("players","fullName='$fullName'");
        $rows = $query->result_array();
        if(count($rows) > 0){
            return array_pop($rows);
        }
        return NULL;
    }
}