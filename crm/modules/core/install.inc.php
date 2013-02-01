<?
/*
    Copyright 2009-2013 Edward L. Platt <ed@elplatt.com>
    
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
    
    // Switch to tracking permissions and roles in the database 2012-09-05
    if ($old_revision < 2) {
        // Rename old role table
        $sql = '
            ALTER TABLE `role`
            RENAME TO `roleOld`
        ';
        $res = mysql_query($sql);
        if (!$res) die(mysql_error());
        
        // Create new table to hold roles
        $sql = '
            CREATE TABLE IF NOT EXISTS `role` (
              `rid` mediumint(9) NOT NULL AUTO_INCREMENT,
              `name` varchar(255) NOT NULL,
              PRIMARY KEY (`rid`)
            ) ENGINE=MyISAM  DEFAULT CHARSET=latin1;
        ';
        $res = mysql_query($sql);
        if (!$res) die(mysql_error());
        
        // Create new union table for users and roles
        $sql = '
            CREATE TABLE IF NOT EXISTS `user_role` (
              `cid` mediumint(8) unsigned NOT NULL,
              `rid` mediumint(8) unsigned NOT NULL,
              PRIMARY KEY (`cid`,`rid`)
            ) ENGINE=MyISAM DEFAULT CHARSET=latin1;
        ';
        $res = mysql_query($sql);
        if (!$res) die(mysql_error());
        
        // Create new table to store permissions for each role
        $sql = '
            CREATE TABLE IF NOT EXISTS `role_permission` (
              `rid` mediumint(8) unsigned NOT NULL,
              `permission` varchar(255) NOT NULL,
              PRIMARY KEY (`rid`,`permission`)
            ) ENGINE=MyISAM DEFAULT CHARSET=latin1;
        ';
        $res = mysql_query($sql);
        if (!$res) die(mysql_error());
        
        // Create default roles
        $roles = array(
            '1' => 'authenticated'
            , '2' => 'member'
            , '3' => 'director'
            , '4' => 'president'
            , '5' => 'vp'
            , '6' => 'secretary'
            , '7' => 'treasurer'
            , '8' => 'webAdmin'
        );
        foreach ($roles as $rid => $role) {
            $sql = "INSERT INTO `role` (`rid`, `name`) VALUES ('$rid', '$role')";
            $res = mysql_query($sql);
            if (!$res) die(mysql_error());
        }
        
        // Copy existing user roles from old table
        $sql = "SELECT `cid`, `member`, `director`, `president`, `vp`, `secretary`, `treasurer`, `webAdmin` FROM `roleOld`";
        $res = mysql_query($sql);
        if (!$res) die(mysql_error());
        $row = mysql_fetch_array($res);
        while ($row) {
            $esc_cid = mysql_real_escape_string($row[cid]);
            foreach ($roles as $rid => $role) {
                if (array_key_exists($role, $row) && $row[$role]) {
                    $esc_rid = mysql_real_escape_string($rid);
                    $insert_sql = "INSERT INTO `user_role` (`cid`, `rid`) VALUES ('$esc_cid', '$esc_rid')";
                    $insert_res = mysql_query($insert_sql);
                    if (!$insert_res) die(mysql_error());
                }
            }
            $row = mysql_fetch_array($res);
        }
        
        // Set default permissions
        $default_perms = array(
            'authenticated' => array('report_view')
            , 'member' => array('contact_view')
            , 'director' => array('user_add', 'user_edit', 'user_delete', 'user_role_edit', 'user_permissions_edit', 'module_upgrade', 'contact_view', 'contact_add', 'contact_edit', 'contact_delete')
        );
        foreach ($roles as $rid => $role) {
            $esc_rid = mysql_real_escape_string($rid);
            if (array_key_exists($role, $default_perms)) {
                foreach ($default_perms[$role] as $perm) {
                    $esc_perm = mysql_real_escape_string($perm);
                    $sql = "INSERT INTO `role_permission` (`rid`, `permission`) VALUES ('$esc_rid', '$esc_perm')";
                    $res = mysql_query($sql);
                    if (!$res) die(mysql_error());
                }
            }
        }
    }
    
    // Add password salt 2012-09-30
    if ($old_revision < 3) {
        $sql = "ALTER TABLE `user` ADD COLUMN `salt` varchar(16) NOT NULL";
        $res = mysql_query($sql);
        if (!$res) die(mysql_error());
    }
}
