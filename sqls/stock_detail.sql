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
-- 表的结构 `stock_detail`
--

CREATE TABLE IF NOT EXISTS `stock_detail` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `stock_id` bigint(20) NOT NULL COMMENT '股票ID',
  `date` char(8) NOT NULL COMMENT '日期',
  `open_price` float NOT NULL COMMENT '开盘价',
  `highest_price` float NOT NULL COMMENT '最高价',
  `lowest_price` float NOT NULL COMMENT '最低价',
  `price` float NOT NULL COMMENT '收盘价',
  `turnover_size` int(11) NOT NULL COMMENT '成交量',
  `turnover_money` float NOT NULL COMMENT '成交金额',
  `turnover_rate` float NOT NULL COMMENT '换手率',
  `advance_decline` float NOT NULL COMMENT '涨幅',
  `advance_decline_rate` float NOT NULL COMMENT '涨幅比例',
  `insert_time` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `stock_id_2` (`stock_id`,`date`),
  KEY `stock_id` (`stock_id`),
  KEY `date_index` (`date`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='股票价格详情' AUTO_INCREMENT=7976149 ;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
