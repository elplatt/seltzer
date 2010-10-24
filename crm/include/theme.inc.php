<?php

/*
    Copyright 2009-2010 Edward L. Platt <elplatt@alum.mit.edu>
    
    This file is part of the Seltzer CRM Project
    theme.inc.php - Provides theming for core elements

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
 * Maps theme calls to appropriate theme handler
 *
 * At least one parmaeter is required, namely the element being themed.
 * Additional parameters will be passed on to the theme handler.
 *
 * @param $element The element to theme
*/
function theme() {
    
    // Check for arguments
    if (func_num_args() < 1) {
        return "";
    }
    $args = func_get_args();
    
    // Construct handler name
    $element = $args[0];
    $handler = 'theme_' . $element;
    
    // Construct handler arguments
    $handler_args = array();
    for ($i = 1; $i < count($args); $i++) {
        $handler_args[] = $args[$i];
    }
    
    // Check for undefined handler
    if (!function_exists($handler)) {
        return "";
    }
    
    return call_user_func_array($handler, $handler_args);
}

/**
 * Returns themed html for a page header
*/
function theme_header() {
    $output = '';
    $output .= theme_logo();
    $output .= theme_login_status();
    $output .= theme_navigation();
    return $output;
}

/* Returns themed html for a page footer
*/
function theme_footer() {
    return 'Powered by <a href="http://github.com/elplatt/seltzer">Seltzer CRM</a>';
}

/* Returns themed html for logo
*/
function theme_logo() {
    return '<div class="logo"><img alt="i3 Detroit" src="images/logo.png"/></div>';
}

/* Returns themed html for user login status
*/
function theme_login_status() {
    
    $output = '<div class="login-status">';
    if (user_id()) {
        $user = user_get_user();
        $output .= 'Welcome, ' . $user['username'] . '. <a href="action.php?command=logout">Log out</a>';
    } else {
        $output .= '<a href="login.php">Log in</a>';
    }
    $output .= '</div>';
    
    return $output;
}

/* Returns themed html for navigation
*/
function theme_navigation() {
    $output = '<ul class="nav">';
    foreach (sitemap() as $link) {
        $output .= '<li>' . theme_navigation_link($link) . '</li>';
    }
    $output .= '</ul>';
    
    return $output;
}

/* Returns themed html for single link
*/
function theme_navigation_link($link) {
    $output = '<a href="' . $link['url'] . '">' . $link['title'] . '</a>';
    return $output;
}

/**
 * Returns themed html for errors
*/
function theme_errors() {

    // Pop and check errors
    $errors = error_list();
    if (empty($errors)) {
        return '';
    }
    
    $output = '<fieldset><ul>';
    
    // Loop through errors
    foreach ($errors as $error) {
        $output .= '<li>' . $error . '</li>';
    }
    
    $output .= '</ul></fieldset>';
    return $output;
}

/* Returns themed html for a login form
*/
function theme_login_form() {
    return theme_form(login_form());
}

/* Returns themed html for a form
 *
 * @param $form The form structure
*/
function theme_form($form) {
    
    // Return empty string if there is no structure
    if (empty($form)) {
        return '';
    }
    
    // Initialize output
    $output = '';
    
    // Determine type of form structure
    switch ($form['type']) {
    case 'form':
        
        // Add form
        $output .= '<form method="' . $form['method'] . '" action="';
        if (!empty($form['action'])) {
            $output .= $form['action'] . '"';
        } else {
            $output .= 'action.php"';
        }
        $output .= '>';
        
        // Add hidden values
        if (!empty($form['command'])) {
            $output .= '<fieldset class="hidden"><input type="hidden" name="command" value="' . $form['command'] . '" /></fieldset>';
        }
        if (count($form['hidden']) > 0) {
            foreach ($form['hidden'] as $name => $value) {
                $output .= '<fieldset class="hidden"><input type="hidden" name="' . $name . '" value="' . $value . '"/></fieldset>';
            }
        }
        
        // Loop through each field and add output
        foreach ($form['fields'] as $field) {
            $output .= theme_form($field);
        }
        
        $output .= '</form>';
        
        break;
    case 'fieldset':
        
        $output .= '<fieldset>';
        
        // Add legend
        if (!empty($form['label'])) {
            $output .= '<legend>' . $form['label'] . '</legend>';
        }
        
        // Loop through each field and add output
        foreach ($form['fields'] as $field) {
            $output .= theme_form($field);
        }
        
        $output .= '</fieldset>';
        
        break;
    case 'message':
        $output .= theme_form_message($form);
        break;
    case 'readonly':
        $output .= theme_form_readonly($form);
        break;
    case 'text':
        $output .= theme_form_text($form);
        break;
    case 'checkbox':
        $output .= theme_form_checkbox($form);
        break;
    case 'select':
        $output .= theme_form_select($form);
        break;
    case 'password':
        $output .= theme_form_password($form);
        break;
    case 'submit':
        $output .= theme_form_submit($form);
        break;
    }
    
    return $output;
}

