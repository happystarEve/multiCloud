-- phpMyAdmin SQL Dump
-- version 4.1.14
-- http://www.phpmyadmin.net
--
-- Host: 127.0.0.1
-- Generation Time: 2017-07-01 09:22:25
-- 服务器版本： 5.6.17
-- PHP Version: 5.5.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `cloud`
--

-- --------------------------------------------------------

--
-- 表的结构 `foldertree`
--

CREATE TABLE IF NOT EXISTS `foldertree` (
  `folderId` int(20) unsigned NOT NULL AUTO_INCREMENT COMMENT '文件夹Id',
  `parentId` int(20) unsigned NOT NULL DEFAULT '1' COMMENT '父文件夹Id',
  `folderName` varchar(80) NOT NULL COMMENT '文件夹名',
  PRIMARY KEY (`folderId`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=6 ;

-- --------------------------------------------------------

--
-- 表的结构 `object`
--

CREATE TABLE IF NOT EXISTS `object` (
  `objectId` int(20) NOT NULL AUTO_INCREMENT COMMENT '对象Id',
  `userId` int(20) NOT NULL COMMENT '用户Id',
  `securityId` int(20) NOT NULL COMMENT '所用的安全策略',
  `objectName` varchar(200) NOT NULL COMMENT '对象名称',
  `dataSecretEncrypted` varchar(80) NOT NULL COMMENT '数据密钥',
  `objectSize` int(20) NOT NULL COMMENT '数据大小',
  `objectDate` int(30) NOT NULL COMMENT '数据修改时间',
  PRIMARY KEY (`objectId`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=23 ;

-- --------------------------------------------------------

--
-- 表的结构 `security`
--

CREATE TABLE IF NOT EXISTS `security` (
  `securityId` int(20) unsigned NOT NULL AUTO_INCREMENT COMMENT '安全策略Id',
  `securityName` varchar(80) NOT NULL COMMENT '安全策略名称',
  PRIMARY KEY (`securityId`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=5 ;

--
-- 转存表中的数据 `security`
--

INSERT INTO `security` (`securityId`, `securityName`) VALUES
(1, 'baiduCloud'),
(2, 'aliCloud'),
(3, 'dualStorage'),
(4, 'halfStorage');

-- --------------------------------------------------------

--
-- 表的结构 `user`
--

CREATE TABLE IF NOT EXISTS `user` (
  `userId` int(20) unsigned NOT NULL AUTO_INCREMENT COMMENT '用户Id',
  `userName` varchar(80) NOT NULL COMMENT '用户名',
  `passwordHashs` varchar(80) NOT NULL COMMENT '两次哈希加密密码',
  `userSecretHash` varchar(80) NOT NULL COMMENT '哈希密钥',
  `userSecretEncrypted` varchar(200) NOT NULL COMMENT '加密密钥',
  `sex` varchar(8) DEFAULT NULL COMMENT '性别',
  `cellphone` varchar(16) NOT NULL COMMENT '手机号码',
  `email` varchar(80) NOT NULL COMMENT '邮箱',
  `birthday` int(20) DEFAULT NULL COMMENT '生日',
  `selectedSecurity` int(20) NOT NULL DEFAULT '2' COMMENT '所选择的安全策略ID',
  `bucketName` varchar(80) NOT NULL COMMENT '存储空间名字',
  PRIMARY KEY (`userId`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2 ;

--
-- 转存表中的数据 `user`
--

INSERT INTO `user` (`userId`, `userName`, `passwordHashs`, `userSecretHash`, `userSecretEncrypted`, `sex`, `cellphone`, `email`, `birthday`, `selectedSecurity`, `bucketName`) VALUES
(1, 'eve', '14e1b600b1fd579f47433b88e8d85291', 'a55b18c55087451e91c2669f8f40146b', 'X4X1ojQ3RQJQna+6O2J+NflRIZr0Qlf5daI7ZlyEl28D9V8sCmQYFPynlFhlkMAh', 'female', '', '', 756576000, 4, 'eve-cloud');

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
