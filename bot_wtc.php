<?php

if(@txpinterface == 'admin') {
	add_privs('bot_wtc_tab', '1,2');
	register_tab('extensions', 'bot_wtc_tab', 'Write tab customize');
	register_callback('bot_wtc_tab', 'bot_wtc_tab');

	global $event;
	if($event == 'article') {
	  register_callback('bot_wtc', 'admin_side', 'head_end');
	  register_callback('bot_hide_per_section', 'admin_side', 'head_end');
	  register_callback('bot_hidden_sections', 'admin_side', 'head_end');
	}
	if($event == 'bot_wtc_tab') {
		register_callback('bot_wtc_css','admin_side','head_end');
	}
	register_callback('bot_wtc_welcome','plugin_lifecycle.bot_write_tab_customize');
}

// ===========================================




$bot_arr_selectors = array(

    'writetab_main_content' => '$("#main_content")',

      'writetab_view_modes' => '$("#view_modes")',
      'title' => '$(".title")',
      'body' => '$(".body")',
      'excerpt' => '$(".excerpt")',

    'writetab_sidebar' => '$("#supporting_content")',

      'save' => '$(".txp-save")',

      'actions' => '$(".txp-actions")',

        'create_new' => '$(".txp-new")',
        'duplicate' => '$(".txp-clone")',      
        'view' => '$(".txp-article-view")', 

      'page_article_nav_hed' => '$(".nav-tertiary")',

      'sort_display' => '$("#txp-write-sort-group")',

        'status' => '$(".status")',
        'section' => '$(".section")',
        'category1' => '$(".category-1")',
        'category2' => '$(".category-2")',

      'date_settings' => '$("#txp-dates-group")',

        'publish' => '$(".posted")',
        'publish_time' => '$(".posted.time")',
        'reset_time' => '$(".posted-now")',
        'expired' => '$(".expires")',
        'expire_time' => '$(".expires.time")',

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

      'advanced_options' => '$("#txp-advanced-group")',

        'article_markup' => '$(".markup-body")',
        'excerpt_markup' => '$(".markup-excerpt")',
        'override_default_form' => '$(".override-form")',

      'textile_help' => '$("#txp-textfilter-group")',

      'recent_articles' => '$("#txp-recent-group")'

);

// creates the translated main plugins array ($bot_items)
global $bot_items;
foreach ( $bot_arr_selectors as $title => $selector ) {
    bot_wtc_insert_in_main_array($title, $selector);
}
natcasesort($bot_items);



// ===========================================================
// Helper functions
// ===========================================================

// helps build the main array

function bot_wtc_insert_in_main_array ($title, $selector)
{
	global $bot_items;
	if (strpos($title, '!bot!'))
	{
		$split_titles = explode("!bot!", $title);
		$title = '';
		for ($i = 0; $i < count($split_titles); $i++)
		{
			$title .= gTxt($split_titles[$i]); // split and build translated title
		}
	}
	else
	{
		$title = gTxt($title); // gets the title to allow translation
	}
	$bot_items [$selector] = gTxt($title);
	return $bot_items;
}



// ===========================================

// creates an array of values extracted from the database

function bot_wtc_fetch_db()
{
	if(bot_wtc_check_install()){
		$out = safe_rows('id, item, position, destination, sections, class', 'bot_wtc ','1=1');
		return $out;
	}
}



// ===========================================================

// creates an array of all cfs for selectInput

function bot_get_cfs()
{
	$r = safe_rows_start('name, val, html', 'txp_prefs','event = "custom" AND val != ""');
	if ($r) {
		global $arr_custom_fields;
		while ($a = nextRow($r)) {
			$name = str_replace('_set', '', $a['name']);
			$html = $a['html'];
			if ($html == 'checkbox' || $html == 'multi-select') {
				$selector = '$("p:has(*[name=\''.$name.'[]\'])")';
			}
			else
			{
				$selector = '$("p:has(*[name=\''.$name.'\'])")';
			}
			$val = $a['val'];
			$arr_custom_fields[$selector] = $val;
		}
	}
	if ($arr_custom_fields) {
 	natcasesort($arr_custom_fields); // sort cfs - used instead of asort because is case-insensitive
	return $arr_custom_fields;
    }
};



