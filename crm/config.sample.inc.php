<?php

/*
    Copyright 2009-2013 Edward L. Platt <ed@elplatt.com>
    
    This file is part of the Seltzer CRM Project
    config.inc.php - Sample configuration

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

// Database configuration
$config_db_host = 'localhost';
$config_db_user = '';
$config_db_password = '';
$config_db_db = '';

// Site info

// The title to display in the title bar
$config_site_title = 'Seltzer CRM';

// The name of the organization to insert into templates
$config_org_name = 'Seltzer CRM';

// The currency code for dealing with payments, can be GBP, USD, or EUR 
$config_currency_code = 'USD';

// The From: address to use when sending email to members
$config_email_from = '';

// The email address to notify when a user is created
$config_email_to = '';

// The hostname of the server
$config_host = $_SERVER['SERVER_NAME'];

// The url path of the crm directory
$config_base_path = '/crm/';

// The name of the theme you want to use
// (currently there is only one, "inspire".)
$config_theme = "inspire";

// Modules
$config_modules = array(
    "contact",
    "user",
    "variable",
    "member",
    "key",
    "payment",
    "amazon_payment",
    "paypal_payment",
    "billing"
);

// Links to show in the main menu
$config_links = array(
    '<front>' => 'Home'
    , 'members' => 'Members'
    , 'plans' => 'Plans'
    , 'keys' => 'Keys'
    , 'payments' => 'Payments'
    , 'reports' => 'Reports'
    , 'permissions' => 'Permissions'
    , 'upgrade' => 'Upgrade'
);
