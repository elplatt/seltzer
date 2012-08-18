<?php 

/*
    Copyright 2009-2011 Edward L. Platt <elplatt@alum.mit.edu>
    
    This file is part of the Seltzer CRM Project
    member.inc.php - Member module

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

// Installation functions //////////////////////////////////////////////////////

/**
 * @return This module's revision number.  Each new release should increment
 * this number.
 */
function member_revision () {
    return 1;
}

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

// Utility functions ///////////////////////////////////////////////////////////
require_once('utility.inc.php');

// DB to Object mapping ////////////////////////////////////////////////////////
require_once('data.inc.php');

// Table data structures ///////////////////////////////////////////////////////
require_once('table.inc.php');

// Forms ///////////////////////////////////////////////////////////////////////
require_once('form.inc.php');

// Request Handlers ////////////////////////////////////////////////////////////
require_once('command.inc.php');

// Member pages ////////////////////////////////////////////////////////////////
require_once('page.inc.php');

// Member reports //////////////////////////////////////////////////////////////
require_once('report.inc.php');

// Themeing ////////////////////////////////////////////////////////////////////
require_once('theme.inc.php');

?>