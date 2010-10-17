<?php

/*
    Copyright 2009-2010 Edward L. Platt <elplatt@alum.mit.edu>
    
    This file is part of the Seltzer CRM Project
    form.inc.php - Core form system and core forms

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
 * Return login form structure
*/
function login_form () {
    $form = array(
        'type' => 'form',
        'method' => 'post',
        'command' => 'login',
        'fields' => array(
            array(
                'type' => 'fieldset',
                'label' => 'Log in',
                'fields' => array(
                    array(
                        'type' => 'text',
                        'label' => 'Username',
                        'name' => 'username'
                    ),
                    array(
                        'type' => 'password',
                        'label' => 'Password',
                        'name' => 'password'
                    ),
                    array(
                        'type' => 'submit',
                        'name' => 'submitted',
                        'value' => 'Log in'
                    )
                )
            )
        )
    );
    return $form;
}

/**
 * Return form structure for delete confirmation
 *
 * @param $type The type of element to delete
 * @param $id The id of the element to delete
*/
function delete_form ($type, $id) {
    $function = $type . '_delete_form';
    if (function_exists($function)) {
        return $function($id);
    }
    return array();
    /*
    switch ($type) {
    case 'member':
        return member_delete_form($id);
        break;
    case 'member_membership':
        return member_membership_delete_form($id);
        break;
    }
    return array();
    */
}

/**
 * Return form structure for a filtering form
 *
 * @param $filters Array of filter keys and labels
 * @param $default The default filter
 * @param $action URL to submit to
 * @param $get HTTP GET params to pass
*/
function filter_form($filters, $selected, $action, $get) {
    
    // Construct hidden fields to pass GET params
    $hidden = array();
    foreach ($get as $key => $val) {
        if ($key != 'filter') {
            $hidden[$key] = $val;
        }
    }
    
    $form = array(
        'type' => 'form',
        'method' => 'get',
        'action' => $action,
        'hidden' => $hidden,
        'fields' => array(
            array(
                'type' => 'fieldset',
                'label' => 'Filter',
                'fields' => array(
                    array(
                        'type' => 'select',
                        'name' => 'filter',
                        'options' => $filters,
                        'selected' => $selected
                    ),
                    array(
                        'type' => 'submit',
                        'value' => 'Filter'
                    )
                )
            )
        )
    );
    
    return $form;
}

?>