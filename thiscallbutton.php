<?php

/**
 * Plugin Name:       This Call Button
 * Description:       ThisCallButton plugin adds a call button on your wordpress websites. Visitors on your website are able to click on the call button to call your phone immediately through web browsers. No external devices/apps required.
 * Version:           1.20
 * Requires at least: 5.1
 * Requires PHP:      7.1
 * Author:            ThisCallButton.com
 * Author URI:        https://www.thiscallbutton.com/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       thiscallbutton
 * */



/**
 * 
 * Setting Page
 **/
add_action( 'admin_init',  'thiscallbutton_register_setting' );

function thiscallbutton_register_setting(){
 
	register_setting(
		'thiscallbutton_settings', // settings group name
		'tcbtn_call_widget_public_key', // option name
		'tcbtn_setting_validate' // sanitization function
	);
	
	register_setting(
		'thiscallbutton_settings', // settings group name
		'tcbtn_call_widget_page_rule_mode',
		'sanitize_text_field' // sanitization function
	);
	
	register_setting(
		'thiscallbutton_settings', // settings group name
		'tcbtn_call_widget_page_rules',
		'sanitize_text_field' // sanitization function
	);
 
	add_settings_section(
		'main_settings_section', 
		'', // title - no needed now
		'', // callback function - no needed now
		'thiscallbutton-settings' // page slug
	);
 
	add_settings_field(
		'tcbtn_call_widget_public_key',
		'Call Widget Public Key',
		'tcbtn_call_widget_public_key_text_field_html', // function which prints the field
		'thiscallbutton-settings', // page slug
		'main_settings_section', // section ID
		array( 
			'label_for' => 'tcbtn_call_widget_public_key',
			'class' => 'tcbtn_call_widget_public_key-class', // for <tr> element
		)
	);
	
	add_settings_field(
		'tcbtn_call_widget_page_rule_mode',
		'Page Rule Mode',
		'tcbtn_call_widget_page_rule_mode_select_html', // function which prints the field
		'thiscallbutton-settings', // page slug
		'main_settings_section', // section ID
		array( 
			'label_for' => 'tcbtn_call_widget_page_rule_mode',
			'class' => 'tcbtn_call_widget_page_rule_mode-class', // for <tr> element
		)
	);
	
	add_settings_field(
		'tcbtn_call_widget_page_rules',
		'Page Rules',
		'tcbtn_call_widget_page_rules_text_html', // function which prints the field
		'thiscallbutton-settings', // page slug
		'main_settings_section', // section ID
		array( 
			'label_for' => 'tcbtn_call_widget_page_rules',
			'class' => 'tcbtn_call_widget_page_rules-class', // for <tr> element
		)
	);
}

function tcbtn_setting_validate($input) {
	// Check our textbox option field contains no HTML tags - if so strip them out
	$input['text_string'] =  wp_filter_nohtml_kses(str_replace("\n", ' ', $input['text_string']));	
	return $input; // return validated input
}

function tcbtn_call_widget_public_key_text_field_html(){
 
	$text = get_option( 'tcbtn_call_widget_public_key' );
 
	printf(
		'<input type="text" id="tcbtn_call_widget_public_key" name="tcbtn_call_widget_public_key" value="%s" style="width:320px"/><div class="tcbtn_call_widget_public_key-description" style="padding-top:6px">If you have not got a key yet, please obtain one at <a href="https://www.thiscallbutton.com/" target="_blank">https://www.thiscallbutton.com</a>. It is free.',
		esc_attr( $text )
	);
 
}

function tcbtn_call_widget_page_rule_mode_select_html(){
 
	$selected = get_option( 'tcbtn_call_widget_page_rule_mode' );
	if ($selected == 'allow'){
		$allowSelected = 'selected="selected"';
		$disallowSelected = '';
	}else{
		$disallowSelected = 'selected="selected"';
		$allowSelected = '';
	}
 	
	printf(
		'<select id="tcbtn_call_widget_page_rule_mode" name="tcbtn_call_widget_page_rule_mode">
			<option value="allow" '.$allowSelected.'>Allow</option>
  			<option value="disallow" '.$disallowSelected.'>Disallow</option>
		</select>',
		esc_attr( $text )
	);
	
	function tcbtn_call_widget_page_rules_text_html(){
 
		$text = trim(get_option( 'tcbtn_call_widget_page_rules' ));
		$text = str_replace(' ', "\n", $text);
		printf(
			'<textarea id="tcbtn_call_widget_page_rules" rows="10" cols="80" name="tcbtn_call_widget_page_rules">%s</textarea>
			<div class="tcbtn_call_widget_page_rules-description" style="padding-top: 6px">eg:<br>http://www.example.com/<br>https://example.com/contact-us<br>https://www.example.com/products/*<br><br>Note:<br>1 row for 1 URL.<br>The wildcard * can only be applied to the end of URLs.</div>',
			esc_attr($text)
		);

	}
 
}


/**
 * 
 * Setting menu
 * */
add_action( 'admin_menu', 'add_thiscallbutton_setting_menu' );
function add_thiscallbutton_setting_menu() {
	add_options_page( 'This Call Button Setting', 'This Call Button', 'manage_options', 'thiscallbutton_setting_menu', 'thiscallbutton_setting_options' );
}
function thiscallbutton_setting_options() {
	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}
	echo '<div class="wrap">
	<h1>This Call Button Settings</h1>
	<form method="post" action="options.php">';
 
		settings_fields( 'thiscallbutton_settings' ); // settings group name
		do_settings_sections( 'thiscallbutton-settings' ); // just a page slug
		
		submit_button();
 
	echo '</form></div>';
}




/**
 * 
 * Load library
 **/

add_action( 'wp_enqueue_scripts', 'thiscallbutton_enqueue_script' );
function thiscallbutton_enqueue_script(){
	$key = get_option( 'tcbtn_call_widget_public_key' );
	if (!$key){
		return;
	}
	
	global $wp;
	$currentURL = trim(trim(home_url($wp->request)),'/');
	$pageRuleMode = get_option( 'tcbtn_call_widget_page_rule_mode' );
	$pageRules = explode(' ', get_option( 'tcbtn_call_widget_page_rules' ));
	$pageRules = array_map(function($rule){return trim(trim($rule),'/');}, $pageRules);
	if ($pageRuleMode == 'allow'){
		$canDisplay = in_array($currentURL, $pageRules);
		if (!$canDisplay){
			$wildcardCheck = str_replace($currentURL, '', $pageRules);
			$canDisplay = (in_array('*', $wildcardCheck) || in_array('/*', $wildcardCheck));
		}
	}else{
		$canDisplay = !in_array($currentURL, $pageRules);
		if ($canDisplay){
			$wildcardCheck = str_replace($currentURL, '', $pageRules);
			$canDisplay = (!in_array('*', $wildcardCheck) && !in_array('/*', $wildcardCheck));
		}
	}
	
	if (!$canDisplay){
		return;
	}
	

	$lib_url = "https://api.thiscallbutton.com/callwidget-2.1.3.min.js?btnkey=" . $key;
	wp_enqueue_script('thiscallbutton-lib-js', $lib_url, array(), false, true);
	$css_url = "https://api.thiscallbutton.com/thiscallbutton.css?v=2.13";
	wp_enqueue_style('thiscallbutton-lib-css', $css_url);
}

