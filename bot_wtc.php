<?php

if (@txpinterface == 'admin') {

    global $event;

    add_privs('bot_wtc_tab', '1,2');
    register_tab('extensions', 'bot_wtc_tab', 'Write tab customize');
    register_callback('bot_wtc_tab', 'bot_wtc_tab');

    if ($event === 'article') {
        register_callback('bot_wtc', 'admin_side', 'head_end');
        register_callback('bot_hide_per_section', 'admin_side', 'head_end');
        register_callback('bot_hidden_sections', 'admin_side', 'head_end');
    }

    if ($event === 'bot_wtc_tab') {
        register_callback('bot_wtc_css', 'admin_side', 'head_end');
    }

    register_callback('bot_wtc_welcome', 'plugin_lifecycle.bot_write_tab_customize');

}

// ===========================================
// Array of parts of Write Tab UI items
// Key: gTxt name => Value: Jquery selector query

$bot_arr_selectors = array(

    'writetab_main_content' => '$("#main_content")',

        'writetab_view_modes' => '$("#view_modes")',
        'title' => '$(".title")',
        'author' => '$(".author")',
        'body' => '$(".body")',
        'excerpt' => '$(".excerpt")',
        'article_markup' => '$(".body .txp-textfilter-options")',
        'excerpt_markup' => '$(".excerpt .txp-textfilter-options")',
        'textile_help' => '$(".textfilter-help")',

    'writetab_sidebar' => '$("#supporting_content")',

        'save' => '$(".txp-save")',

        'actions' => '$("#txp-article-actions")',

            'create_new' => '$(".txp-new")',
            'duplicate' => '$(".txp-clone")',
            'view' => '$(".txp-article-view")',

        'page_article_nav_hed' => '$(".nav-tertiary")',

        'sort_display' => '$("#txp-write-sort-group")',

            'status' => '$(".status")',
            'section' => '$(".section")',
            'override_default_form' => '$(".override-form")',

        'date_settings' => '$("#txp-dates-group")',

            'publish' => '$("#publish-datetime-group")',
            'publish_date' => '$(".posted.date")',
            'publish_time' => '$(".posted.time")',
            'reset_time' => '$(".reset-time")',

            'expired' => '$("#expires-datetime-group")',
            'expire_date' => '$(".expires.date")',
            'expire_time' => '$(".expires.time")',
            'expire_now' => '$(".expire-now")',

        'categories' =>  '$("#txp-categories-group")',

            'category1' => '$(".category-1")',
            'category2' => '$(".category-2")',

        'meta' => '$("#txp-meta-group")',

            'url_title' => '$(".url-title")',
            'description' => '$(".description")',
            'keywords' => '$(".keywords")',

        'comment_settings' => '$("#txp-comments-group")',

            'use_comments' => '$(".comments-annotate")',
            'comment_invitation' => '$(".comment-invite")',

        'article_image_group' => '$("#txp-image-group")',

            'article_image' => '$(".article-image")',

        'custom' => '$("#txp-custom-field-group")',

        'recent_articles' => '$("#txp-recent-group")'

);

    // Get language strings not in plugin for write tab item dropdowns
    $ui_language = get_pref('language_ui', TEXTPATTERN_DEFAULT_LANG);
    $langObject = \Txp::get('\Textpattern\L10n\Lang'); // Fetch ref to language class if not already done
    // Extract strings from the given groups in the current UI language.
    $strings = $langObject->extract($ui_language, 'article, tag, prefs, public');
/*
    // Get just the relevant subset of strings that corresponds to dropdown content
    $strings = array_intersect_key($strings, array_flip(array_keys($bot_arr_selectors)));
*/
    // Set the internal strings to use them (true = append to what's already loaded)
    $langObject->setPack($strings, true);

    // Creates the translated main plugins array ($bot_items)
    global $bot_items;
    foreach ($bot_arr_selectors as $title => $selector) {
        bot_wtc_insert_in_main_array($title, $selector);
    }
    natcasesort($bot_items);



// ===========================================================
// Helper functions
// ===========================================================

// Helps build the main array

function bot_wtc_insert_in_main_array($title, $selector)
{
    global $bot_items;

    if (strpos($title, '!bot!')) {
        $split_titles = explode("!bot!", $title);
        $title = '';
        for ($i = 0; $i < count($split_titles); $i++) {
            $title .= gTxt($split_titles[$i]); // split and build translated title
        }
    } else {
        $title = gTxt($title); // Gets the title to allow translation
    }
    $bot_items [$selector] = gTxt($title);
    return $bot_items;
}


// ===========================================
// Creates an array of values extracted from the database

function bot_wtc_fetch_db()
{
    if (bot_wtc_check_install()) {
        $out = safe_rows('id, item, position, destination, sections, class', 'bot_wtc ', '1=1');
        return $out;
    }
}


// ===========================================================
// Creates an array of all cfs for selectInput

