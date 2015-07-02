<?php
/**
 * Created by PhpStorm.
 * 场次控制模型
 * User: user1011
 * Date: 2015/7/2
 * Time: 12:48
 */

class SceneModel extends CI_Model{

    function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    /**
     * 获得新场次号
     * @return mixed
     */
    function get_new_scene()
    {
        $result = $this->db->insert('scene_queue', array("start_dateline"=>time()));
        if($result == true){
            $insertId = $this->db->insert_id();
            return $insertId;
        }

        return false;
    }

    /**
     * 获得新场地号，并返回场地信息
     * @return mixed
     */
    function get_new_place()
    {
        //TODO 场地的默认配置需要从配置文件中设置
        $newPlace = array(
            "start_dateline"=>time(),
            "end_dateline"=>null,
            "maxPlayer"=>12,
            "minPlayer"=>2,
            "status"=>1,
        );
        $result = $this->db->insert('place_queue', $newPlace);
        if($result == true){
            $insertId = $this->db->insert_id();
            $newPlace["placeId"] = $insertId;
            return $newPlace;
        }

        return false;
    }
}