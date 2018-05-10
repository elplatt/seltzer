<?php

/*
    Copyright 2009-2018 Edward L. Platt <ed@elplatt.com>
    
    This file is part of the Seltzer CRM Project
    core.inc.php - Core functions
    
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
 * @return This module's revision number.  Each new release should increment
 * this number.
 */
function core_revision () {
    return 3;
}

/**
 * Install or upgrade this module.
 * @param $old_revision The last installed revision of this module, or 0 if the
 *   module has never been installed.
 */
function core_install ($old_revision = 0) {
    global $db_connect;
    if ($old_revision < 1) {
        $sql = 'SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";';
        $res = mysqli_query($db_connect, $sql);
        if (!$res) crm_error(mysqli_error($res));
        
        $sql = '
            CREATE TABLE IF NOT EXISTS `module` (
                `did` MEDIUMINT(8) unsigned NOT NULL AUTO_INCREMENT,
                `name` VARCHAR(255) NOT NULL,
                `revision` MEDIUMINT(8) unsigned NOT NULL,
                PRIMARY KEY (`did`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
        ';
        $res = mysqli_query($db_connect, $sql);
        if (!$res) crm_error(mysqli_error($res));
    }
}

/**
 * @return An array of the permissions provided by this module.
 */
function core_permissions () {
    $permissions = array_merge(
        module_permissions()
        , array('report_view')
    );
    return $permissions;
}
