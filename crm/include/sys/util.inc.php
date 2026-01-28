<?php

/*
    Copyright 2009-2026 Edward L. Platt <ed@elplatt.com>
    
    This file is part of the Seltzer CRM Project
    util.inc.php - Core utility functions
    
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
 * @return The base path to the directory containing index.php.
 */
function base_path () {
    global $config_base_path;
    $variable_base_path = variable_get('base_path', $config_base_path);
    return $variable_base_path;
}

/**
 * @return the path to the current page.
 */
function path () {
    return array_key_exists('q', $_GET) ? $_GET['q'] : '';
}

/**
 * @return The title of the site.
 */
function title () {
    global $config_site_title;
    $variable_site_title = variable_get('site_title', $config_site_title);
    return $variable_site_title;
}

/**
 * @return An array of navigation links
 */
function links () {
    global $config_links;
    return $config_links;
}

/**
 * Return a url to an internal path.
 * @param $path The path to convert to a url.
 * @param $opts An associative array of options.  Keys are:
 *   'query' - An array of query paramters to add to the url.
 * @return A string containing the url.
 */
function crm_url ($path = '', $opts = array()) {
    $url = base_path() . "index.php?";
    $terms = array();
    // Construct terms of the query string
    if ($path != '<front>') {
        $terms[] = "q=$path";
    }
    if (isset($opts['query'])) {
        foreach ($opts['query'] as $key => $value) {
            $terms[] = "$key=$value";
        }
    }
    $url .= implode('&', $terms);
    return $url;
}

/**
 * Return a link.
 * @param $text The text of the link.
 * @param $path The path to link to.
 * @param $opts Options array to pass to crm_url().
 * @return A string containing the html link.
 */
function crm_link ($text, $path, $opts = array()) {
    return '<a href="' . crm_url($path, $opts) . '">' . $text . '</a>';
}

/**
 * Die and print and print a debug backtrace.
 * @param $text
 */
function crm_error ($text) {
    print "<pre>$text\n";
    print_r(debug_backtrace());
    print "</pre>";
    die();
}

/**
 * Parse and return the version of the app as specified in crm.inc.php under $crm_version.
 * @return A string representation of the array
 */
function crm_version() {
    global $crm_version;
    $version = implode(".", $crm_version);
    return $version;
}

/**
 * @return The github username configured in config.inc.php to display in the footer.
 */
function github_username () {
    global $config_github_username;
    $variable_github_username = variable_get('github_username', $config_github_username);
    return $variable_github_username;
}

/**
 * @return The github username configured in config.inc.php to display in the footer.
 */
function github_repo () {
    global $config_github_repo;
    $variable_github_repo = variable_get('github_repo', $config_github_repo);
    return $variable_github_repo;
}

/**
 * @return The URL protocol configured in config.inc.php for use in outgoing emails.
 */
function protocol_security () {
    global $config_protocol_security;
    $variable_protocol_security = variable_get('protocol_security', $config_protocol_security);
    return $variable_protocol_security;
}

/**
 * @return The hostname configured in config.inc.php.
 */
function get_host () {
    global $config_host;
    $variable_host = variable_get('host', $config_host);
    return $variable_host;
}

/**
 * @return The org name configured in config.inc.php.
 */
function get_org_name () {
    global $config_org_name;
    $variable_org_name = variable_get('org_name', $config_org_name);
    return $variable_org_name;
}

/**
 * @return The org website configured in config.inc.php.
 */
function get_org_website () {
    global $config_org_website;
    $variable_org_website = variable_get('org_website', $config_org_website);
    return $variable_org_website;
}

/**
 * @return The currency code configured in config.inc.php.
 */
function get_currency_code () {
    global $config_currency_code;
    $variable_currency_code = variable_get('currency_code', $config_currency_code);
    return $variable_currency_code;
}

/**
 * @return The email from configured in config.inc.php.
 */
function get_email_from () {
    global $config_email_from;
    $variable_email_from = variable_get('email_from', $config_email_from);
    return $variable_email_from;
}

/**
 * @return The email to configured in config.inc.php.
 */
function get_email_to () {
    global $config_email_to;
    $variable_email_to = variable_get('email_to', $config_email_to);
    return $variable_email_to;
}

/**
 * @return The address1 configured in config.inc.php.
 */
function get_address1 () {
    global $config_address1;
    $variable_address1 = variable_get('address1', $config_address1);
    return $variable_address1;
}

/**
 * @return The address2 configured in config.inc.php.
 */
function get_address2 () {
    global $config_address2;
    $variable_address2 = variable_get('address2', $config_address2);
    return $variable_address2;
}

/**
 * @return The address3 configured in config.inc.php.
 */
function get_address3 () {
    global $config_address3;
    $variable_address3 = variable_get('address3', $config_address3);
    return $variable_address3;
}

/**
 * @return The town/city configured in config.inc.php.
 */
function get_town_city () {
    global $config_town_city;
    $variable_town_city = variable_get('town_city', $config_town_city);
    return $variable_town_city;
}

/**
 * @return The zipcode configured in config.inc.php.
 */
function get_zipcode () {
    global $config_zipcode;
    $variable_zipcode = variable_get('zipcode', $config_zipcode);
    return $variable_zipcode;
}

/**
 * @return The theme configured in config.inc.php.
 */
function get_theme () {
    global $config_theme;
    $variable_theme = variable_get('theme', $config_theme);
    return $variable_theme;
}
