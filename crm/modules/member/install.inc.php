<?php

/*
    Copyright 2009-2023 Edward L. Platt <ed@elplatt.com>
    
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
    global $db_connect;
    if ($old_revision == 1) {
        error_log('The database version is too old to upgrade to this release of ' . title() . '. Please upgrade one release at a time.');
        return;
    }
    // Initial installation
    if ($old_revision == 0) {
        // Create member table
        $sql = "
            CREATE TABLE IF NOT EXISTS `member` (
                `cid` mediumint(8) unsigned NOT NULL
                , PRIMARY KEY (`cid`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8;
        ";
        $res = mysqli_query($db_connect, $sql);
        if (!$res) crm_error(mysqli_error($db_connect));
        // Create membership table
        $sql = "
            CREATE TABLE IF NOT EXISTS `membership` (
                `sid` mediumint(8) unsigned NOT NULL AUTO_INCREMENT
                , `cid` mediumint(8) unsigned NOT NULL
                , `pid` mediumint(8) unsigned NOT NULL
                , `start` date NOT NULL
                , `end` date DEFAULT NULL
                , PRIMARY KEY (`sid`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;
        ";
        $res = mysqli_query($db_connect, $sql);
        if (!$res) crm_error(mysqli_error($db_connect));
        // Create plan table
        $sql = "
            CREATE TABLE IF NOT EXISTS `plan` (
                `pid` mediumint(8) unsigned NOT NULL AUTO_INCREMENT
                , `name` varchar(255) NOT NULL
                , `price` varchar(6) NOT NULL
                , `active` tinyint(1) NOT NULL
                , `voting` tinyint(1) NOT NULL
                , PRIMARY KEY (`pid`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;
        ";
        $res = mysqli_query($db_connect, $sql);
        if (!$res) crm_error(mysqli_error($db_connect));
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
            , 'director' => array('member_view', 'member_add', 'member_edit', 'member_delete', 'member_membership_view', 'member_membership_edit', 'member_plan_edit')
            , 'webAdmin' => array('member_view', 'member_add', 'member_edit', 'member_delete', 'member_membership_view', 'member_membership_edit', 'member_plan_edit')
        );
        foreach ($roles as $rid => $role) {
            if (array_key_exists($role, $default_perms)) {
                foreach ($default_perms[$role] as $perm) {
                    $sql = "
                        INSERT INTO `role_permission`
                        (`rid`, `permission`)
                        VALUES
                        ('$rid', '$perm')
                    ";
                    $res = mysqli_query($db_connect, $sql);
                    if (!$res) crm_error(mysqli_error($db_connect));
                }
            }
        }
    }
    if ($old_revision < 4) {
        // Alter member table
        $sql = "
            ALTER TABLE `member`
                ADD COLUMN `emergencyName` varchar(255) NOT NULL
                , ADD COLUMN `emergencyPhone` varchar(16) NOT NULL
            ;
        ";
        $res = mysqli_query($db_connect, $sql);
        if (!$res) crm_error(mysqli_error($db_connect));
        $sql = "
            UPDATE contact, member
            SET member.emergencyName=contact.emergencyName
            , member.emergencyPhone = contact.emergencyPhone
            WHERE member.cid=contact.cid;
        ";
        $res = mysqli_query($db_connect, $sql);
        if (!$res) crm_error(mysqli_error($db_connect));
        $sql = "
            ALTER TABLE `contact`
                DROP column `emergencyName`
                , DROP column `emergencyPhone`
            ;
        ";
        $res = mysqli_query($db_connect, $sql);
        if (!$res) crm_error(mysqli_error($db_connect));
    }
    if ($old_revision < 5) {
        // Alter member table
        $sql = "
            ALTER TABLE `member`
                ADD COLUMN `emergencyRelation` varchar(255) NOT NULL
            ;
        ";
        $res = mysqli_query($db_connect, $sql);
        if (!$res) crm_error(mysqli_error($db_connect));
    }
    if ($old_revision < 6) {
        // Set default permissions
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
            'director' => array('member_list')
            , 'webAdmin' => array('member_list')
        );
        foreach ($roles as $rid => $role) {
            $esc_rid = mysqli_real_escape_string($db_connect, $rid);
            if (array_key_exists($role, $default_perms)) {
                foreach ($default_perms[$role] as $perm) {
                    $esc_perm = mysqli_real_escape_string($db_connect, $perm);
                    $sql = "
                        INSERT INTO `role_permission`
                        (`rid`, `permission`)
                        VALUES
                        ('$esc_rid', '$esc_perm')
                    ";
                    $res = mysqli_query($db_connect, $sql);
                    if (!$res) crm_error(mysqli_error($db_connect));
                }
            }
        }
    }
    if ($old_revision < 7) {
        // Alter member table
        $sql = "
            ALTER TABLE `member`
                ADD COLUMN `address1` varchar(255) NOT NULL
                , ADD COLUMN `address2` varchar(255) NOT NULL
                , ADD COLUMN `address3` varchar(255) NOT NULL
                , ADD COLUMN `town_city` varchar(255) NOT NULL
                , ADD COLUMN `zipcode` varchar(255) NOT NULL
            ;
        ";
        $res = mysqli_query($db_connect, $sql);
        if (!$res) crm_error(mysqli_error($db_connect));
    }
    if ($old_revision < 8) {
        // Alter contact table
        $sql = "
            ALTER TABLE `contact`
                ADD COLUMN `createdBy` varchar(255)
                , ADD COLUMN `createdDate` date
                , ADD COLUMN `createdTime` time
            ;
        ";
        $res = mysqli_query($db_connect, $sql);
        if (!$res) crm_error(mysqli_error($db_connect));
    }
    if ($old_revision < 9) {
        // Set default permissions
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
            'member' => array('member_plan_view')
            , 'director' => array('member_plan_view')
            , 'webAdmin' => array('member_plan_view')
        );
        foreach ($roles as $rid => $role) {
            $esc_rid = mysqli_real_escape_string($db_connect, $rid);
            if (array_key_exists($role, $default_perms)) {
                foreach ($default_perms[$role] as $perm) {
                    $esc_perm = mysqli_real_escape_string($db_connect, $perm);
                    $sql = "
                        INSERT INTO `role_permission`
                        (`rid`, `permission`)
                        VALUES
                        ('$esc_rid', '$esc_perm')
                    ";
                    $res = mysqli_query($db_connect, $sql);
                    if (!$res) crm_error(mysqli_error($db_connect));
                }
            }
        }
    }
}