function bot_get_cfs()
{
    $r = safe_rows_start('name, val, html', 'txp_prefs', 'event = "custom" AND val != ""');
    if ($r) {
        global $arr_custom_fields;
        while ($a = nextRow($r)) {
//            $name = str_replace('_set', '', $a['name']);
            $name = str_replace('_', '-', substr($a['name'], 0, -4));
            $html = $a['html'];
            $selector = '$(".'.$name.'")';
/*
            if ($html == 'checkbox' || $html == 'multi-select') {
                $selector = '$("p:has(*[name=\''.$name.'[]\'])")';
            } else {
                $selector = '$("p:has(*[name=\''.$name.'\'])")';
            }
*/
            // If glz_cf_gtxt function exists, use custom_field title if one exists
            $cf_title = (function_exists('glz_cf_gtxt')) ? glz_cf_gtxt($a['val']) : '';
            $val = (!empty($cf_title)) ? $cf_title : $a['val'];
            $arr_custom_fields[$selector] = $val;
        }
    }
    if ($arr_custom_fields) {
        natcasesort($arr_custom_fields); // Sort cfs - used instead of asort because is case-insensitive
        return $arr_custom_fields;
    }
};


// ===========================================================
// Creates an array of all sections for selectInput

function bot_get_sections()
{
    $r = safe_rows_start('name, title', 'txp_section', '1=1');
    if ($r) {
        while ($a = nextRow($r)) {
            $name = $a['name'];
            $title = $a['title'];
            $sections[$name] = $title;
        }
    }
    natcasesort($sections);
    return $sections;
}


// ===========================================================
// Update button

function bot_update_button()
{
    return n.'<div class="bot_update_button">'
        .n.eInput('bot_wtc_tab')
        .n.sInput('update')
        .n.fInput('submit', 'update', gTxt('bot_wtc_update_button'), 'publish')
        .'</div>';
}



// ===========================================================
// Checks if item is a layout region

function bot_wtc_is_region($item)
{
    $item = get_magic_quotes_gpc() ? $item : doSlash($item) ;

    if ($item == '$(\"#main_content\")'
     || $item == '$(\"#supporting_content\")'
     || $item == '$(\"#view_modes\")'
    ) {
        return 1;
    }
    return 0;
}



// ===========================================================


//===========================================
// Kickstart the plugin after installation / activation / deletion

function bot_wtc_welcome($evt, $stp)
{
    switch ($stp) {
        case 'installed':
            bot_wtc_install();
            break;
        case 'enabled':
            bot_wtc_update();
            break;
        case 'deleted':
            bot_wtc_deinstall();
            break;
    }

    return;
}


//===========================================
// Set up bot_wtc tables

