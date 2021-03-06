<?php

/**
 * @package anno
 * This file is part of the Annotum theme for WordPress
 * Built on the Carrington theme framework <http://carringtontheme.com>
 *
 * Copyright 2008-2015 Crowd Favorite, Ltd. All rights reserved. <http://crowdfavorite.com>
 * Released under the GPL license
 * http://www.opensource.org/licenses/gpl-license.php
 */

/**
 * Array of user meta keys and their labels
 */
global $anno_user_meta;
$anno_user_meta = apply_filters('anno_user_meta', array(
	'_anno_prefix' => _x('Name Prefix', 'form label', 'anno'),
	'_anno_suffix' => _x('Name Suffix', 'form label', 'anno'),
	'_anno_institution' => _x('Institution', 'form label', 'anno'),
	'_anno_department' => _x('Department', 'form label', 'anno'),
	'_anno_city' => _x('City', 'form label', 'anno'),
	'_anno_state' => _x('State', 'form label', 'anno'),
	'_anno_country' => _x('Country', 'form label', 'anno'),
));

function anno_sanitize_user_meta_keys($meta_array) {
	$sanitize_keys_meta = array();
	foreach ($meta_array as $key => $value) {
		$sanitized_key = null;
		if (strpos($key, '_anno_') === 0) {
			$sanitized_key = substr($key, 6);
		}

		if ($sanitized_key) {
			$sanitize_keys_meta[$sanitized_key] = $value;
		}
		else {
			$sanitize_keys_meta[$key] = $value;
		}
	}

	return $sanitize_keys_meta;
}

/**
 * User profile markup for Annotum specific items
 */
function anno_profile_fields($user) {
	global $anno_user_meta;
	if (is_array($anno_user_meta) && !empty($anno_user_meta)) {
?>
		<?php echo apply_filters('anno_profile_fields_title', __('<h3>Miscellaneous</h3>', 'anno')); ?>
		<table class="form-table">
			<tbody>

<?php
		foreach ($anno_user_meta as $key => $label) {
			$meta_val = get_user_meta($user->ID, $key, true);
?>
				<tr>
					<th><label for="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></label></th>
					<td><input type="text" name="<?php echo esc_attr($key); ?>" class="regular-text" id="<?php echo esc_attr($key); ?>" value="<?php echo esc_attr($meta_val); ?>" />
				</tr>
<?php
		} // foreach
?>
			</tbody>
		</table>
		<input type="hidden" name="anno_profile_update" value="1">
<?php
	} // if
}
add_action('show_user_profile', 'anno_profile_fields');
add_action('edit_user_profile', 'anno_profile_fields');

/**
 * Update Annotum specific user meta
 */
function anno_profile_update($user_id) {
	global $anno_user_meta;
	// anno_profile_update to ensure that we're updating from the user profile edit page
	if (is_array($anno_user_meta) && !empty($anno_user_meta) && isset($_POST['anno_profile_update'])) {
		$required_fields = anno_user_required_fields();
		foreach ($anno_user_meta as $key => $label) {
			$value = isset($_POST[$key]) ? trim($_POST[$key]) : '';
			if (isset($required_fields[$key])) {
				if (!empty($value)) {
					update_user_meta($user_id, $key, $value);
				}
			}
			else {
				update_user_meta($user_id, $key, $value);
			}
		}
	}
}
add_action('personal_options_update', 'anno_profile_update');
add_action('edit_user_profile_update', 'anno_profile_update');

/**
 * List of required fields for new user signup
 * @note multisite currently not supported
 */
function anno_user_required_fields() {
	// key => label
	// keys should be user fields and/or the same fields from $anno_user_meta
	return apply_filters('anno_user_required_fields', array(
		'first_name' => __('First Name', 'anno'),
		'last_name' => __('Last Name', 'anno'),
	));
}

/**
 * Output additional fields on signup screen
 * @note multisite currently not supported
 */
function anno_register_form(){
	$required_fields = anno_user_required_fields();
	if (is_array($required_fields) && !empty($required_fields)) {
		foreach ($required_fields as $key => $label) {
			$input_val = isset($_POST[$key]) ? $_POST[$key] : '';
?>
			<p>
				<label for="<?php echo esc_attr($key) ?>"><?php echo esc_html($label); ?><br />
					<input id="<?php echo esc_attr($key) ?>" class="input" type="text" tabindex="20" size="25" value="<?php echo esc_attr($input_val); ?>" name="<?php echo esc_attr($key) ?>"/>
				</label>
			</p>
<?php
		}
	}
}
add_action('register_form','anno_register_form');

/**
 * Validate required fields on signup
 * @note multisite currently not supported
 */
function anno_user_register_validation($login, $email, $errors) {
	$required_fields = anno_user_required_fields();
	if (is_array($required_fields) && !empty($required_fields)) {
		foreach ($required_fields as $key => $label) {
			if (empty($_POST[$key])) {
				$errors->add('empty_'.$key, sprintf(__('<strong>ERROR</strong>: Please enter your %s.', 'anno'), $label));
			}
		}
	}
}
add_action('register_post','anno_user_register_validation', 10, 3);

/**
 * Insert additional fields into DB when a user signs up
 * @note multisite currently not supported
 */
function anno_user_register($user_id)  {
	$userdata = array();
	$update = false;

	foreach ($userdata as $key => $value) {
		if ($key == 'first_name' || $key == 'last_name') {
			$userdata[$key] = $_POST[$key];
			$update = true;
		}
		else {
			// @TODO WP may make the keys a little
			// more friendly to filter into the wp_insert_post process
			// @see _get_additional_user_keys and _wp_get_user_contactmethods
			$val = isset($_POST[$key]) ? $_POST[$key] : '';
			update_user_meta($user_id, $key, $val);
		}
	}

	if ($update) {
		$userdata['ID'] = $user_id;
		wp_update_user($userdata);
	}
}
add_action('user_register', 'anno_user_register');


/**
 * Enforce fields on profile update
 */
function anno_user_profile_update_validation($errors) {
	$required_fields = anno_user_required_fields();
	if (is_array($required_fields) && !empty($required_fields)) {
		foreach ($required_fields as $key => $label) {
			$val = isset($_POST[$key]) ? trim($_POST[$key]) : '';
			if (empty($val)) {
				$errors->add('empty_'.$key, sprintf(__('<strong>ERROR</strong>: Please enter %s.', 'anno'), $label));
			}
		}
	}
}
add_action('user_profile_update_errors', 'anno_user_profile_update_validation');

/**
 * Sanitize meta key to use around the site
 * @param string
 * @todo log filter
 */
function anno_sanitize_meta_key($meta_key) {
	$meta_key = str_replace('_anno_', '', $meta_key);
	return apply_filters('anno_sanitize_meta_key', $meta_key);
}
?>