// ===========================================================

// creates an array of all sections for selectInput

function bot_get_sections()
{
	$r = safe_rows_start('name, title', 'txp_section','1=1');
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

// update button

function bot_update_button()
{
	return n.'<div class="bot_update_button">'
		.n.eInput('bot_wtc_tab')
		.n.sInput('update')
		.n.fInput('submit', 'update', 'Update', 'publish')
		.'</div>';
}



// ===========================================================

// checks if item is a layout region

function bot_wtc_is_region($item)
{
    $item = get_magic_quotes_gpc() ? $item : doSlash($item) ;

	if($item == '$(\"#main_content\")'
	|| $item == '$(\"#supporting_content\")'
	|| $item == '$(\"#view_modes\")'
	)
	{
		return 1;
	}
	return 0;
}



// ===========================================================

// outputs html for warnings

function bot_warning($warning)
{
	return graf(hed(gTxt($warning),'3', ' id="bot_warning"'));
};

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

// set up bot_wtc tables

function bot_wtc_install()
{

	// Create the bot_wtc table
	safe_create('bot_wtc','
	    id           INT          NOT NULL AUTO_INCREMENT,
	    item		     VARCHAR(255) NOT NULL,
	    position     VARCHAR(255) NOT NULL,
	    destination  VARCHAR(255) NOT NULL,
	    sections     TEXT		      NOT NULL,
	    class    	   VARCHAR(255) NOT NULL,
	    PRIMARY KEY (id)
	');

	// set pref entries in txp_prefs table
	set_pref ('bot_wtc_script','', 'bot_wtc_','2');
	set_pref ('bot_wtc_static_sections','', 'bot_wtc_', '2');

}


//===========================================

// remove bot_wtc tables

function bot_wtc_deinstall()
{

	// Drop the bot_wtc table
	safe_drop("bot_wtc");
	// Remove bot_wtc prefs
	safe_delete('txp_prefs', 'event = "bot_wtc_"' );

}


// ===========================================================

// updates cfs selectors in db | introduced in bot_wtc 0.7.1

function bot_wtc_update()
{
	// proceeds only if plugin is already installed
	if (!bot_wtc_check_install()) {
		return;
	}
	safe_alter('bot_wtc', 'CHANGE sections sections TEXT',0);
	$db_values = bot_wtc_fetch_db(); // array
	for ($i =0; $i < count($db_values); $i++) {
		$id = $db_values[$i]['id'];
		$item = $db_values[$i]['item'];
		$destination = $db_values[$i]['destination'];
		// updates cfs
    	if (strpos($item,'custom')) { // if item contains the substring 'custom'
			$cf_number = preg_replace("/[^0-9]/", '', $item); // ditch anything that is not a number
			$type = safe_field('html', 'txp_prefs', 'name = "custom_'.$cf_number.'_set"'); // retrieve cfs type
			if ($type == 'checkbox' || $type == 'multi-select') {
				$selector = '$("p:has(*[name=\'custom_'.$cf_number.'[]\'])")'; // adds the '[]' part
			}
			else
			{
				$selector = '$("p:has(*[name=\'custom_'.$cf_number.'\'])")';
			}
			safe_update('bot_wtc', 'item = "'.doslash($selector).'"', 'id = "'.$id.'"');
     	}
    	if (strpos($destination,'custom')) { // if destination contains the substring 'custom'
			$cf_number = preg_replace("/[^0-9]/", '', $destination); // ditch anything that is not a number
			$type = safe_field('html', 'txp_prefs', 'name = "custom_'.$cf_number.'_set"'); // retrieve cfs type
			if ($type == 'checkbox' || $type == 'multi-select') {
				$selector = '$("p:has(*[name=\'custom_'.$cf_number.'[]\'])")'; // adds the '[]' part
			}
			else
			{
				$selector = '$("p:has(*[name=\'custom_'.$cf_number.'\'])")';
			}
			safe_update('bot_wtc', 'destination = "'.doslash($selector).'"', 'id = "'.$id.'"');
     	}
    }
}


// ===========================================================

	// Check if the bot_wtc table exists

function bot_wtc_check_install()
{
	// if number of rows is false, table does not exist
	if (safe_count("bot_wtc", "1 = 1") !== false) {
		return true;
	}
	return false;
}



//===========================================

// outputs all items for selectInput() (used for destination dropdown)

function bot_all_items_selectinput()
{
	global $bot_items;
	$cfs = bot_get_cfs(); // get cfs array in the form: cf_selector => cf_name
	// final values for the txp function selectInput (including cfs if any)
	if (is_array($cfs)) { // if there is at least one custom field set adds cfs to $bot_items array
		$all_items_select = array_merge($cfs, $bot_items);
	}
	else {
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
	$db_values = bot_wtc_fetch_db(); // array of values from the db
	$all_items = bot_all_items_selectinput();
	if (bot_wtc_check_install()) {
		$used_items = safe_column('item', 'bot_wtc', '1=1'); // numeric array of item values from the db
		foreach ($all_items as $item => $title) {
	   		if (!in_array($item, $used_items)) {
	 			$items_selectInput[$item] = $title;
	 		}
		}
	}
	else {
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

// outputs the rows for the html table in the bot_wtc_tab

function bot_wtc_output_rows()
{
	global $bot_items;

	$selectInput_for_position = array('insertBefore'=>'before','insertAfter'=>'after'); // position values for the txp function selectInput
	$db_values = bot_wtc_fetch_db(); // array of values from the db

    $destination_selectInput = bot_all_items_selectinput();
	$items_selectInput = bot_contextual_selectinput();

	// builds rows for new item sections list
	$sections= bot_get_sections(); // get sections array
	$new_item_sections_rows = '';
	foreach ($sections as $key => $value) {
		$new_item_sections_row = '<label>'.checkbox('new_item_sections[]', $key, '0').$value.'</label><br />';
		$new_item_sections_rows .= $new_item_sections_row;
    }
    $new_item_sections_rows .= '<p ><a href="#" class="bot_all">'.gTxt("all").'</a> | <a href="#" class="bot_none">'.gTxt("none").'</a></p>'; // hide all/none

	// new item insertion
	$rows = "";
	$input_row = tr(
		td(selectInput('new_item',bot_contextual_selectinput(), '', '1'), '', 'bot_hilight')
		.td(selectInput('new_item_position', $selectInput_for_position, '', '1'))
		.td(selectInput('new_item_destination',bot_all_items_selectinput(), '', '1'))
		.td('<p><a href="#" class="bot_push">'.gTxt("tag_section_list").'</a></p><div class="bot_collapse">'.$new_item_sections_rows.'</div>')
		.td(finput('text','new_item_class', ''))
		.td()
		);
		$rows .= $input_row;

	// other rows - output if at least one record was already set
	if ($db_values){
		for ($i = 0; $i < count( $db_values ); $i++){
			// data for "sections to show" selectinput - decides wether a section is checked or not
			$bot_hide_in_this_sections_array = explode('|', $db_values[$i]['sections']);
			$item_sections_rows = '';
			foreach ($sections as $key => $value) { // if section is in db mark as checked
			    $checked = in_array($key, $bot_hide_in_this_sections_array) ? '1': '0';
				$item_sections_row =  '<label>'.checkbox('bot_wtc_sections_for_id_'.$db_values[$i]['id'].'[]', $key, $checked).$value.'</label><br />';
				$item_sections_rows .= $item_sections_row;
		    }
		    $item_sections_rows .= '<p><a href="#" class="bot_all">'.gTxt("all").'</a> | <a href="#" class="bot_none">'.gTxt("none").'</a></p>'; // hide all/none
			$single_row = tr(
			td(selectInput('item[]',bot_contextual_selectinput($db_values[$i]['item']), $db_values[$i]['item'],'0'), '', 'bot_hilight')
			.td(selectInput('item_position[]', $selectInput_for_position, $db_values[$i]['position'], '1'))
			.td(selectInput('item_destination[]',bot_all_items_selectinput(), $db_values[$i]['destination'],'1'))
 			.td('<p><a href="#" class="bot_push">'.gTxt("tag_section_list").'</a></p><div class="bot_collapse">'.$item_sections_rows.'</div>')
			.td(finput('text', 'item_class[]', $db_values[$i]['class']))
			.td(checkbox('bot_delete_id[]', $db_values[$i]['id'], '0').'<label for="bot_delete_id"> '.gTxt('delete').'</label>'))
			.hInput('bot_wtc_id[]', $db_values[$i]['id']);

			$rows .= $single_row;
		}
	};
	return $rows;
}



//===========================================

// builds rows for sections list

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
		$static_sections_row = '<label>'.checkbox('static_sections[]', $key, $checked).$value.'</label><br />';
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
		$item_row = '<label>'.checkbox('bot_adv_items[]', htmlspecialchars($key), '0').$value.'</label><br />';
		$item_rows .= $item_row;
    $sections= bot_get_sections(); // get sections array
    }
	$sections_rows = '';
	foreach ($sections as $key => $value) {
		$sections_row = '<label>'.checkbox('bot_adv_sections[]', $key, '0').$value.'</label><br />';
		$sections_rows .= $sections_row;
    }
    return '<section role="region" class="txp-details" id="bot_advanced" aria-labelledby="bot_advanced-label">'
        .n.'<h3 id="bot_advanced-label">Advanced/Multiple selection</h3>'
        .n.'<div role="group">'
        .n.form(n.bot_update_button()
        .n.'<div id="bot_adv_items"><h4>Items</h4>'.$item_rows.'</div>' // items list
        .n.'<div  id="bot_adv_hide"><h4>Hide in sections</h4>'.$sections_rows.'<p><a href="#" class="bot_all">'.gTxt("all").'</a> | <a href="#" class="bot_none">'.gTxt("none").'</a></p></div>' // sections list
        .n.'<div  id="bot_adv_class"><h4>Set css class</h4>'.finput('text','bot_adv_class', '').'</div>' // class
        .n.bot_update_button()
        .n.'</div>'
        .n.'</section>'
    );
}

//===========================================



function bot_wtc_tab($event, $step)
{
	global $bot_items;
	$cfs = bot_get_cfs();

	pagetop('Write tab customize '.gTxt('preferences'), ($step == 'update' ? gTxt('preferences_saved') : ''));
	echo hed('Write tab customize','2');

	if ($step == 'update'){
	    // set function variables
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
		if ($item){ // if at least a saved item exists

           	$db_values = bot_wtc_fetch_db(); // array of values from the db
			for ($i = 0; $i < count($item); $i++){
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
					if (!((bot_wtc_is_region($item[$i]) XOR bot_wtc_is_region($item_destination[$i])) && $item_destination[$i])){
  					    // check if item is different from destination
						if($item[$i] != $item_destination[$i]){
       						safe_update("bot_wtc",
							"position = '"
							.doslash($item_position[$i])
							."', destination = '"
							.doslash($item_destination[$i])
							."', item = '"
							.doslash($item[$i])
							."', sections = '"
							.doslash($item_sections_string)
							."', class = '"
							.doslash($item_class[$i])
							."'", "id = '".$bot_wtc_id[$i]."'");
						}
						else {
							echo bot_warning('same_item_warning');
						}
					}
					else {
						echo bot_warning('region_warning');
					}
				}
				else {
					echo bot_warning('combo_warning');
				}
			}
		}

		// db insert for new item
		// allowed input combinations
		if (($new_item && $new_item_destination && $new_item_position)
		|| ($new_item && $new_item_class && !$new_item_destination && !$new_item_position)
		|| ($new_item && $new_item_sections && !$new_item_destination && !$new_item_position)){
			// check if a column is linked with a non-column item
			if (!((bot_wtc_is_region($new_item) XOR bot_wtc_is_region($new_item_destination)) &&  $new_item_destination)){
				// check items are not the same
				if($new_item != $new_item_destination){
                    // transforms the sections array in a string
                    $new_item_sections_string = $new_item_sections ? implode('|', $new_item_sections) : '';
					safe_insert("bot_wtc",
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
					."'");
				}
				else {
					echo bot_warning('same_item_warning');
				}
			}
			else {
				echo bot_warning('td_warning');
			}
		}

		elseif ($new_item || $new_item_destination || $new_item_position || $new_item_class || $new_item_sections){
			echo bot_warning('combo_warning');
		}

		if ($delete_id){ // checks if there is something to delete
			foreach ($delete_id as $id) {
				safe_delete('bot_wtc', 'id ="'.$id.'"' );
			}
		}


		// update advanced preferences
        if ($bot_adv_items AND ($bot_adv_sections || $bot_adv_class)) { // check if item AND section OR class is selected

            $db_values = bot_wtc_fetch_db(); // first array: all values from db

            if ($bot_adv_sections) {
            	$bot_db_sections = array(); // more specific array: only item => sections
                for ($i =0; $i < count($db_values); $i++) {
                	$bot_db_sections[$db_values[$i]['item']] = $db_values[$i]['sections'];
                }

                foreach ($bot_adv_items as $item) { // iterates posted items
                    // fetch -if any- existing sections from db for current item and merges arrays eliminating duplicates
                    if (is_array($bot_db_sections) AND array_key_exists($item, $bot_db_sections)) {
                       	$db_sect_array = explode('|', $bot_db_sections[$item]);
                        $final_array = array_unique(array_merge($db_sect_array, $bot_adv_sections));
                        $bot_adv_sections_string = implode('|', $final_array); // new sections string
                    }
                    else {
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

                foreach ($bot_adv_items as $item) { // iterates posted items
                    // fetch -if any- existing class from db for current item and merges arrays eliminating duplicates
                    if (is_array($bot_db_classes) AND array_key_exists($item, $bot_db_classes)) {
                       	$db_class_array = explode(' ', $bot_db_classes[$item]);
                       	$posted_class_array = explode(' ', $bot_adv_class);
                        $final_array = array_unique(array_merge($db_class_array, $posted_class_array));
                        $bot_adv_classes_string = implode(' ', $final_array); // new sections string
                    }
                    else {
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
        }
        elseif ($bot_adv_sections || $bot_adv_class) {
        	echo bot_warning('no_items_warning');
        }

		// updates static sections prefs
    if ($static_sections) {
    	$static_sections_string = implode('|', $static_sections);
	    safe_update('txp_prefs', 'val= "'.doslash($static_sections_string).'", html="text_input" ', 'name = "bot_wtc_static_sections"' );
    }

    // updates script prefs
		if ($bot_wtc_script) {
	  	safe_update('txp_prefs', 'val= \''.doslash($bot_wtc_script).'\', html=\'textarea\' ', 'name = \'bot_wtc_script\'' );
		}
	}

	if (bot_wtc_check_install()) { // what to show when accessing tab

		$bot_wtc_script = safe_field('val', 'txp_prefs', 'name = "bot_wtc_script"'); // fetch prefs value for bot_wtc_script
		echo n.t.'<div class="txp-layout-textbox">'; // main div
		echo '<p id="bot_controls" class="nav-tertiary">
            <a id="bot_expand_all" class="navlink" href="#">Expand all</a>
            <a id="bot_collapse_all" class="navlink" href="#">Collapse all</a>
            <a id="bot_advanced_open" class="navlink" href="#">Toggle advanced</a>
            </p>';
		echo n.t.bot_advanced();
		echo n.t.'<div id="bot_main">'; // main div

		echo form( // beginning of the form
 			'<table id="bot_wtc_table" class="txp-list">' // beginning of the table
			.'<thead>'
            .tr(hcell(strong(gTxt('Item')))
			.hcell(strong(gTxt('Position')))
			.hcell(strong(gTxt('Destination')))
			.hcell(strong(gTxt('Hide in:')))
			.hcell(strong(gTxt('Class')))
			.hcell() // collapse all/show all)
			).'</thead>'
			.bot_wtc_output_rows() // html rows generated by "bot_wtc_output_rows()"
			.'</table>' // end of the table

            .bot_update_button()

			.n.'<section role="region" class="txp-details" id="bot_static_sections" aria-labelledby="bot_static_sections-label">'  // static sections
			.n.'<h3 id="bot_static_sections-label" class="txp-summary expanded">'
			.n.'<a class="bot_push toggle" role="button" href="#bot_static_sections-details" aria-expanded="true">Hide sections in sections dropdown</a>'
			.n.'</h3>'
			.n.'<div id="bot_static_sections-details" class="bot_collapse">'
			.bot_wtc_static_sections_select()
			.bot_update_button()
			.n.'</div>'
			.n.'</section>'

			.n.'<section role="region" class="txp-details" id="bot_js_box" aria-labelledby="bot_js_box-label">'  // js code box
			.n.'<h3 id="bot_js_box-label" class="txp-summary expanded">'
			.n.'<a class="bot_push toggle" href="#" role="button" href="#bot_js_box-details" aria-expanded="true">Additional js code</a>'
			.n.'</h3>'
			.n.'<div id="bot_js_box-details" class="bot_collapse">'
			.n.'<a id="bot_js_link" href="#">Add external script</a> | <a id="bot_jq_link" href="#">Add Jquery script</a>'
			.n.'<textarea id="bot_wtc_script" name="bot_wtc_script" cols="60" rows="10">'.$bot_wtc_script.'</textarea>' // script textarea
			.n.bot_update_button()
			.n.'</div>'
			.n.'</section>'

		);

	}

	// snippets to insert in the script box
	$bot_jquery_snippet = '<script type=\"text/javascript\">\n    $(document).ready(function() {\n        //your code here\n    });\n<\/script>\n';
	$bot_js_snippet = '<script type=\"text/javascript\" src=\"path_to_script\"><\/script>\n';

  // add some jquery action
	echo
	'<script  type="text/javascript">'.n.
	'	$(document).ready(function() {'.n.
			'$("div.bot_collapse").hide()'.n.
			'$("section#bot_advanced").hide()'.n.
			'$("a.bot_push").click(function(){'.n.
			'  $(this).toggleClass("bot_arrow").parent().next().slideToggle();'.n.
			'  return false;'.n.
			'});'.n.
			'$("#bot_collapse_all").click(function(){'.n.
			'  $("div.bot_collapse").slideUp();'.n.
			'  return false;'.n.
  			 '});'.n.
			'$("#bot_expand_all").click(function(){'.n.
			'  $("div.bot_collapse").slideDown();'.n.
			'  return false;'.n.
  			 '});'.n.
			'$("#bot_advanced_open").click(function(){'.n.
			'  $("section#bot_advanced").slideToggle();'.n.
			'  $("div#bot_main").toggle();'.n.
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
	'	});'.n.
	'</script>';
}



// ===========================================================
// plugins output
// ===========================================================

// css for the plugin tab under extensions

function bot_wtc_css() {

	global $event;
	// Output css only in 'bot_wtc' extensions tab.
	if($event != 'bot_wtc_tab') {
		return;
	}

	echo '<style type="text/css">
			#bot_main {
				margin: auto; width:800px;
			}
			#page-bot_wtc_tab h2 {
				text-align: center;	margin:20px auto; padding-bottom:10px;
			}
			#bot_controls {
				margin: 20px auto;
			}
			#bot_controls a{margin-right:-5px}
			#bot_expand_all,
			#bot_collapse_all,
			#bot_advanced_open {
				font-size:10px;
			}
			#bot_wtc_table {
			 	padding:10px 0 20px; margin-left:0;
			}
			#bot_wtc_table td {
				vertical-align:center;
				padding:5px;
				white-space:nowrap;
			}
			#bot_wtc_table td p{margin:3px 0 0 0}
			#bot_advanced {}
			#bot_adv_items,
			#bot_adv_hide,
			#bot_adv_class
			{
				width:260px; float:left; margin-bottom:20px;
			}
			#bot_uninstall-details,
			#bot_static_sections-details,
			#bot_js_box-details {
				padding:0 20px;
			}
		    #bot_uninstall-details{padding-bottom:20px;}

			#bot_wtc_script {
				width:100%; border:dotted #ccc 1px;
			}
			.bot_update_button {
				margin:20px 0; clear:both;
			}
			#bot_uninstall {
			}
			#bot_install {
				margin: auto; width:800px;
			}
			.bot_hilight {
				background:#eaeaea
			}
			a.bot_push {
				font-weight:bold; background: url(txp_img/arrowupdn.gif) no-repeat right bottom; padding-right:13px;
			}
			#bot_warning {
				text-align:center; background:#990000; color:#fff; margin: 20px auto; padding:10px; text-shadow:none;
			}
		</style>';
}