function bot_wtc_install()
{
    // Create the bot_wtc table
    safe_create('bot_wtc', '
        id            INT          NOT NULL AUTO_INCREMENT,
        item          VARCHAR(255) NOT NULL,
        position      VARCHAR(255) NOT NULL,
        destination   VARCHAR(255) NOT NULL,
        sections      TEXT         NOT NULL,
        class         VARCHAR(255) NOT NULL,
        PRIMARY KEY (id)
    ');

    // Set pref entries in txp_prefs table
    set_pref('bot_wtc_script', '', 'bot_wtc_', '2');
    set_pref('bot_wtc_static_sections', '', 'bot_wtc_', '2');
}


//===========================================
// Remove bot_wtc tables

function bot_wtc_deinstall()
{
    // Drop the bot_wtc table
    safe_drop("bot_wtc");
    // Remove bot_wtc prefs
    safe_delete('txp_prefs', 'event = "bot_wtc_"');
}


// ===========================================================
// Updates cfs selectors in db

function bot_wtc_update()
{
    // proceeds only if plugin is already installed
    if (!bot_wtc_check_install()) {
        return;
    }
    safe_alter('bot_wtc', 'CHANGE sections sections TEXT', 0);
    $db_values = bot_wtc_fetch_db(); // array
    for ($i =0; $i < count($db_values); $i++) {
        $id = $db_values[$i]['id'];
        $item = $db_values[$i]['item'];
        $destination = $db_values[$i]['destination'];
        // Updates cfs
        // If item contains the substring 'custom'
        if (strpos($item, 'custom')) {
            // Get cf number from selector string (i.e. just the numbers)
            $cf_number = preg_replace("/[^0-9]/", '', $item);
            // New selector
            $selector = '$(".custom-'.$cf_number.'")';
            safe_update('bot_wtc', 'item = "'.doslash($selector).'"', 'id = "'.$id.'"');
        }
        // If destination contains the substring 'custom'
        if (strpos($destination, 'custom')) {
            // Get cf number from selector string (i.e. just the numbers)
            $cf_number = preg_replace("/[^0-9]/", '', $destination);
            // New selector
            $selector = '$(".custom-'.$cf_number.'")';
            safe_update('bot_wtc', 'destination = "'.doslash($selector).'"', 'id = "'.$id.'"');
        }
    }
}


// ===========================================================
// Check if the bot_wtc table exists

function bot_wtc_check_install()
{
    // If number of rows is false, table does not exist
    if (safe_count("bot_wtc", "1 = 1") !== false) {
        return true;
    }
    return false;
}


//===========================================
// Outputs all items for selectInput() (used for destination dropdown)

function bot_all_items_selectinput()
{
    global $bot_items;

    $cfs = bot_get_cfs(); // Get cfs array in the form: cf_selector => cf_name
    // Final values for the txp function selectInput (including cfs if any)
    if (is_array($cfs)) { // If there is at least one custom field set adds cfs to $bot_items array
        $all_items_select = array_merge($cfs, $bot_items);
    } else {
        $all_items_select = $bot_items;
    }
    return $all_items_select;
    natcasesort($all_items_select);
}


//===========================================

// outputs only 'not-yet-used' items for selectInput() (used for items dropdown)

function bot_contextual_selectinput($current = "")
{
    global $bot_items;

    $db_values = bot_wtc_fetch_db(); // Array of values from the db
    $all_items = bot_all_items_selectinput();
    if (bot_wtc_check_install()) {
        $used_items = safe_column('item', 'bot_wtc', '1=1'); // Numeric array of item values from the db
        foreach ($all_items as $item => $title) {
            if (!in_array($item, $used_items)) {
                $items_selectInput[$item] = $title;
            }
        }
    } else {
        $items_selectInput = $all_items;
    }
    if ($current) { // if the parameter is given adds current value to array
        $items_selectInput[$current] = $all_items[$current];
    }
    return  $items_selectInput;
}



// ===========================================================
// bot_wtc tab
// ===========================================================

// Outputs the rows for the html table in the bot_wtc_tab

function bot_wtc_output_rows()
{
    global $bot_items;

    $selectInput_for_position = array('insertBefore'=>gTxt('bot_wtc_before'),'insertAfter'=>gTxt('bot_wtc_after')); // position values for the txp function selectInput
    $db_values = bot_wtc_fetch_db(); // array of values from the db

    $destination_selectInput = bot_all_items_selectinput();
    $items_selectInput = bot_contextual_selectinput();

    // Builds rows for new item sections list
    $sections= bot_get_sections(); // get sections array
    $new_item_sections_rows = '';
    foreach ($sections as $key => $value) {
        $new_item_sections_row = '<div class="txp-form-checkbox">'.
            n.checkbox('new_item_sections[]', $key, '0', 0, $key).
            n.tag($value, 'label', array('for' => $key)).
            n.'</div>';
//        $new_item_sections_row = '<label>'.checkbox('new_item_sections[]', $key, '0').$value.'</label><br />';
        $new_item_sections_rows .= $new_item_sections_row;
    }
    $new_item_sections_rows .= '<p ><a href="#" class="bot_all">'.gTxt("all").'</a> | <a href="#" class="bot_none">'.gTxt("none").'</a></p>'; // hide all/none

    // New item insertion
    $rows = "";
    $input_row = tr(
        td(selectInput('new_item', bot_contextual_selectinput(), '', '1'), '', '').
        td(selectInput('new_item_position', $selectInput_for_position, '', '1')).
        td(selectInput('new_item_destination', bot_all_items_selectinput(), '', '1')).
        td('<p><a href="#" class="bot_push">'.gTxt('bot_wtc_section_list').'<span class="ui-icon ui-icon-caret-1-s"></span></a></p><div class="bot_collapse">'.$new_item_sections_rows.'</div>').
        td(finput('text', 'new_item_class', '')).
        td()
        );
    $rows .= $input_row;

    // Other rows - output if at least one record was already set
    if ($db_values) {
        for ($i = 0; $i < count($db_values); $i++) {
            // data for "sections to show" selectinput - decides wether a section is checked or not
            $bot_hide_in_this_sections_array = explode('|', $db_values[$i]['sections']);
            $item_sections_rows = '';
            foreach ($sections as $key => $value) { // if section is in db mark as checked
                $checked = in_array($key, $bot_hide_in_this_sections_array) ? '1': '0';
                $item_sections_row = '<div class="txp-form-checkbox">'.
                    n.checkbox('bot_wtc_sections_for_id_'.$db_values[$i]['id'].'[]', $key, $checked, 0, $key).
                    n.tag($value, 'label', array('for' => $key)).
                    n.'</div>';
                $item_sections_rows .= $item_sections_row;
            }
            $item_sections_rows .= '<p><a href="#" class="bot_all">'.gTxt("all").'</a> | <a href="#" class="bot_none">'.gTxt("none").'</a></p>'; // hide all/none
            $single_row = tr(
            td(selectInput('item[]', bot_contextual_selectinput($db_values[$i]['item']), $db_values[$i]['item'], '0'), '', '')
            .td(selectInput('item_position[]', $selectInput_for_position, $db_values[$i]['position'], '1'))
            .td(selectInput('item_destination[]', bot_all_items_selectinput(), $db_values[$i]['destination'], '1'))
            .td('<p><a href="#" class="bot_push">'.gTxt('bot_wtc_section_list').'<span class="ui-icon ui-icon-caret-1-s"></span></a></p><div class="bot_collapse">'.$item_sections_rows.'</div>')
            .td(finput('text', 'item_class[]', $db_values[$i]['class']))
            .td(tag(
                checkbox('bot_delete_id[]', $db_values[$i]['id'], '0', 0, 'bot_delete_id_'.$db_values[$i]['id']).'<label for="bot_delete_id_'.$db_values[$i]['id'].'"> '.gTxt('delete').'</label>',
                'div',
                array('class' => 'txp-form-checkbox')
                )
                .hInput('bot_wtc_id[]', $db_values[$i]['id'])
                )
            );

            $rows .= $single_row;
        }
    };
    return $rows;
}



//===========================================
// Builds rows for sections list

function bot_wtc_static_sections_select()
{
    // get sections array
    $sections= bot_get_sections();
    $static_sections = safe_field('val', 'txp_prefs', 'name = "bot_wtc_static_sections"'); //  fetch prefs value for bot_wtc_static_sections
    $static_sections = explode('|', $static_sections); // creates an array of statica sections from the string in txp_prefs
    $static_sections_rows = '';
    foreach ($sections as $key => $value) {
        // if section is in db mark as checked
        $checked = in_array($key, $static_sections) ? '1': '0';
        $static_sections_row = '<div class="txp-form-checkbox">'.
            n.checkbox('static_sections[]', $key, $checked, 0, $key).
            n.tag($value, 'label', array('for' => $key)).
            n.'</div>';
        $static_sections_rows .= $static_sections_row;
    }
    return $static_sections_rows;
}



//===========================================
// Advanced multiple selection on bot_wtc tab

function bot_advanced()
{
    global $bot_items;
    $items = bot_all_items_selectinput(); // get items array
    $item_rows = '';
    foreach ($items as $key => $value) {
        $item_row = '<div class="txp-form-checkbox">'.
            n.checkbox('bot_adv_items[]', $key, '0', 0, $value).
            n.tag($value, 'label', array('for' => $value)).
            n.'</div>';
        $item_rows .= $item_row;
        $sections= bot_get_sections(); // get sections array
    }
    $sections_rows = '';
    foreach ($sections as $key => $value) {
        $sections_row = '<div class="txp-form-checkbox">'.
            n.checkbox('bot_adv_sections[]', $key, '0', 0, $key).
            n.tag($value, 'label', array('for' => $key)).
            n.'</div>';
        $sections_rows .= $sections_row;
    }

    return n.wrapRegion('bot_advanced',
            form(
                bot_update_button().
                n.'<div class="txp-grid">'.
                n.'<div id="bot_adv_items" class="txp-grid-cell"><h4>'.gTxt('bot_wtc_items').'</h4>'.$item_rows.'</div>'.
                  '<div id="bot_adv_hide"  class="txp-grid-cell"><h4>'.gTxt('bot_wtc_hide_in_section').'</h4>'.$sections_rows.'<p><a href="#" class="bot_all">'.gTxt("all").'</a> | <a href="#" class="bot_none">'.gTxt("none").'</a></p></div>'.
                  '<div id="bot_adv_class" class="txp-grid-cell"><h4>'.gTxt('bot_wtc_set_css_class').'</h4>'.finput('text', 'bot_adv_class', '').'</div>'.
                n.'</div>'
            ),
        'bot_advanced-details',
        'bot_wtc_advanced_multiple_group',
        'bot_advanced_multiple',
        '',
        '',
        1
    );

}

//===========================================



function bot_wtc_tab($event, $step, $msg='')
{
    global $bot_items;
    $cfs = bot_get_cfs();

    if ($step == 'update') {
        // Set function variables
        $new_item = ps('new_item'); //variable
        $new_item_position = ps('new_item_position'); //variable
        $new_item_destination = ps('new_item_destination'); //variable
        $new_item_sections = ps('new_item_sections'); //array
        $new_item_class = ps('new_item_class'); //variable
        $bot_wtc_script = ps('bot_wtc_script'); //variable
        $static_sections = ps('static_sections'); //variable
        $item = ps('item'); //array
        $item_position = ps('item_position'); //array
        $item_destination = ps('item_destination'); //array
        $item_class = ps('item_class'); //array
        $bot_wtc_id = ps('bot_wtc_id'); //array
        $delete_id = ps('bot_delete_id'); //array
        $bot_adv_items = ps('bot_adv_items'); //array
        $bot_adv_sections = ps('bot_adv_sections'); //array
        $bot_adv_class = ps('bot_adv_class'); //variable

        // db update for existing items
        if ($item) { // if at least a saved item exists

               $db_values = bot_wtc_fetch_db(); // array of values from the db
            for ($i = 0; $i < count($item); $i++) {
                // builds the posted variable name for current item sections
                $item_posted_sections_name = 'bot_wtc_sections_for_id_'.$db_values[$i]['id'];
                $item_sections = isset($_POST[$item_posted_sections_name]) ? $_POST[$item_posted_sections_name] : ''; //array
                // builds sections string for current item
                $item_sections_string = $item_sections ? implode('|', $item_sections): '';
                // allowed input data combinations
                if (($item[$i] && $item_destination[$i] && $item_position[$i])
                || ($item[$i] && $item_class[$i] && !$item_destination[$i] && !$item_position[$i])
                || ($item[$i] && $item_sections_string && !$item_destination[$i] && !$item_position[$i])) {
                    // check if a column/region is linked with a non-column item BUT ONLY IF both items are set (otherwise couldn't apply i.e. class to a single region)
                    if (!((bot_wtc_is_region($item[$i]) xor bot_wtc_is_region($item_destination[$i])) && $item_destination[$i])) {
                        // check if item is different from destination
                        if ($item[$i] != $item_destination[$i]) {
                            safe_update(
                                "bot_wtc",
                                "position = '".doslash($item_position[$i])."',
                                 destination = '".doslash($item_destination[$i])."',
                                 item = '".doslash($item[$i])."',
                                 sections = '".doslash($item_sections_string)."',
                                 class = '".doslash($item_class[$i])."'",
                                "id = '".$bot_wtc_id[$i]."'"
                            );
                        } else {
                            $msg = array(gTxt('bot_wtc_same_item_warning'), E_ERROR);
                        }
                    } else {
                        $msg = array(gTxt('bot_wtc_region_warning'), E_ERROR);
                    }
                } else {
                    $msg = array(gTxt('bot_wtc_combo_warning'), E_ERROR);
                }
            }
        }

        // db insert for new item
        // allowed input combinations
        if (($new_item && $new_item_destination && $new_item_position)
        || ($new_item && $new_item_class && !$new_item_destination && !$new_item_position)
        || ($new_item && $new_item_sections && !$new_item_destination && !$new_item_position)) {
            // check if a column is linked with a non-column item
            if (!((bot_wtc_is_region($new_item) xor bot_wtc_is_region($new_item_destination)) &&  $new_item_destination)) {
                // check items are not the same
                if ($new_item != $new_item_destination) {
                    // transforms the sections array in a string
                    $new_item_sections_string = $new_item_sections ? implode('|', $new_item_sections) : '';
                    safe_insert(
                        "bot_wtc",
                    "position = '"
                    .doslash($new_item_position)
                    ."', destination = '"
                    .doslash($new_item_destination)
                    ."', class = '"
                    .doslash($new_item_class)
                    ."', sections = '"
                    .doslash($new_item_sections_string)
                    ."', item = '"
                    .doslash($new_item)
                    ."'"
                    );
                } else {
                    $msg = array(gTxt('bot_wtc_same_item_warning'), E_ERROR);
                }
            } else {
                $msg = array(gTxt('bot_wtc_td_warning'), E_ERROR);
            }
        } elseif ($new_item || $new_item_destination || $new_item_position || $new_item_class || $new_item_sections) {
            $msg = array(gTxt('bot_wtc_combo_warning'), E_ERROR);
        }

        if ($delete_id) { // checks if there is something to delete
            foreach ($delete_id as $id) {
                safe_delete('bot_wtc', 'id ="'.$id.'"');
            }
        }

        // Update advanced preferences
        if ($bot_adv_items and ($bot_adv_sections || $bot_adv_class)) { // check if item AND section OR class is selected

            $db_values = bot_wtc_fetch_db(); // first array: all values from db

            if ($bot_adv_sections) {
                $bot_db_sections = array(); // more specific array: only item => sections
                for ($i =0; $i < count($db_values); $i++) {
                    $bot_db_sections[$db_values[$i]['item']] = $db_values[$i]['sections'];
                }

                foreach ($bot_adv_items as $item) { // iterates posted items
                    // Fetch any existing sections from db for current item and merges arrays eliminating duplicates
                    if (is_array($bot_db_sections) and array_key_exists($item, $bot_db_sections)) {
                        $db_sect_array = explode('|', $bot_db_sections[$item]);
                        $final_array = array_unique(array_merge($db_sect_array, $bot_adv_sections));
                        $bot_adv_sections_string = implode('|', $final_array); // new sections string
                    } else {
                        $bot_adv_sections_string = implode('|', $bot_adv_sections);
                    }
                    safe_upsert(
                        "bot_wtc",
                        "sections = '"
                        .doslash($bot_adv_sections_string)
                        ."'",
                        "item = '".doslash($item)."'"
                    );
                }
            }

            if ($bot_adv_class) {
                $bot_db_classes = array(); // more specific array: only item => classes
                for ($i =0; $i < count($db_values); $i++) {
                    $bot_db_classes[$db_values[$i]['item']] = $db_values[$i]['class'];
                }

                foreach ($bot_adv_items as $item) { // Iterates posted items
                    // Fetch any existing class from db for current item and merges arrays eliminating duplicates
                    if (is_array($bot_db_classes) and array_key_exists($item, $bot_db_classes)) {
                        $db_class_array = explode(' ', $bot_db_classes[$item]);
                        $posted_class_array = explode(' ', $bot_adv_class);
                        $final_array = array_unique(array_merge($db_class_array, $posted_class_array));
                        $bot_adv_classes_string = implode(' ', $final_array); // new sections string
                    } else {
                        $bot_adv_classes_string = $bot_adv_class;
                    }
                    safe_upsert(
                        "bot_wtc",
                        "class = '"
                        .doslash($bot_adv_classes_string)
                        ."'",
                        "item = '".doslash($item)."'"
                    );
                }
            }
        } elseif ($bot_adv_sections || $bot_adv_class) {
            $msg = array(gTxt('bot_wtc_no_items_warning'), E_ERROR);
        }

        // Updates static sections prefs
        if ($static_sections) {
            $static_sections_string = implode('|', $static_sections);
            safe_update('txp_prefs', 'val= "'.doslash($static_sections_string).'", html="text_input" ', 'name = "bot_wtc_static_sections"');
        }

        // Updates script prefs
        if ($bot_wtc_script) {
            safe_update('txp_prefs', 'val= \''.doslash($bot_wtc_script).'\', html=\'textarea\' ', 'name = \'bot_wtc_script\'');
        }

        // No error message? Then announce success
        if (empty($msg)) {
            $msg = gTxt('preferences_saved');
        }
    }

    // Top of page
    pagetop(gTxt('bot_wtc_tab_name'), $msg);

    // Page heading and 'toggle advanced'
    echo tag_start('div', array('class' => 'txp-layout')).
            tag_start('div', array('class' => 'txp-layout-2col')).
                hed(gTxt('bot_wtc_tab_name'), 1, array('class' => 'txp-heading')).
            tag_end('div').
            tag_start('div', array('class' => 'txp-layout-2col')).
                href('<span class="ui-icon ui-icon-gear"></span>'.gTxt('bot_wtc_advanced'), '#', array('id' => 'bot_advanced_open', 'class' => 'txp-list-options')).
            tag_end('div').
            tag_start('div', array('class' => 'txp-layout-1col', 'id' => $event.'_container'));

    // What to show when accessing tab
    if (bot_wtc_check_install()) {

        // Fetch prefs value for bot_wtc_script
        $bot_wtc_script = safe_field('val', 'txp_prefs', 'name = "bot_wtc_script"');

        // Advanced / multiple panel
        echo n.t.bot_advanced();

        // Main div
        echo n.t.'<div id="bot_main" class="txp-layout-1col">';

        // 'Expand all' and 'Collapse all' controls
        echo graf(
            href('<span class="ui-icon ui-icon-arrowthickstop-1-s"></span> '.gTxt('expand_all'), '#', array(
                'id'            => 'bot_expand_all',
                'class'         => 'txp-expand-all',
                'aria-controls' => 'bot_main',
            )).
            href('<span class="ui-icon ui-icon-arrowthickstop-1-n"></span> '.gTxt('collapse_all'), '#', array(
                'id'            => 'bot_collapse_all',
                'class'         => 'txp-collapse-all',
                'aria-controls' => 'bot_main',
            )), array('id' => 'bot_controls', 'class' => 'txp-actions txp-list-options')
        );

        // Table
        echo form( // begin form

            tag_start('table', array('id' => 'bot_wtc_table', 'class' => 'txp-list--no-options')).

            n.tag_start('thead').
                tr(
                    hCell(gTxt('bot_wtc_item'), '', array('class' => 'txp-list-col-item', 'scope' => 'col')).
                    hCell(gTxt('bot_wtc_position'), '', array('class' => 'txp-list-col-position', 'scope' => 'col')).
                    hCell(gTxt('bot_wtc_destination'), '', array('class' => 'txp-list-col-destination', 'scope' => 'col')).
                    hCell(gTxt('bot_wtc_hide'), '', array('class' => 'txp-list-col-hide', 'scope' => 'col')).
                    hCell(gTxt('bot_wtc_class'), '', array('class' => 'txp-list-col-class', 'scope' => 'col')).
                    hcell()
                ).
            n.tag_end('thead').
                bot_wtc_output_rows(). // HTML rows generated by "bot_wtc_output_rows()"
            n.tag_end('table').

            bot_update_button().

            n.wrapRegion('bot_static_sections',
                    bot_wtc_static_sections_select().
                    bot_update_button(),
                'bot_static_sections-details',
                'bot_wtc_static_sections_group',
                'bot_static_sections',
                'bot_collapse').

            n.wrapRegion('bot_js_box',
                    '<div class="txp-actions">'.
                    n.'<a id="bot_js_link" href="#"><span class="ui-icon ui-icon-circlesmall-plus"></span> '.gTxt('bot_wtc_add_external_script').'</a>'.
                    n.'<a id="bot_jq_link" href="#"><span class="ui-icon ui-icon-circlesmall-plus"></span> '.gTxt('bot_wtc_add_inline_code').'</a>'.
                    n.'</div>'.
                    n.'<textarea id="bot_wtc_script" name="bot_wtc_script" cols="60" rows="10">'.$bot_wtc_script.'</textarea>'. // script textarea
                    n.bot_update_button(),
                'bot_js_box-details',
                'bot_wtc_add_javascript_group',
                'bot_add_javascript',
                'bot_collapse').

            n.tag_end('div')

        );  // end form
    }

    // Snippets to insert in the script box
    $bot_jquery_snippet = '<script>\n    $(document).ready(function() {\n        // your code here\n    });\n<\/script>\n';
    $bot_js_snippet = '<script src=\"path_to_script\"><\/script>\n';

    // Add jquery action
    echo
    '<script>'.n.
    '    $(document).ready(function() {'.n.
            '$("div.bot_collapse").hide()'.n.
            '$("section#bot_advanced").hide()'.n.
            '$("a.bot_push").click(function(){'.n.
            '  $(this).toggleClass("bot_arrow").parent().next().slideToggle("fast");'.n.
            '  $(this).children(".ui-icon").toggleClass("ui-icon-caret-1-s").toggleClass("ui-icon-caret-1-n");'.n.
            '  return false;'.n.
            '});'.n.
            '$("#bot_collapse_all").click(function(){'.n.
            '  $("div.bot_collapse").slideUp("fast").parent().find(".ui-icon").removeClass("ui-icon-caret-1-n").addClass("ui-icon-caret-1-s");'.n.
            '  return false;'.n.
             '});'.n.
            '$("#bot_expand_all").click(function(){'.n.
            '  $("div.bot_collapse").slideDown("fast").parent().find(".ui-icon").removeClass("ui-icon-caret-1-s").addClass("ui-icon-caret-1-n");'.n.
            '  return false;'.n.
             '});'.n.
            '$("#bot_advanced_open").click(function(){'.n.
            '  $("section#bot_advanced").slideToggle("fast");'.n.
            '  $("div#bot_main").toggle("fast");'.n.
            '  return false;'.n.
             '});'.n.
            '$("a.bot_all").click(function(){'.n.
            '  $(this).parent().parent().find("input").attr("checked", true);'.n.
            '  return false;'.n.
            '});'.n.
            '$("a.bot_none").click(function(){'.n.
            '  $(this).parent().parent().find("input").attr("checked", false);'.n.
            '  return false;'.n.
            '});'.n.
            '$("#bot_jq_link").click(function(){'.n.
            '  var areaValue = $("#bot_wtc_script").val();'.n.
            '  $("#bot_wtc_script").val(areaValue + "'.$bot_jquery_snippet.'");'.n.
            '  return(false);'.n.
            '});'.n.
            '$("#bot_js_link").click(function(){'.n.
            '  var areaValue = $("#bot_wtc_script").val();'.n.
            '  $("#bot_wtc_script").val(areaValue + "'.$bot_js_snippet.'");'.n.
            '  return(false);'.n.
            '});'.n.
    '    });'.n.
    '</script>';
}



// ===========================================================
// plugins output
// ===========================================================

// CSS for the plugin tab under extensions

function bot_wtc_css()
{
    global $event;
    // Output css only in 'bot_wtc' extensions tab.
    if ($event != 'bot_wtc_tab') {
        return;
    }

    echo '<style>

    #bot_wtc_table {
        padding: 10px 0 20px;
        margin-left: 0;
    }

    #bot_wtc_table td {
        vertical-align: top;
        padding: 5px;
        white-space: nowrap;
    }

    #bot_wtc_table td p {
        margin: 3px 0 0 0;
    }
    #bot_wtc_table td:last-child .txp-form-checkbox {
        line-height: 2.3;
    }

    #bot_wtc_table td select {
        width: 100%;
        background-color: transparent;
    }

    .txp-form-checkbox * {
        cursor: pointer;
    }

    #bot_advanced {}

    #bot_advanced .txp-grid-cell {
        width: calc((100% - 12px)/3);
        border: none;
        margin-bottom: 20px;
    }
    input[name="bot_adv_class"] {
        width: 100%;
    }

    #bot_uninstall-details, #bot_static_sections-details, #bot_js_box-details {
        padding: 0 20px;
    }

    #bot_uninstall-details {
        padding-bottom: 20px;
    }

    #bot_wtc_script {
        width: 100%;
        border: dotted #ccc 1px;
    }

    .bot_update_button {
        margin: 20px 0;
        clear: both;
    }

    #bot_uninstall {}

    #bot_install {
        margin: auto;
        width: 800px;
    }

    a.bot_push {
        padding-right: 13px;
        line-height: 1.85;
    }

    a.bot_push.bot_arrow span.ui-icon {
        background-position: 0 0;
    }
    #bot_static_sections-details {
        padding-top: 20px;
    }
    #bot_js_box-details .txp-actions {
        padding-top: 5px;
        padding-bottom: 10px;
    }

