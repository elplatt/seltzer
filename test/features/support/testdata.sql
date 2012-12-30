-- phpMyAdmin SQL Dump
-- version 3.4.5deb1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Dec 30, 2012 at 09:34 AM
-- Server version: 5.1.63
-- PHP Version: 5.3.6-13ubuntu3.9

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `seltzerdb`
--

-- --------------------------------------------------------

--
-- Table structure for table `contact`
--

DROP TABLE IF EXISTS `contact`;
CREATE TABLE IF NOT EXISTS `contact` (
  `cid` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `firstName` varchar(255) NOT NULL,
  `middleName` varchar(255) NOT NULL,
  `lastName` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(32) NOT NULL,
  `emergencyName` varchar(255) NOT NULL,
  `emergencyPhone` varchar(16) NOT NULL,
  PRIMARY KEY (`cid`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=4 ;

--
-- Dumping data for table `contact`
--

INSERT INTO `contact` (`cid`, `firstName`, `middleName`, `lastName`, `email`, `phone`, `emergencyName`, `emergencyPhone`) VALUES
(1, 'Admin', '', 'User', 'root@localhost', '', '', ''),
(2, 'Bobby', 'J', 'Brady', 'root@localhost', '555-1212', 'Alice', '555-1212'),
(3, 'Cindy', 'J', 'Brady', 'root@localhost', '555-1212', 'Alice', '555-1212');

-- --------------------------------------------------------

--
-- Table structure for table `key`
--

DROP TABLE IF EXISTS `key`;
CREATE TABLE IF NOT EXISTS `key` (
  `kid` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `cid` mediumint(8) unsigned NOT NULL,
  `start` date DEFAULT NULL,
  `end` date DEFAULT NULL,
  `serial` varchar(255) NOT NULL,
  `slot` mediumint(8) unsigned NOT NULL,
  PRIMARY KEY (`kid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `member`
--

DROP TABLE IF EXISTS `member`;
CREATE TABLE IF NOT EXISTS `member` (
  `cid` mediumint(8) unsigned NOT NULL,
  PRIMARY KEY (`cid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `member`
--

INSERT INTO `member` (`cid`) VALUES
(2),
(3);

-- --------------------------------------------------------

--
-- Table structure for table `membership`
--

DROP TABLE IF EXISTS `membership`;
CREATE TABLE IF NOT EXISTS `membership` (
  `sid` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `cid` mediumint(8) unsigned NOT NULL,
  `pid` mediumint(8) unsigned NOT NULL,
  `start` date NOT NULL,
  `end` date DEFAULT NULL,
  PRIMARY KEY (`sid`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=4 ;

--
-- Dumping data for table `membership`
--

INSERT INTO `membership` (`sid`, `cid`, `pid`, `start`, `end`) VALUES
(1, 2, 0, '2012-12-01', NULL),
(2, 2, 1, '2012-12-29', NULL),
(3, 3, 2, '2012-12-01', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `module`
--

DROP TABLE IF EXISTS `module`;
CREATE TABLE IF NOT EXISTS `module` (
  `did` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `revision` mediumint(8) unsigned NOT NULL,
  PRIMARY KEY (`did`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=4 ;

--
-- Dumping data for table `module`
--

INSERT INTO `module` (`did`, `name`, `revision`) VALUES
(1, 'core', 2),
(2, 'member', 2),
(3, 'key', 2);

-- --------------------------------------------------------

--
-- Table structure for table `plan`
--

DROP TABLE IF EXISTS `plan`;
CREATE TABLE IF NOT EXISTS `plan` (
  `pid` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `price` varchar(6) NOT NULL,
  `active` tinyint(1) NOT NULL,
  `voting` tinyint(1) NOT NULL,
  PRIMARY KEY (`pid`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=3 ;

--
-- Dumping data for table `plan`
--

INSERT INTO `plan` (`pid`, `name`, `price`, `active`, `voting`) VALUES
(1, 'Big Spender', '1000', 1, 1),
(2, 'Cheapskate', '1', 1, 0);

-- --------------------------------------------------------

--
-- Table structure for table `resetPassword`
--

DROP TABLE IF EXISTS `resetPassword`;
CREATE TABLE IF NOT EXISTS `resetPassword` (
  `cid` mediumint(8) unsigned NOT NULL,
  `code` varchar(40) NOT NULL,
  PRIMARY KEY (`cid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;


-- --------------------------------------------------------

--
-- Table structure for table `role`
--

DROP TABLE IF EXISTS `role`;
CREATE TABLE IF NOT EXISTS `role` (
  `rid` mediumint(9) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`rid`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=9 ;

--
-- Dumping data for table `role`
--

INSERT INTO `role` (`rid`, `name`) VALUES
(1, 'authenticated'),
(2, 'member'),
(3, 'director'),
(4, 'president'),
(5, 'vp'),
(6, 'secretary'),
(7, 'treasurer'),
(8, 'webAdmin');

-- --------------------------------------------------------

--
-- Table structure for table `roleOld`
--

DROP TABLE IF EXISTS `roleOld`;
CREATE TABLE IF NOT EXISTS `roleOld` (
  `cid` mediumint(8) unsigned NOT NULL,
  `member` tinyint(1) NOT NULL,
  `director` tinyint(1) NOT NULL,
  `president` tinyint(1) NOT NULL,
  `vp` tinyint(1) NOT NULL,
  `secretary` tinyint(1) NOT NULL,
  `treasurer` tinyint(1) NOT NULL,
  `webAdmin` tinyint(1) NOT NULL,
  PRIMARY KEY (`cid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `role_permission`
--

DROP TABLE IF EXISTS `role_permission`;
CREATE TABLE IF NOT EXISTS `role_permission` (
  `rid` mediumint(8) unsigned NOT NULL,
  `permission` varchar(255) NOT NULL,
  PRIMARY KEY (`rid`,`permission`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `role_permission`
--

INSERT INTO `role_permission` (`rid`, `permission`) VALUES
(1, 'report_view'),
(2, 'contact_view'),
(2, 'member_membership_view'),
(2, 'member_view'),
(3, 'contact_add'),
(3, 'contact_delete'),
(3, 'contact_edit'),
(3, 'contact_view'),
(3, 'key_delete'),
(3, 'key_edit'),
(3, 'key_view'),
(3, 'member_add'),
(3, 'member_delete'),
(3, 'member_edit'),
(3, 'member_membership_edit'),
(3, 'member_membership_view'),
(3, 'member_plan_edit'),
(3, 'member_view'),
(3, 'module_upgrade'),
(3, 'user_add'),
(3, 'user_delete'),
(3, 'user_edit'),
(3, 'user_permissions_edit'),
(3, 'user_role_edit');

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

DROP TABLE IF EXISTS `user`;
CREATE TABLE IF NOT EXISTS `user` (
  `cid` mediumint(11) unsigned NOT NULL,
  `username` varchar(32) NOT NULL,
  `hash` varchar(40) NOT NULL,
  `salt` varchar(16) NOT NULL,
  PRIMARY KEY (`cid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`cid`, `username`, `hash`, `salt`) VALUES
(1, 'admin', 'a3cc77bdbed55015abfa94eafa67ed2f9b51496d', '7~[/,~/]27sngi0?'),
(2, 'bbrady', '5db451df895854dd994d05638c63167e506e46b6', '2j+%e!)zs+8}),03'),
(3, 'Cbrady', '6f42897de945ef7920a6ca1aad74ca777321ac38', '0g>]o6wvu`>/o]~&');

-- --------------------------------------------------------

--
-- Table structure for table `user_role`
--

DROP TABLE IF EXISTS `user_role`;
CREATE TABLE IF NOT EXISTS `user_role` (
  `cid` mediumint(8) unsigned NOT NULL,
  `rid` mediumint(8) unsigned NOT NULL,
  PRIMARY KEY (`cid`,`rid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `user_role`
--

INSERT INTO `user_role` (`cid`, `rid`) VALUES
(2, 2),
(3, 2);

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
