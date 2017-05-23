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
-- 表的结构 `stock_basic`
--

CREATE TABLE IF NOT EXISTS `stock_basic` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT COMMENT '自增ID',
  `stock_code` varchar(32) NOT NULL COMMENT '股票代码',
  `stock_name` varchar(64) NOT NULL COMMENT '股票名称',
  `insert_time` int(11) NOT NULL COMMENT '插入时间',
  `stock_type` varchar(16) NOT NULL COMMENT '股票类型（sha:上海A，cyb:创业板,zxb:中小板，sza:深圳A）',
  PRIMARY KEY (`id`),
  UNIQUE KEY `stockCode` (`stock_code`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='股票基础信息' AUTO_INCREMENT=7193 ;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