</style>';
}


// ===========================================================
// Builds array of sections to hide

function bot_hide_per_section_array()
{
    // Array of values from the db
    $db_values = bot_wtc_fetch_db();

    for ($i =0; $i<count($db_values); $i++) {
        if ($db_values[$i]['sections']) {
            $sections_to_hide = explode('|', $db_values[$i]['sections']);
            foreach ($sections_to_hide as $section) {
                $bot_hide_per_section[$section][] = $db_values[$i]['item'];
            }
        }
    }

    // Return array only if values exist
    if (isset($bot_hide_per_section)) {
        return $bot_hide_per_section;
    }
}



// ===========================================================
// js rows dealing with items to hide on section change AND on page load

function bot_wtc_jquery_hide_sections_rows()
{
    $bot_hide_per_section = bot_hide_per_section_array();
    foreach ($bot_hide_per_section as $section => $fields) {
        echo n.'            if (value=="'.$section.'"){'.n;
        for ($i =0; $i<count($fields); $i++) {
            echo '                '.$fields[$i].'.hide();'.n;
        }
        echo '            }'.n;
    }
}



// ===========================================================
// js rows to restore every previously hidden item on section change

function bot_wtc_jquery_restore_rows()
{
    $bot_hide_per_section = bot_hide_per_section_array();
    foreach ($bot_hide_per_section as $section => $fields) {
        for ($i =0; $i<count($fields); $i++) {
            $out[] = $fields[$i];
        }
    }
    $out = array_unique($out);
    foreach ($out as $value) {
        echo '            '.$value.'.show();'.n;
    }
}



