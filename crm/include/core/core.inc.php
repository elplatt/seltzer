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
        user_permissions()
        , module_permissions()
        , array('report_view')
    );
    return $permissions;
}

// Subsystems //////////////////////////////////////////////////////////////////

require_once('sys/init.inc.php');       // Pre-module initialization
require_once('sys/util.inc.php');       // Generic utility functions
require_once('sys/csv.inc.php');        // CSV parser
require_once('sys/command.inc.php');    // Comand processing system
require_once('sys/theme.inc.php');      // Theme system
require_once('sys/template.inc.php');   // Template system
require_once('sys/error.inc.php');      // Error reporting system
require_once('sys/form.inc.php');       // Form system
require_once('sys/table.inc.php');      // Table system
require_once('sys/user.inc.php');       // User auth and permissions system
require_once('sys/module.inc.php');     // Module system
require_once('sys/page.inc.php');       // Page system

// Core module elements ////////////////////////////////////////////////////////

require_once('init.inc.php');           // Core module initialization
require_once('install.inc.php');        // Core database schema
require_once('form.inc.php');           // Core database schema
require_once('page.inc.php');           // Core pages
require_once('theme.inc.php');          // Core element theming

?>