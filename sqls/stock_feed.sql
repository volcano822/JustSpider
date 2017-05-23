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
-- 表的结构 `stock_feed`
--

CREATE TABLE IF NOT EXISTS `stock_feed` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `stock_id` bigint(20) NOT NULL COMMENT '股票ID',
  `date` varchar(32) NOT NULL COMMENT '日期时间',
  `url` varchar(256) NOT NULL COMMENT '资讯URL地址',
  `title` varchar(256) NOT NULL COMMENT '资讯标题',
  `crc32_value` bigint(20) NOT NULL COMMENT 'crc32值，用于判重',
  `create_time` int(11) NOT NULL COMMENT '插入时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `contentUnique` (`stock_id`,`crc32_value`),
  KEY `stock_id` (`stock_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='股票资讯' AUTO_INCREMENT=253412 ;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