// ===========================================================
//  Builds the script

function bot_hide_per_section()
{
    global $event;
    if ($event !== 'article') {
        return;
    }

    $bot_hide_per_section = bot_hide_per_section_array();
    // Output js only if values exist
    if ($bot_hide_per_section) {
        echo
                '<script>'.n.
                '    $(document).ready(function() {'.n;
        echo
                '        $("select#section").change(function(){'.n;
        bot_wtc_jquery_restore_rows();
        echo
                '            var value = $("select#section").val();';
        bot_wtc_jquery_hide_sections_rows();
        echo
                '        }).change();'.n.
                '    });'.n.
                '</script>';
    }
}



// ===========================================================
// Invisible sections in section list

function bot_hidden_sections()
{
    global $event;
    if ($event !== 'article') {
        return;
    }

    // Fetch prefs value for bot_wtc_static_sections
    $bot_hidden_sections = safe_field('val', 'txp_prefs', 'name = "bot_wtc_static_sections"');

    // Output js only if values exist
    if ($bot_hidden_sections) {
        $sections = explode("|", $bot_hidden_sections);
        echo
        '<script>'.n.
        '    $(document).ready(function() {'.n;
        foreach ($sections as $value) {
            echo    '           $("select#section option:not(:selected)[value=\''.$value.'\']").remove();'.n;
        }
        echo
        '    });'.n.
        '</script>';
    }
}



