<?php

/*
    Copyright 2009-2013 Edward L. Platt <ed@elplatt.com>
    
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
 * @return An array of the permissions provided by this module.
 */
function core_permissions () {
    $permissions = array_merge(
        module_permissions()
        , array('report_view')
    );
    return $permissions;
}

// Core module elements ////////////////////////////////////////////////////////

require_once('init.inc.php');           // Core module initialization
require_once('install.inc.php');        // Core database schema
require_once('form.inc.php');           // Core database schema
require_once('page.inc.php');           // Core pages
require_once('theme.inc.php');          // Core element theming

?>