<?
/*
    Copyright 2009-2012 Edward L. Platt <elplatt@alum.mit.edu>
    
    This file is part of the Seltzer CRM Project
    install.inc.php - Core installation and upgrade functions

    Seltzer is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    any later version.

    Seltzer is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with Seltzer.  If not, see <http://www.gnu.org/licenses/>.
*/

/**
 * Handle installation request.
 *
 * @return The url to redirect to on completion.
 */
function command_install () {
    global $esc_post;
    
    // Check whether already installed
    $sql = "SHOW TABLES LIKE 'contact'";
    $res = mysql_query($sql);
    if (!$res) die(mysql_error());
    $row = mysql_fetch_assoc($res);
    if ($row) {
        error_register('The database must be empty before you can install Seltzer CRM!');
        return 'index.php';
    }
    
    // Create tables
    
    $sql = 'SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";';
    $res = mysql_query($sql);
    if (!$res) die(mysql_error());
    
    $sql = '
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
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;
    ';
    $res = mysql_query($sql);
    if (!$res) die(mysql_error());

    $sql = '
CREATE TABLE IF NOT EXISTS `key` (
  `kid` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `cid` mediumint(8) unsigned NOT NULL,
  `start` date DEFAULT NULL,
  `end` date DEFAULT NULL,
  `serial` varchar(255) NOT NULL,
  `slot` mediumint(8) unsigned NOT NULL,
  PRIMARY KEY (`kid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;
    ';
    $res = mysql_query($sql);
    if (!$res) die(mysql_error());

    $sql = '
CREATE TABLE IF NOT EXISTS `member` (
  `cid` mediumint(8) unsigned NOT NULL,
  PRIMARY KEY (`cid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
    ';
    $res = mysql_query($sql);
    if (!$res) die(mysql_error());
    
    $sql = '
CREATE TABLE IF NOT EXISTS `membership` (
  `sid` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `cid` mediumint(8) unsigned NOT NULL,
  `pid` mediumint(8) unsigned NOT NULL,
  `start` date NOT NULL,
  `end` date DEFAULT NULL,
  PRIMARY KEY (`sid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;
    ';
    $res = mysql_query($sql);
    if (!$res) die(mysql_error());
    
    $sql = '
CREATE TABLE IF NOT EXISTS `plan` (
  `pid` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `price` varchar(6) NOT NULL,
  `active` tinyint(1) NOT NULL,
  `voting` tinyint(1) NOT NULL,
  PRIMARY KEY (`pid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;
    ';
    $res = mysql_query($sql);
    if (!$res) die(mysql_error());
    
    $sql = '
CREATE TABLE IF NOT EXISTS `resetPassword` (
  `cid` mediumint(8) unsigned NOT NULL,
  `code` varchar(40) NOT NULL,
  PRIMARY KEY (`cid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
    ';
    $res = mysql_query($sql);
    if (!$res) die(mysql_error());
    
    $sql = '
CREATE TABLE IF NOT EXISTS `role` (
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
    ';
    $res = mysql_query($sql);
    if (!$res) die(mysql_error());
    
    $sql = '
CREATE TABLE IF NOT EXISTS `user` (
  `cid` mediumint(11) unsigned NOT NULL,
  `username` varchar(32) NOT NULL,
  `hash` varchar(40) NOT NULL,
  PRIMARY KEY (`cid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC;
    ';
    $res = mysql_query($sql);
    if (!$res) die(mysql_error());
    
    // Add admin contact and user
    $sql = "
        INSERT INTO `contact`
        (`firstName`, `lastName`, `email`)
        VALUES
        ('Admin', 'User', '$esc_post[email]')
    ";
    $res = mysql_query($sql);
    if (!$res) die(mysql_error());
    $cid = mysql_insert_id();
    
    $hash = mysql_real_escape_string(sha1($_POST['password']));
    $sql = "
        INSERT INTO `user`
        (`cid`, `username`, `hash`)
        VALUES
        ('$cid', 'admin', '$hash')
    ";
    $res = mysql_query($sql);
    if (!$res) die(mysql_error());

    // Add all roles for admin
    $sql = "
        INSERT INTO `role`
        (`cid`, `member`, `director`, `president`, `vp`, `secretary`, `treasurer`, `webAdmin`)
        VALUES
        ('$cid', '1', '1', '1', '1', '1', '1', '1')
    ";
    $res = mysql_query($sql);
    if (!$res) die(mysql_error());
    
    message_register('Seltzer CRM has been installed.');
    message_register('You may log in as user "admin"');
    return 'login.php';
}
