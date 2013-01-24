<?php

/*
    Copyright 2009-2013 Edward L. Platt <ed@elplatt.com>
    
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
require_once('config.inc.php');

// Add core modules
array_unshift($config_modules, 'core');

// Optional Modules ////////////////////////////////////////////////////////////
foreach ($config_modules as $module) {
    require_once($module . '/' . $module . '.inc.php');
}

// Initialize //////////////////////////////////////////////////////////////////
foreach ($config_modules as $module) {
    $init = $module . '_init';
    if (function_exists($init)) {
        call_user_func($init);
    }
}
