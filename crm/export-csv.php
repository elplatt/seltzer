<?php

/*
    Copyright 2009-2021 Edward L. Platt <ed@elplatt.com>
    
    This file is part of the Seltzer CRM Project
    export-csv.php - Exports a table to csv format.
    
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
require_once('include/crm.inc.php');

if (!user_id()) {
    crm_error("ERROR: User not logged in");
}

header('Content-type: text/csv');
header('Content-Disposition: attachment; filename="export.csv"');
print theme('table_csv', $_GET['name'], json_decode($_GET['opts'], true));