/* Return themed html for a message
 *
 * @param $field the message
 */
function theme_form_message($field) {
    $output = '<fieldset class="form-row">';
    $output .= $field['value'];
    $output .= '</fieldset>';
    return $output;
}

/* Return themed html for a read-only field
 *
 * @param $field the field
 */
function theme_form_readonly($field) {
    $output = '<fieldset class="form-row">';
    if (!empty($field['label'])) {
        $output .= '<label>' . $field['label'] . '</label>';
    }
    if (!empty($field['value'])) {
        $output .= '<span class="value">' . $field['value'] . '</span>';
    }
    $output .= '</fieldset>';
    return $output;
}

/* Return themed html for a text field
 *
 * @param $field the text field
 */
function theme_form_text($field) {
    $output = '<fieldset class="form-row">';
    if (!empty($field['label'])) {
        $output .= '<label>' . $field['label'] . '</label>';
    }
    $output .= '<input type="text" name="' . $field['name'] . '"';
    if (!empty($field['class'])) {
        $output .= ' class="' . $field['class'] . '"';
    }
    if (!empty($field['value'])) {
        $output .= ' value="' . $field['value'] . '"';
    }
    $output .= '/>';
    $output .= '</fieldset>';
    return $output;
}

/* Return themed html for a checkbox
 *
 * @param $field the checkbox
 */
function theme_form_checkbox($field) {
    $output = '<fieldset class="form-row">';
    $output .= '<input type="checkbox" name="' . $field['name'] . '" value="1"';
    if ($field['checked']) {
        $output .= ' checked="checked"';
    }
    $output .= '/>';
    if (!empty($field['label'])) {
        $output .= '<label>' . $field['label'] . '</label>';
    }
    $output .= '</fieldset>';
    return $output;
}

/* Return themed html for a password field
 *
 * @param $field the password field
 */
function theme_form_password($field) {
    $output = '<fieldset class="form-row">';
    if (!empty($field['label'])) {
        $output .= '<label>' . $field['label'] . '</label>';
    }
    $output .= '<input type="password" name="' . $field['name'] . '"/>';
    $output .= '</fieldset>';
    return $output;
}

/* Return themed html for a select field
 *
 * @param $field the select field
 */
function theme_form_select($field) {
    $output = '<fieldset class="form-row">';
    if (!empty($field['label'])) {
        $output .= '<label>' . $field['label'] . '</label>';
    }
    $output .= '<select name="' . $field['name'] . '">';
    
    foreach ($field['options'] as $key => $value) {
        $output .= '<option value="' . $key . '"';
        if ($field['selected'] == $key) {
            $output .= ' selected="selected"';
        }
        $output .= '>';
        $output .= $value;
        $output .= '</option>';
    }
    
    $output .= '</select>';
    $output .= '</fieldset>';
    return $output;
}

/* Return themed html for a submit button
 *
 * @param $field the submit button
 */
function theme_form_submit($field) {
    $output = '<fieldset class="form-row">';
    if (!empty($field['label'])) {
        $output .= '<label>' . $field['label'] . '</label>';
    }
    $output .= '<input type="submit"';
    if (!empty($field['name'])) {
        $output .= ' name="' . $field['name'] .'"';
    }
    if (!empty($field['value'])) {
        $output .= ' value="' . $field['value'] . '"';
    }
    $output .= '/>';
    $output .= '</fieldset>';
    return $output;
}

