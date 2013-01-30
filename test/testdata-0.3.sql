-- phpMyAdmin SQL Dump
-- version 3.1.1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Jan 30, 2013 at 04:47 PM
-- Server version: 5.5.29
-- PHP Version: 5.2.17

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `elplattc_seltzer`
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
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=7 ;

--
-- Dumping data for table `contact`
--

INSERT INTO `contact` (`cid`, `firstName`, `middleName`, `lastName`, `email`, `phone`, `emergencyName`, `emergencyPhone`) VALUES
(1, 'Admin', '', 'User', 'rockymcrockerson@gmail.com', '', '', ''),
(2, 'Maricela', '', 'Severin', 'mseverin@elplatt.com', '248.555.3141', 'Marcie Brode', '248.555.5926'),
(3, 'Alana', '', 'Jurgensen', 'ajurgensen@elplatt.com', '248.555.5358', 'Javier Lev', '248.555.9793'),
(4, 'Hugh', '', 'Nold', 'hnold@elplatt.com', '248.555.2384', 'Hugh Korte', '248.555.6264'),
(5, 'Marylou', '', 'Klocke', 'mklocke@elplatt.com', '248.555.3383', 'Sharron Smit', '248.555.2795'),
(6, 'Darren', '', 'Madia', 'dmadia@elplatt.com', '248.555.0288', 'Nannie Prudhomme', '248.555.4197');

-- --------------------------------------------------------

--
-- Table structure for table `contact_amazon`
--

DROP TABLE IF EXISTS `contact_amazon`;
CREATE TABLE IF NOT EXISTS `contact_amazon` (
  `cid` mediumint(8) unsigned NOT NULL,
  `amazon_name` varchar(255) NOT NULL,
  PRIMARY KEY (`amazon_name`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `contact_amazon`
--


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
(3),
(4),
(5),
(6);

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

DROP TABLE IF EXISTS `module`;
CREATE TABLE IF NOT EXISTS `module` (
  `did` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `revision` mediumint(8) unsigned NOT NULL,
  PRIMARY KEY (`did`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=9 ;

--
-- Dumping data for table `module`
--

INSERT INTO `module` (`did`, `name`, `revision`) VALUES
(1, 'core', 3),
(2, 'member', 2),
(3, 'key', 2),
(4, 'variable', 1),
(8, 'payment', 1),
(6, 'amazon_payment', 1),
(7, 'billing', 1);

-- --------------------------------------------------------

--
-- Table structure for table `payment`
--

DROP TABLE IF EXISTS `payment`;
CREATE TABLE IF NOT EXISTS `payment` (
  `pmtid` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `date` date DEFAULT NULL,
  `description` varchar(255) NOT NULL,
  `code` varchar(8) NOT NULL,
  `value` mediumint(8) NOT NULL,
  `credit` mediumint(8) unsigned NOT NULL,
  `debit` mediumint(8) unsigned NOT NULL,
  `method` varchar(255) NOT NULL,
  `confirmation` varchar(255) NOT NULL,
  `notes` text NOT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`pmtid`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=6 ;

--
-- Dumping data for table `payment`
--

INSERT INTO `payment` (`pmtid`, `date`, `description`, `code`, `value`, `credit`, `debit`, `method`, `confirmation`, `notes`, `created`) VALUES
(1, '2012-01-01', 'Dues payment', 'USD', 8900, 3, 0, 'cash', '1', '', '2013-01-30 16:41:08'),
(2, '2012-02-01', 'Dues payment', 'USD', 8900, 3, 0, 'cash', '2', '', '2013-01-30 16:42:14'),
(3, '2012-03-01', 'Dues payment', 'USD', 8900, 3, 0, 'cash', '3', '', '2013-01-30 16:42:43'),
(4, '2012-04-01', 'Dues payment', 'USD', 17800, 3, 0, 'cheque', '1001', '', '2013-01-30 16:43:26'),
(5, '2012-04-01', 'Dues payment', 'USD', 8900, 5, 3, 'cash', '1001', '', '2013-01-30 16:44:12');

-- --------------------------------------------------------

--
-- Table structure for table `payment_amazon`
--

DROP TABLE IF EXISTS `payment_amazon`;
CREATE TABLE IF NOT EXISTS `payment_amazon` (
  `pmtid` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `amazon_name` varchar(255) NOT NULL,
  PRIMARY KEY (`pmtid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

--
-- Dumping data for table `payment_amazon`
--


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
(1, 'Standard', '39.00', 1, 0),
(2, 'Core', '89.00', 1, 1);

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

--
-- Dumping data for table `resetPassword`
--


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

--
-- Dumping data for table `roleOld`
--


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
(3, 'payment_delete'),
(3, 'payment_edit'),
(3, 'payment_view'),
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
(3, 2),
(4, 2),
(5, 2),
(6, 2);

-- --------------------------------------------------------

--
-- Table structure for table `variable`
--

DROP TABLE IF EXISTS `variable`;
CREATE TABLE IF NOT EXISTS `variable` (
  `name` varchar(255) NOT NULL,
  `value` text NOT NULL,
  PRIMARY KEY (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Dumping data for table `variable`
--

