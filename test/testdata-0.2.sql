-- phpMyAdmin SQL Dump
-- version 3.1.1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Oct 19, 2012 at 09:30 PM
-- Server version: 5.1.66
-- PHP Version: 5.2.17

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `elplattc_seltzertest`
--

-- --------------------------------------------------------

--
-- Table structure for table `contact`
--

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
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=7 ;

--
-- Dumping data for table `contact`
--

INSERT INTO `contact` (`cid`, `firstName`, `middleName`, `lastName`, `email`, `phone`, `emergencyName`, `emergencyPhone`) VALUES
(1, 'Admin', '', 'User', 'ed@elplatt.com', '', '', ''),
(2, 'Maricela', '', 'Severin', 'mseverin@elplatt.com', '248.555.3141', 'Marcie Brode', '248.555.5926'),
(3, 'Alana', '', 'Jurgensen', 'ajurgensen@elplatt.com', '248.555.5358', 'Javier Lev', '248.555.9793'),
(4, 'Hugh', '', 'Nold', 'hnold@elplatt.com', '248.555.2384', 'Hugh Korte', '248.555.6264'),
(5, 'Marylou', '', 'Klocke', 'mklocke@elplatt.com', '248.555.3383', 'Sharron Smit', '248.555.2795'),
(6, 'Darren', '', 'Madia', 'dmadia@elplatt.com', '248.555.0288', 'Nannie Prudhomme', '248.555.4197');

-- --------------------------------------------------------

--
-- Table structure for table `key`
--

CREATE TABLE IF NOT EXISTS `key` (
  `kid` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `cid` mediumint(8) unsigned NOT NULL,
  `start` date DEFAULT NULL,
  `end` date DEFAULT NULL,
  `serial` varchar(255) NOT NULL,
  `slot` mediumint(8) unsigned NOT NULL,
  PRIMARY KEY (`kid`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=6 ;

--
-- Dumping data for table `key`
--

INSERT INTO `key` (`kid`, `cid`, `start`, `end`, `serial`, `slot`) VALUES
(1, 2, '2012-01-01', NULL, '31415-92653', 1),
(2, 3, '2012-01-01', '2012-03-01', '58979-32384', 2),
(3, 4, '2012-10-19', '2012-04-30', '62643-38327', 3),
(4, 5, '2012-04-01', NULL, '95028-84197', 4),
(5, 3, '2012-03-01', NULL, '16939-93751', 2);

-- --------------------------------------------------------

--
-- Table structure for table `member`
--

CREATE TABLE IF NOT EXISTS `member` (
  `cid` mediumint(8) unsigned NOT NULL,
  PRIMARY KEY (`cid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `member`
--

INSERT INTO `member` (`cid`) VALUES
(2),
(3),
(4),
(5),
(6);

-- --------------------------------------------------------

--
-- Table structure for table `membership`
--

CREATE TABLE IF NOT EXISTS `membership` (
  `sid` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `cid` mediumint(8) unsigned NOT NULL,
  `pid` mediumint(8) unsigned NOT NULL,
  `start` date NOT NULL,
  `end` date DEFAULT NULL,
  PRIMARY KEY (`sid`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=6 ;

--
-- Dumping data for table `membership`
--

INSERT INTO `membership` (`sid`, `cid`, `pid`, `start`, `end`) VALUES
(1, 2, 1, '2012-01-01', NULL),
(2, 3, 2, '2012-01-01', NULL),
(3, 4, 1, '2012-03-01', '2012-04-30'),
(4, 5, 2, '2012-04-01', NULL),
(5, 6, 1, '2012-05-01', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `module`
--

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
(1, 'core', 3),
(2, 'member', 2),
(3, 'key', 2);

-- --------------------------------------------------------

--
-- Table structure for table `plan`
--

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
(1, 'Standard', '39.00', 1, 0),
(2, 'Core', '89.00', 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `resetPassword`
--

CREATE TABLE IF NOT EXISTS `resetPassword` (
  `cid` mediumint(8) unsigned NOT NULL,
  `code` varchar(40) NOT NULL,
  PRIMARY KEY (`cid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `resetPassword`
--


-- --------------------------------------------------------

--
-- Table structure for table `role`
--

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

--
-- Dumping data for table `roleOld`
--


-- --------------------------------------------------------

--
-- Table structure for table `role_permission`
--

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
(1, 'admin', 'a2a3162089e61555d9292ab950d17cdbc62a7ec7', ']9y-!2"1b-caf~4<'),
(2, 'mseverin', '', ''),
(3, 'ajurgensen', '', ''),
(4, 'hnold', '', ''),
(5, 'mklocke', '', ''),
(6, 'dmadia', '', '');

-- --------------------------------------------------------

--
-- Table structure for table `user_role`
--

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
(3, 2),
(4, 2),
(5, 2),
(6, 2);
