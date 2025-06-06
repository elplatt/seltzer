<?php

/*
    Copyright 2009-2025 Edward L. Platt <ed@elplatt.com>
    
    This file is part of the Seltzer CRM Project
    autocomplete.php - Provides data for autocomplete elements
    
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

// Save path of directory containing index.php
$crm_root = dirname(__FILE__);

// Bootstrap the site
include('include/crm.inc.php');

$handler = $_GET['command'] . '_autocomplete';
if (function_exists($handler)) {
    print json_encode(call_user_func($handler, $_GET['term']));
}
