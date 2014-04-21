<?php

/*
    Copyright 2009-2014 Edward L. Platt <ed@elplatt.com>
    Copyright 2013-2014 Chris Murray <chris.f.murray@hotmail.co.uk>

    This file is part of the Seltzer CRM Project
    register.inc.php - registration module

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
function register_revision () {
    return 1;
}

/**
 * Install or upgrade this module.
 * @param $old_revision The last installed revision of this module, or 0 if the
 *   module has never been installed.
 */
function register_install($old_revision = 0) {
    if ($old_revision < 1) {
        // There is nothing to install. Do nothing
    }
}

/**
 * @return The themed html string for a registration form.
*/
function theme_register_form () {
    return theme('form', crm_get_form('register'));
}

/**
 * @return The form structure for registering a member.
*/
function register_form () {
    
    // Start with contact form
    $form = crm_get_form('member_add');
    
    // Change form command
    $form['submit'] = 'Register';
    
    return $form;
}
