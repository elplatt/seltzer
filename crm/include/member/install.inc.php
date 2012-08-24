<?php 

/*
    Copyright 2009-2012 Edward L. Platt <elplatt@alum.mit.edu>
    
    This file is part of the Seltzer CRM Project
    install.inc.php - Member module installation code

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
function member_install($old_revision = 0) {
    if ($old_revision < 1) {
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
    }
}
