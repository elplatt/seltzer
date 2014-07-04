<?php

/*
    Copyright 2009-2014 Edward L. Platt <ed@elplatt.com>
    
    This file is part of the Seltzer CRM Project
    form.inc.php - Core form system.

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
 * Get a form, allowing modules to alter it.
 */
function crm_get_form () {
    if (func_num_args() < 1) {
        return array();
    }
    $args = func_get_args();
    $form_id = array_shift($args);
    $hook = "${form_id}_form";
    // Build initial form
    if (!function_exists($hook)) {
        error_register("No such hook: $hook");
        return array();
    }
    $form = call_user_func_array($hook, $args);
    if (empty($form)) {
        return $form;
    }
    // Allow modules to alter the form
    foreach (module_list() as $module) {
        $hook = $module . '_form_alter';
        if (function_exists($hook)) {
            $form = $hook($form, $form_id);
            if (empty($form)) {
                error_register('Empty form returned by ' . $hook);
            }
        }
    }
    return $form;
}

/**
 * Set form field values or use default.
 * @param $field A form field.
 * @param $values An associative array of form values.
 * @return The modified $field structure.
 */
function form_set_value ($field, $values) {
    // Set value if specified
    $name = $field['name'];
    if (isset($values[$name])) {
        $field['value'] = $values[$name];
    } else if (isset($field['value'])) {
        return $field;
    } else {
        $field['value'] = $field['default'];
    }
    return $field;
}

/**
 * @param $form The form structure.
 * @return The themed html string for a form.
*/
function theme_form ($form) {
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
            $output .= crm_url('').'"';
        }
        if (array_key_exists('enctype', $form)) {
            $output .= ' enctype="' . $form['enctype'] . '"';
        }
        $output .= '>';
        
        // Add hidden values
        if (!empty($form['command'])) {
            $output .= '<fieldset class="hidden"><input type="hidden" name="command" value="' . $form['command'] . '" /></fieldset>';
        }
        if (array_key_exists('hidden', $form) && count($form['hidden']) > 0) {
            foreach ($form['hidden'] as $name => $value) {
                $output .= '<fieldset class="hidden"><input type="hidden" name="' . $name . '" value="' . $value . '"/></fieldset>';
            }
        }
        
        // Loop through each field and add output
        foreach ($form['fields'] as $field) {
            if (array_key_exists('values', $form)) {
                $field = form_set_value($field, $form['values']);
                $field['values'] = $form['values'];
            }
            $output .= theme('form', $field);
        }
        
        // Add submit button
        if (isset($form['submit'])) {
            $submit_field = array(
                'type' => 'submit'
                , 'value' => $form['submit']
            );
            $output .= theme('form', $submit_field);
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
            if (array_key_exists('values', $form)) {
                $field = form_set_value($field, $form['values']);
                $field['values'] = $form['values'];
            }
            $output .= theme('form', $field);
        }
        
        $output .= '</fieldset>';
        
        break;
    case 'table':
        $output .= theme('form_table', $form);
        break;
    case 'message':
        $output .= theme('form_message', $form);
        break;
    case 'readonly':
        $output .= theme('form_readonly', $form);
        break;
    case 'text':
        $output .= theme('form_text', $form);
        break;
    case 'textarea':
        $output .= theme('form_textarea', $form);
        break;
    case 'checkbox':
        $output .= theme('form_checkbox', $form);
        break;
    case 'select':
        $output .= theme('form_select', $form);
        break;
    case 'password':
        $output .= theme('form_password', $form);
        break;
    case 'file':
        $output .= theme('form_file', $form);
        break;
    case 'submit':
        $output .= theme('form_submit', $form);
        break;
    }
    return $output;
}

/**
 * Theme a table in the form.
 * @param $field The form table data.
 * @return The themed html string for a form table element.
 */
function theme_form_table ($field) {
    // First create a table structure, and then theme it
    $table = array(
        'id' => ''
        , 'class' => ''
        , 'rows' => array()
    );
    $table['columns'] = $field['columns'];
    
    foreach ($field['rows'] as $formRow) {
        $row = array();
        foreach ($formRow as $formCell) {
            $row[] = theme('form', $formCell);
        }
        $table['rows'][] = $row;
    }
    
    return theme('table', $table);
}

/**
 * Themes a message in a form.
 *
 * @param $field the message.
 * @return The themed html string for a message form element.
 */
function theme_form_message($field) {
    if (!array_key_exists('class', $field)) { $field['class'] = ''; }
    $output = '<fieldset class="form-row ' . $field['class'] . '">';
    $output .= $field['value'];
    $output .= '</fieldset>';
    return $output;
}

/**
 * Themes a read-only field in a form.
 * 
 * @param $field The field.
 * @return The themed html for a read-only form field.
 */
function theme_form_readonly ($field) {
    if (!array_key_exists('class', $field)) { $field['class'] = ''; }
    $output = '<fieldset class="form-row ' . $field['class'] . '">';
    if (!empty($field['label'])) {
        $output .= '<label>' . $field['label'] . '</label>';
    }
    if (!empty($field['value'])) {
        $output .= '<span class="value">' . $field['value'] . '</span>';
    }
    if (!empty($field['name'])) {
        $output .= '<input type="hidden" name="' . $field['name'] . '" value="' . $field['value'] . '"/>';
    }
    $output .= '</fieldset>';
    return $output;
}

/**
 * Themes a text field in a form.
 * 
 * @param $field the text field.
 * @return The themed html for the text field.
 */
