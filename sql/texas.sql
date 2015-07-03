/*
Navicat MySQL Data Transfer

Source Server         : localhost
Source Server Version : 50624
Source Host           : localhost:3306
Source Database       : texas

Target Server Type    : MYSQL
Target Server Version : 50624
File Encoding         : 65001

Date: 2015-07-03 18:47:34
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for bet_log
-- ----------------------------
DROP TABLE IF EXISTS `bet_log`;
CREATE TABLE `bet_log` (
  `id` int(11) NOT NULL,
  `player_id` int(11) DEFAULT NULL COMMENT '玩家id',
  `scene_id` int(11) DEFAULT NULL COMMENT '场号',
  `bet_count` int(11) DEFAULT NULL COMMENT ' 当前押注数',
  `bank_roll` int(11) DEFAULT NULL COMMENT '游戏时身上的筹码数',
  `rounds` int(11) DEFAULT NULL COMMENT ' 押注圈 - 每一个牌局可分为四个押注圈，对应 1=底牌圈 2=翻牌圈 3=转牌圈 4=河牌圈',
  `dateline` int(11) DEFAULT NULL COMMENT '押注时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='押注日志';

-- ----------------------------
-- Records of bet_log
-- ----------------------------

-- ----------------------------
-- Table structure for place_queue
-- ----------------------------
DROP TABLE IF EXISTS `place_queue`;
CREATE TABLE `place_queue` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '场次ID',
  `start_dateline` int(11) DEFAULT NULL COMMENT '场地开设时间',
  `end_dateline` int(11) DEFAULT NULL COMMENT '场地关闭时间',
  `maxPlayer` int(11) DEFAULT '12' COMMENT '最大允许参加游戏的人数',
  `minPlayer` int(11) DEFAULT '2' COMMENT '最少开局人数',
  `placeStatus` tinyint(11) DEFAULT '1' COMMENT '场地可用状态 1=可用 0=不可用',
  `sceneStatus` tinyint(11) DEFAULT '0' COMMENT '场次状态 0=未开始 1=进行中 2=已结束',
  `sceneId` int(11) DEFAULT '0' COMMENT '当前场次号',
  `players` varchar(255) DEFAULT '' COMMENT '玩家的ID,用逗号分隔',
  `playersCount` int(11) DEFAULT '0' COMMENT '当前场地中的玩家人数',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8 COMMENT='场地';

-- ----------------------------
-- Records of place_queue
-- ----------------------------
INSERT INTO `place_queue` VALUES ('10', '1435906480', null, '12', '2', '1', '0', '0', '1,2', '2');

-- ----------------------------
-- Table structure for players
-- ----------------------------
DROP TABLE IF EXISTS `players`;
CREATE TABLE `players` (
  `id` int(11) NOT NULL,
  `fullName` varchar(255) DEFAULT NULL COMMENT '全名',
  `wallet` int(11) DEFAULT NULL COMMENT '钱包',
  `type` int(11) DEFAULT '0' COMMENT '是否电脑玩家 0=否 1=是',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='玩家表';

-- ----------------------------
-- Records of players
-- ----------------------------
INSERT INTO `players` VALUES ('1', 'user1', '3000', '1');
INSERT INTO `players` VALUES ('2', 'user2', '2000', '0');

-- ----------------------------
-- Table structure for scene_queue
-- ----------------------------
DROP TABLE IF EXISTS `scene_queue`;
CREATE TABLE `scene_queue` (
  `int` int(11) NOT NULL AUTO_INCREMENT COMMENT '场次ID',
  `placeId` int(11) NOT NULL COMMENT '场地ID',
  `start_dateline` int(11) DEFAULT NULL COMMENT '开场时间',
  `end_dateline` int(11) DEFAULT NULL COMMENT '结束时间',
  `status` tinyint(11) DEFAULT '0' COMMENT ' 0=未开始 1=进行中 2=已结束',
  PRIMARY KEY (`int`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ----------------------------
-- Records of scene_queue
-- ----------------------------