// ===========================================================

function bot_wtc_jquery_rows()
{
    global $bot_items;
    // Array of values from the db
    $db_values = bot_wtc_fetch_db();

    $rows = '';
    for ($i = 0; $i <count($db_values); $i++) {
        $item = ($db_values[$i]['item'] != '') ? $db_values[$i]['item'] : '';
        $position = ($db_values[$i]['position'] != '') ? '.'.$db_values[$i]['position'] : '';
        $destination = ($db_values[$i]['destination'] != '') ? '('.$db_values[$i]['destination'].')' : '';
        $class = ($db_values[$i]['class'] != '') ? '.addClass("'.$db_values[$i]['class'].'")' : '';
        $row = $item.$position.$destination.$class.';'.n;
        $rows .= $row;
    }
    return $rows;
};



// ===========================================================

function bot_wtc()
{
    // Fetch prefs value for bot_wtc_script
    $bot_wtc_script = safe_field('val', 'txp_prefs', 'name = "bot_wtc_script"');
    // Fetch 'position' from db to check if a move is saved
    $position = safe_column('position', 'bot_wtc', '1=1');
    // Fetch 'class' from db to check if a class is saved
    $class = safe_column('class', 'bot_wtc', '1=1');

    // Output code only if a preference is saved
    if (isset($position) || isset($class)) {
        echo
        '<script>'.n.
        '    $(document).ready(function() {'.n.
                bot_wtc_jquery_rows().n.
        '    });'.n.
        '</script>';
    }
    if ($bot_wtc_script) {
        echo n.$bot_wtc_script.n;
    };
}
