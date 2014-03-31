<?php 

/*
    Copyright 2009-2014 Edward L. Platt <ed@elplatt.com>
    
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
    if ($old_revision == 1) {
        error_log('The database version is too old to upgrade to this release of ' . title(). '.  Please upgrade one release at a time.');
        return;
    }
    // Initial installation
    if ($old_revision == 0) {
        // Create member table
        $sql = '
            CREATE TABLE IF NOT EXISTS `member` (
              `cid` mediumint(8) unsigned NOT NULL,
              PRIMARY KEY (`cid`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8;
        ';
        $res = mysql_query($sql);
        if (!$res) die(mysql_error());
        // Create membership table
        $sql = '
            CREATE TABLE IF NOT EXISTS `membership` (
              `sid` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
              `cid` mediumint(8) unsigned NOT NULL,
              `pid` mediumint(8) unsigned NOT NULL,
              `start` date NOT NULL,
              `end` date DEFAULT NULL,
              PRIMARY KEY (`sid`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
        ';
        $res = mysql_query($sql);
        if (!$res) die(mysql_error());
        // Create plan table
        $sql = '
            CREATE TABLE IF NOT EXISTS `plan` (
              `pid` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
              `name` varchar(255) NOT NULL,
              `price` varchar(6) NOT NULL,
              `active` tinyint(1) NOT NULL,
              `voting` tinyint(1) NOT NULL,
              PRIMARY KEY (`pid`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
        ';
        $res = mysql_query($sql);
        if (!$res) die(mysql_error());
        // Create default permissions
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
        $default_perms = array(
            'member' => array('member_view', 'member_membership_view')
            , 'director' => array('member_view', 'member_add', 'member_edit', 'member_delete', 'member_membership_view', 'member_membership_edit',  'member_plan_edit')
            , 'webAdmin' => array('member_view', 'member_add', 'member_edit', 'member_delete', 'member_membership_view', 'member_membership_edit',  'member_plan_edit')
        );
        foreach ($roles as $rid => $role) {
            if (array_key_exists($role, $default_perms)) {
                foreach ($default_perms[$role] as $perm) {
                    $sql = "INSERT INTO `role_permission` (`rid`, `permission`) VALUES ('$rid', '$perm')";
                    $res = mysql_query($sql);
                    if (!$res) die(mysql_error());
                }
            }
        }
    }
}
