<?php

/*
    Copyright 2024 Ilias Daradimos <judgedrid@gmail.com>
    
    This file is part of the Seltzer CRM Project
    service.php - Provides service endpoints
    
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

$handler = $_GET['endpoint'] . '_service';
//TODO: Check against registered endpoint in conf api_endpoints array
if (function_exists($handler)) {
    //TODO: Some sanitize may be needed here
    $url_params = $_GET;
    if (isset($_SERVER['CONTENT_TYPE']) && $_SERVER['CONTENT_TYPE'] === 'application/json') {
        $rawData = file_get_contents('php://input');
        $jsonData = json_decode($rawData, true);
    
        if (json_last_error() !== JSON_ERROR_NONE) {
            header('Status: 400 Bad Request');
            echo "Malformed JSON payload";
            exit;
        }

    }
    print json_encode(call_user_func($handler, $url_params, $jsonData), JSON_NUMERIC_CHECK);
} else {
    header('Status: 404 Not Found');
    echo 'Endpoint "'.$_GET['endpoint'].'" not found';
    exit;
}
