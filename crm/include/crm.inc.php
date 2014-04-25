<?php

/*
    Copyright 2009-2014 Edward L. Platt <ed@elplatt.com>
    
    This file is part of the Seltzer CRM Project
    crm.inc.php - Loads all core libraries and modules

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

// Configuration ///////////////////////////////////////////////////////////////
$crm_version = array(
    'major' => 0
    , 'minor' => 4
    , 'patch' => 1
    , 'revision' => 'dev'
);
require_once($crm_root . '/config.inc.php');

// Include base system /////////////////////////////////////////////////////////
require_once('sys/util.inc.php');       // Generic utility functions
require_once('sys/csv.inc.php');        // CSV parser
require_once('sys/command.inc.php');    // Command processing system
require_once('sys/theme.inc.php');      // Theme system
require_once('sys/template.inc.php');   // Template system
require_once('sys/error.inc.php');      // Error reporting system
require_once('sys/form.inc.php');       // Form system
require_once('sys/table.inc.php');      // Table system
require_once('sys/module.inc.php');     // Module system
require_once('sys/page.inc.php');       // Page system
require_once('sys/data.inc.php');       // DB-to-object system
require_once('sys/init.inc.php');       // Pre-module initialization

// Include core content ////////////////////////////////////////////////////////
require_once('form.inc.php');
require_once('page.inc.php');
require_once('reports.inc.php');
require_once('theme.inc.php');

// Add core modules
array_unshift($config_modules, 'core');

// Optional Modules ////////////////////////////////////////////////////////////
foreach ($config_modules as $module) {
    require_once($crm_root. '/modules/' . $module . '/' . $module . '.inc.php');
}

// Initialize //////////////////////////////////////////////////////////////////
page_init();
foreach ($config_modules as $module) {
    $init = $module . '_init';
    if (function_exists($init)) {
        call_user_func($init);
    }
}
