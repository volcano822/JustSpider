-- phpMyAdmin SQL Dump
-- version 3.4.10
-- http://www.phpmyadmin.net
--
-- 主机: localhost:3306
-- 生成日期: 2017 年 05 月 23 日 14:42
-- 服务器版本: 5.5.30
-- PHP 版本: 5.2.17

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- 数据库: `hxf`
--

-- --------------------------------------------------------

--
-- 表的结构 `house_chengjiao`
--

CREATE TABLE IF NOT EXISTS `house_chengjiao` (
  `house_code` char(32) NOT NULL COMMENT '房源编号',
  `title` varchar(64) NOT NULL COMMENT '标题',
  `house_info` varchar(128) NOT NULL COMMENT '房子信息',
  `deal_date` date NOT NULL COMMENT '成交日期',
  `price` float NOT NULL COMMENT '成交价',
  `position_info` varchar(128) NOT NULL COMMENT '位置信息',
  `src` varchar(64) NOT NULL COMMENT '成交数据来源',
  `unit_price` float NOT NULL COMMENT '单价',
  `insert_time` int(11) NOT NULL COMMENT '插入时间',
  `ext` varchar(256) NOT NULL COMMENT '其他信息',
  `village` varchar(64) NOT NULL COMMENT '小区名称',
  PRIMARY KEY (`house_code`),
  KEY `village` (`village`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='北京市二手房成交数据';

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