// ===========================================================

// builds array of sections to hide

function bot_hide_per_section_array()
{

	$db_values = bot_wtc_fetch_db();  // array of values from the db

	for ($i =0; $i<count($db_values); $i++) {
		if ($db_values[$i]['sections']) {
		    $sections_to_hide = explode('|', $db_values[$i]['sections']);
		    foreach ($sections_to_hide as $section) {
				$bot_hide_per_section[$section][] = $db_values[$i]['item'];
			}
	    }
	}
	if (isset($bot_hide_per_section)) { // return array only if values exist
 		return $bot_hide_per_section;
 	}
}



// ===========================================================

// js rows dealing with items to hide on section change AND on page load

function bot_wtc_jquery_hide_sections_rows()
{
	$bot_hide_per_section = bot_hide_per_section_array();
	foreach ($bot_hide_per_section as $section => $fields) {
		echo n.'			if (value=="'.$section.'"){'.n;
        for ($i =0; $i<count($fields); $i++) {
			echo '				'.$fields[$i].'.hide();'.n;
        }
		echo '			}'.n;
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
	echo '			'.$value.'.show();'.n;
	}

}



// ===========================================================

//  builds the script

function bot_hide_per_section()
{
  global $event;
  if($event !== 'article') {
  	return;
  }

  $bot_hide_per_section = bot_hide_per_section_array();
	// output js only if values exist
	if ($bot_hide_per_section) {
		 	echo
				'<script  type="text/javascript">'.n.
				'	$(document).ready(function() {'.n;
			echo
				'		$("select#section").change(function(){'.n;
							bot_wtc_jquery_restore_rows();
			echo
				'			var value = $("select#section").val();';
							bot_wtc_jquery_hide_sections_rows();
			echo
				'		}).change();'.n.
				'	});'.n.
				'</script>';
		}
	}



