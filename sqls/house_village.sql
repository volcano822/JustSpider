-- phpMyAdmin SQL Dump
-- version 3.4.10
-- http://www.phpmyadmin.net
--
-- 主机: localhost:3306
-- 生成日期: 2017 年 05 月 23 日 14:43
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
-- 表的结构 `house_village`
--

CREATE TABLE IF NOT EXISTS `house_village` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `name` varchar(64) NOT NULL COMMENT '小区名称',
  `status` smallint(6) NOT NULL DEFAULT '0' COMMENT '抓取状态：默认0: 进行中， 1: 已同步至最新',
  `one_house_code` char(32) NOT NULL COMMENT '一条房源编号',
  `village_code` varchar(32) NOT NULL DEFAULT '0' COMMENT '小区编号',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='小区信息' AUTO_INCREMENT=10981 ;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
