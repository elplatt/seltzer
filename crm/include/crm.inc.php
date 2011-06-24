<?php

/*
    Copyright 2009-2011 Edward L. Platt <elplatt@alum.mit.edu>
    
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

// Init code ///////////////////////////////////////////////////////////////////
require_once('init.inc.php');

// Core Modules ////////////////////////////////////////////////////////////////

// Basic framework
require_once('core/core.inc.php');

// Tabbed page functionality
require_once('page/page.inc.php');

// Users and permissions
require_once('user/user.inc.php');

// Form functionality
require_once('form/form.inc.php');

// Members and contacts
require_once('member/member.inc.php');

// Key card module
require_once('key/key.inc.php');

// Non-module core functions ///////////////////////////////////////////////////

// Command handlers
require_once('command.inc.php');

// Theme functions
require_once('theme.inc.php');

?>