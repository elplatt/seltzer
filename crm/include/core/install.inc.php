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
 * Install or upgrade this module.
 * @param $old_revision The last installed revision of this module, or 0 if the
 *   module has never been installed.
 */
function core_install ($old_revision = 0) {
    
    if ($old_revision < 1) {
        $sql = 'SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";';
        $res = mysql_query($sql);
        if (!$res) die(mysql_error());
        
        $sql = '
            CREATE TABLE IF NOT EXISTS `module` (
                `did` MEDIUMINT(8) unsigned NOT NULL AUTO_INCREMENT,
                `name` VARCHAR(255) NOT NULL,
                `revision` MEDIUMINT(8) unsigned NOT NULL,
                PRIMARY KEY (`did`)
            ) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;
        ';
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
    }
}