function theme_form_text ($field) {
    if (!array_key_exists('class', $field)) { $field['class'] = ''; }
    $classes = array();
    if (array_key_exists('class', $field) && !empty($field['class'])) {
        array_push($classes, $field['class']);
    }
    if (!empty($field['autocomplete'])) {
        array_push($classes, 'autocomplete');
    }
    if (!empty($field['suggestion'])) {
        array_push($classes, 'autocomplete');
    }
    if (!empty($field['value']) && array_key_exists('defaultClear', $field) && $field['defaultClear'] == True)
    {
        array_push($classes, 'defaultClear');
    }
    $output = '<fieldset class="form-row ' . $field['class'] . '">';
    if (!empty($field['label'])) {
        $output .= '<label>' . $field['label'] . '</label>';
    }
    if (empty($field['autocomplete'])) {
        $output .= '<input type="text" name="' . $field['name'] . '"';
    } else {
        $output .= '<input type="text" name="' . $field['name'] . '-label"';
    }
    if (!empty($classes)) {
        $output .= ' class="' . join(' ', $classes) . '"';
    }
    if (empty($field['autocomplete'])) {
        if (!empty($field['value'])) {
            $output .= ' value="' . $field['value'] . '"';
            
            if (array_key_exists('defaultClear', $field) && $field['defaultClear'] == True) {
                $output .= ' title="' . $field['value'] . '"';
            }
        }
    } else {
        if (!empty($field['description'])) {
            $output .= ' value="' . $field['description'] . '"';
        }
    }
    $output .= '/>';
    if(array_key_exists('suggestion', $field))
    {
        $output .= '<span class="autocomplete" style="display:none;">' . $field['suggestion'] . '</span>';
    }
    if (array_key_exists('autocomplete', $field) && !empty($field['autocomplete'])) {
        $val = array_key_exists('value', $field) ? $field['value'] : '';
        $output .= '<input class="autocomplete-value" type="hidden" name="' . $field['name'] . '"';
        $output .= ' value="' . $val . '"/>';
        $output .= '<span class="autocomplete" style="display:none;">' . $field['autocomplete'] . '</span>';
    }
    $output .= '</fieldset>';
    return $output;
}

/**
 * Themes a textarea in a form.
 * 
 * @param $field the textarea field.
 * @return The themed html for the textarea.
 */
function theme_form_textarea ($field) {
    $classes = array();
    if (!empty($field['class'])) {
        array_push($classes, $field['class']);
    }
    $output = '<fieldset class="form-row ' . $field['class'] . '">';
    if (!empty($field['label'])) {
        $output .= '<label>' . $field['label'] . '</label>';
    }
    $output .= '<textarea name="' . $field['name'] . '"';
    if (!empty($classes)) {
        $output .= ' class="' . join(' ', $classes) . '"';
    }
    $output .= '>';
    $output .= $field['value'];
    $output .= '</textarea>';
    $output .= '</fieldset>';
    return $output;
}

/**
 * Themes a checkbox in a form.
 *
 * @param $field the checkbox.
 * @return The themed html for the checkbox.
 */
function theme_form_checkbox ($field) {
    if (!array_key_exists('class', $field)) { $field['class'] = ''; }
    $output = '<fieldset class="form-row form-row-checkbox ' . $field['class'] . '">';
    $output .= '<input type="checkbox" name="' . $field['name'] . '" value="1"';
    if (array_key_exists('checked', $field) && $field['checked']) {
        $output .= ' checked="checked"';
    }
    $output .= '/>';
    if (!empty($field['label'])) {
        $output .= '<label>' . $field['label'] . '</label>';
    }
    $output .= '</fieldset>';
    return $output;
}

/**
 * Themes a password field in a form.
 * 
 * @param $field the password field.
 * @return The themed html for the password field.
 */
function theme_form_password ($field) {
    if (!array_key_exists('class', $field)) { $field['class'] = ''; }
    $output = '<fieldset class="form-row ' . $field['class'] . '">';
    if (!empty($field['label'])) {
        $output .= '<label>' . $field['label'] . '</label>';
    }
    $output .= '<input type="password" name="' . $field['name'] . '"/>';
    $output .= '</fieldset>';
    return $output;
}

/**
 * Themes a file field in a form.
 * 
 * @param $field the field data.
 * @return The themed html for the field.
 */
function theme_form_file ($field) {
    if (!array_key_exists('class', $field)) { $field['class'] = ''; }
    $output = '<fieldset class="form-row ' . $field['class'] . '">';
    if (!empty($field['label'])) {
        $output .= '<label>' . $field['label'] . '</label>';
    }
    $output .= '<input type="file" name="' . $field['name'] . '"/>';
    $output .= '</fieldset>';
    return $output;
}

/**
 * Themes a select field in a form.
 * 
 * @param $field the select field.
 * @return themed html for the select field.
 */
function theme_form_select ($field) {
    if (!array_key_exists('class', $field)) { $field['class'] = ''; }
    $output = '<fieldset class="form-row ' . $field['class'] . '">';
    if (!empty($field['label'])) {
        $output .= '<label>' . $field['label'] . '</label>';
    }
    $output .= '<select name="' . $field['name'] . '">';
    
    foreach ($field['options'] as $key => $value) {
        $output .= '<option value="' . $key . '"';
        if (array_key_exists('selected', $field) && $field['selected'] == $key) {
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

/**
 * Themes a submit button in a form.
 *
 * @param $field The submit button.
 * @return The themed html for the submit button.
 */
function theme_form_submit ($field) {
    if (!array_key_exists('class', $field)) { $field['class'] = ''; }
    $output = '<fieldset class="form-row ' . $field['class'] . '">';
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
