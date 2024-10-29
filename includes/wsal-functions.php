<?php
/**
 * Add our neccesary hooks and filters.
 *
 * @package wsal
 * @subpackage wsal-memberpress
 */

use WSAL\Helpers\Classes_Helper;

add_filter( 'wsal_event_objects', 'wsal_memberpress_add_custom_event_objects', 10, 2 );
add_filter( 'wsal_ignored_custom_post_types', 'wsal_memberpress_add_custom_ignored_cpt' );
add_filter( 'wsal_truncate_alert_value', 'data_truncate', 10, 4 );
add_filter( 'wsal_event_type_data', 'wsal_memberpress_add_custom_event_type', 10, 2 );

/**
 * Added our event types to the available list.
 *
 * @param  array $types - Current event types.
 *
 * @return array $types - Altered list.
 */
function wsal_memberpress_add_custom_event_type( $types ) {
	$new_types = array(
		'expired'  => esc_html__( 'Expired', 'wsal-memberpress' ),
	);

	// combine the two arrays.
	$types = array_merge( $types, $new_types );

	return $types;
}


 /**
  * Register a custom event object within WSAL.
  *
  * @param array $objects array of objects current registered within WSAL.
  */
function wsal_memberpress_add_custom_event_objects( $objects ) {
	$new_objects = array(
		'memberpress_memberships'   => esc_html__( 'Memberships in MemberPress', 'wsal-memberpress' ),
		'memberpress_groups'        => esc_html__( 'Groups in MemberPress', 'wsal-memberpress' ),
		'memberpress_rules'         => esc_html__( 'Rules in MemberPress', 'wsal-memberpress' ),
		'memberpress_settings'      => esc_html__( 'Settings in MemberPress', 'wsal-memberpress' ),
		'memberpress_roles'         => esc_html__( 'Roles in MemberPress', 'wsal-memberpress' ),
		'memberpress_subscriptions' => esc_html__( 'Subscriptions in MemberPress', 'wsal-memberpress' ),
		'memberpress_transactions'  => esc_html__( 'Transactions in MemberPress', 'wsal-memberpress' ),
		'memberpress_members'  	    => esc_html__( 'Members in MemberPress', 'wsal-memberpress' ),
		
	);

	// combine the two arrays.
	$objects = array_merge( $objects, $new_objects );

	return $objects;
}

/**
 * Adds new ignored CPT for our plugin
 *
 * @method wsal_memberpress_extension_add_custom_event_object_text
 * @since  1.0.0
 * @param  array $post_types An array of default post_types.
 * @return array
 */
function wsal_memberpress_add_custom_ignored_cpt( $post_types ) {
	$new_post_types = array(
		'memberpressproduct',
		'memberpressgroup',
		'memberpressrule',
	);

	// combine the two arrays.
	$post_types = array_merge( $post_types, $new_post_types );
	return $post_types;
}

/**
 * Ensure values are not overly lengthy.
 *
 * @param string  $value
 * @param string  $expression
 * @param integer $length
 * @param string  $ellipses_sequence
 * @return string
 */
function data_truncate( $value, $expression, $length = 100, $ellipses_sequence = '...' ) {
	$length = 200;

	switch ( $expression ) {
		case '%previous_value%':
		case '%value%':
			$value = mb_strlen( $value ) > $length ? ( mb_substr( $value, 0, $length ) . $ellipses_sequence ) : $value;
			break;
		default:
			break;
	}

	return $value;
}

add_action(
	'wsal_sensors_manager_add',
	/**
	* Adds sensors classes to the Class Helper
	*
	* @return void
	*
	* @since latest
	*/
	function () {
		require_once __DIR__ . '/../wp-security-audit-log/sensors/class-memberpress-sensor.php';

		Classes_Helper::add_to_class_map(
			array(
				'WSAL\\Plugin_Sensors\\MemberPress_Sensor' => __DIR__ . '/../wp-security-audit-log/sensors/class-memberpress-sensor.php',
			)
		);
	}
);