// ===========================================================

// invisible sections in section list

function bot_hidden_sections()
{
  global $event;
  if($event !== 'article') {
  	return;
  }

	// fetch prefs value for bot_wtc_static_sections
	$bot_hidden_sections = safe_field('val', 'txp_prefs', 'name = "bot_wtc_static_sections"');
	// output js only if values exist
	if ($bot_hidden_sections) {
		$sections = explode("|", $bot_hidden_sections);
		echo
		'<script  type="text/javascript">'.n.
		'	$(document).ready(function() {'.n;
		foreach ($sections as $value) {
			echo    '           $("select#section option:not(:selected)[value=\''.$value.'\']").remove();'.n;
		}
		echo
		'	});'.n.
		'</script>';
	}

}



// ===========================================================



function bot_wtc_jquery_rows()
{
	global $bot_items;
	// array of values from the db
	$db_values = bot_wtc_fetch_db();

	$rows = '';
	for ($i = 0; $i <count($db_values); $i++)
	{
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
	// fetch prefs value for bot_wtc_script
	$bot_wtc_script = safe_field('val', 'txp_prefs', 'name = "bot_wtc_script"');
 	// fetch 'position' from db to check if a move is saved
 	$position = safe_column('position', 'bot_wtc', '1=1');
 	// fetch 'class' from db to check if a class is saved
 	$class = safe_column('class', 'bot_wtc', '1=1');

	// output code only if a preference is saved
	if(isset($position) || isset($class)){
		echo
		'<script  type="text/javascript">'.n.
		'	$(document).ready(function() {'.n.
				bot_wtc_jquery_rows().n.
		'	});'.n.
		'</script>';
	}
	if ($bot_wtc_script) {
		echo n.$bot_wtc_script.n;
	};

}