/**
 * Return themed html for a table
 *
 * @param $table The table data.
*/
function theme_table($table) {
    
    // Check if table is empty
    if (empty($table['rows'])) {
        return '';
    }
    
    // Open table
    $output = "<table";
    if (!empty($table['id'])) {
        $output .= ' id="' . $table['id'] . '"';
    }
    if (!empty($table['class'])) {
        $output .= ' class="' . $table['class'] . '"';
    }
    $output .= '>';
    
    $output .= "<thead><tr>";
    
    // Loop through headers
    foreach ($table['columns'] as $col) {
        
        // Open header cell
        $output .= '<th';
        if (!empty($col['id'])) {
            $output .= ' id="' . $col['id'] . '"';
        }
        if (!empty($col['class'])) {
            $output .= ' class="' . $col['class'] . '"';
        }
        $output .= '>';
        
        $output .= $col['title'];
        $output .= '</th>';
    }
    $output .= "</tr></thead>";
    
    // Output table body
    $output .= "<tbody>";
    
    // Initialize zebra striping
    $zebra = 1;
    
    // Loop through rows
    foreach ($table['rows'] as $row) {
        
        $output .= '<tr';
        if ($zebra % 2 === 0) {
            $output .= ' class="even"';
        } else {
            $output .= ' class="odd"';
        }
        $zebra++;
        $output .= '>';
        
        foreach ($row as $i => $cell) {
            $output .= '<td';
            if (!empty($table['columns'][$i]['id'])) {
                $output .= ' id="' . $col['id'] . '"';
            }
            if (!empty($table['columns'][$i]['id'])) {
                $output .= ' class="' . $col['class'] . '"';
            }
            $output .= '>';
            $output .= $cell;
            $output .= '</td>';
        }
        
        $output .= '</tr>';
    }
    
    $output .= "</tbody>";
    $output .= "</table>";
    
    return $output;
}

/**
 * Return themed html for a vertical table
 *
 * @param $table The table data.
*/
function theme_table_vertical($table) {
    
    // Check if table is empty
    if (empty($table['rows'])) {
        return '';
    }
    
    // Open table
    $output = "<table";
    if (!empty($table['id'])) {
        $output .= ' id="' . $table['id'] . '"';
    }
    if (!empty($table['class'])) {
        $output .= ' class="' . $table['class'] . '"';
    }
    $output .= '>';
    
    // Output table body
    $output .= "<tbody>";
    
    // Loop through headers
    foreach ($table['columns'] as $i => $col) {
        
        // Open row
        $output .= '<tr>';
        
        // Print header
        $output .= '<td';
        if (!empty($col['id'])) {
            $output .= ' id="' . $col['id'] . '"';
        }
        if (!empty($col['class'])) {
            $output .= ' class="' . $col['class'] . '"';
        }
        $output .= '>';
        
        $output .= $col['title'];
        $output .= '</td>';
        
        // Loop through rows
        foreach ($table['rows'] as $row) {
            
            $output .= '<td';
            if (!empty($table['columns'][$i]['id'])) {
                $output .= ' id="' . $col['id'] . '"';
            }
            if (!empty($table['columns'][$i]['id'])) {
                $output .= ' class="' . $col['class'] . '"';
            }
            $output .= '>';
            $output .= $row[$i];
            $output .= '</td>';
        }
        
        $output .= '</tr>';
    }
    
    $output .= "</tbody>";
    $output .= "</table>";
    
    return $output;
}

/**
 * Returned themed html for a delete confirmation form
 *
 * @param $type The type of element to delete
 * @param $id The id of the element to delete
*/
function theme_delete_form ($type, $id) {
    return theme_form(delete_form($type, $id));
}

/**
 * Return themed html for a page
 *
 * @param $page The page name
 * @param $options Additional options
 */
function theme_page($page, $options = array()) {
    
    // Create data structure
    $data = page($page, $options);
    
    // Initialize output
    $tabs = '';
    $header = '';
    
    // Add page title to header
    if (!empty($data['#title'])) {
        $header .= '<h1>' . $data['#title'] . '</h1>';
    }
    
    // Add button list to header
    $header .= '<ul class="page-nav">';
    
    // Loop through each tab
    foreach ($data as $tab => $tab_data) {
        
        // Skip special keys
        if ($tab{0} === '#') {
            continue;
        }
        
        // Generate tab name
        $tab_name = preg_replace('/\W+/', '-', strtolower($tab));
        
        $header .= '<li><a href="#tab-' . $tab_name . '">' . $tab . '</a></li>';
        
        $tabs .= '<fieldset id="tab-' . $tab_name . '" class="tab">';
        
        $tabs .= '<legend><a name="' . $tab_name . '">' . $tab . '</a></legend>';
        
        $tabs .= join($tab_data);
        
        $tabs .= '</fieldset>';
    }
    
    // Close header button list
    $header .= '</ul>';
    
    return $header . $tabs;
}

?>