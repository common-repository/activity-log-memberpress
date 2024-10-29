<?php
/**
 * Our list of events.
 *
 * @package wsal
 */

// phpcs:disable WordPress.WP.I18n.UnorderedPlaceholdersText 
// phpcs:disable WordPress.WP.I18n.MissingTranslatorsComment

$custom_alerts = array(
	esc_html__( 'Memberpress', 'wsal-memberpress' ) => array(
		esc_html__( 'Memberships', 'wsal-memberpress' )      => array(
			array(
				6200,
				WSAL_HIGH,
				esc_html__( 'A membership was created, deleted or restored.', 'wsal-memberpress' ),
				esc_html__( 'Membership name %name%.', 'wsal-memberpress' ),
				array(
					esc_html__( 'Membership ID', 'wsal-memberpress' ) => '%ID%',
				),				
				array(
					esc_html__( 'View Membership', 'wsal-memberpress' ) => '%ViewLink%',
				),
				'memberpress_memberships',
				'created',
			),
			array(
				6201,
				WSAL_HIGH,
				esc_html__( 'A membership was modified.', 'wsal-memberpress' ),
				esc_html__( 'Membership Option was modified in %name%', 'wsal-memberpress' ),
				array(
					esc_html__( 'Membership ID', 'wsal-memberpress' ) => '%ID%',
					esc_html__( 'Option name', 'wsal-memberpress' ) => '%option_name%',
					esc_html__( 'Previous value', 'wsal-memberpress' ) => '%previous_value%',
					esc_html__( 'New value', 'wsal-memberpress' ) => '%value%',
				),
				array(
					esc_html__( 'View Membership', 'wsal-memberpress' ) => '%ViewLink%',
				),
				'memberpress_memberships',
				'modified',
			),
			array(
				6202,
				WSAL_HIGH,
				esc_html__( 'A membership was permanently deleted.', 'wsal-memberpress' ),
				esc_html__( 'Permanently deleted the Membership %name%', 'wsal-memberpress' ),
				array(
					esc_html__( 'Membership ID', 'wsal-memberpress' ) => '%ID%',
				),
				array(),
				'memberpress_memberships',
				'deleted',
			),
			array(
				6203,
				WSAL_HIGH,
				esc_html__( 'A group was created, deleted or restored.', 'wsal-memberpress' ),
				esc_html__( 'Group name %name%', 'wsal-memberpress' ),
				array(
					esc_html__( 'Group ID', 'wsal-memberpress' ) => '%ID%',
				),
				array(
					esc_html__( 'View Group', 'wsal-memberpress' ) => '%ViewLink%',
				),
				'memberpress_groups',
				'created',
			),
			array(
				6204,
				WSAL_HIGH,
				esc_html__( 'A group option was modified.', 'wsal-memberpress' ),
				esc_html__( 'Group Option was modified in %name%', 'wsal-memberpress' ),
				array(
					esc_html__( 'Group ID', 'wsal-memberpress' ) => '%ID%',
					esc_html__( 'Option name', 'wsal-memberpress' ) => '%option_name%',
					esc_html__( 'Previous value', 'wsal-memberpress' ) => '%previous_value%',
					esc_html__( 'New value', 'wsal-memberpress' ) => '%value%',
				),
				array(
					esc_html__( 'View  Group', 'wsal-memberpress' ) => '%ViewLink%',
				),
				'memberpress_groups',
				'modified',
			),
			array(
				6205,
				WSAL_HIGH,
				esc_html__( 'A group was permanently deleted.', 'wsal-memberpress' ),
				esc_html__( 'Permanently deleted the Group %name%', 'wsal-memberpress' ),
				array(
					esc_html__( 'Group ID', 'wsal-memberpress' ) => '%ID%',
				),
				array(),
				'memberpress_groups',
				'deleted',
			),
			array(
				6206,
				WSAL_HIGH,
				esc_html__( 'A rule was created, deleted or restored.', 'wsal-memberpress' ),
				esc_html__( 'Rule %name% .', 'wsal-memberpress' ),
				array(
					esc_html__( 'Rule ID', 'wsal-memberpress' ) => '%ID%',
				),
				array(
					esc_html__( 'View Rule', 'wsal-memberpress' ) => '%ViewLink%',
				),
				'memberpress_rules',
				'created',
			),
			array(
				6207,
				WSAL_HIGH,
				esc_html__( 'A rule option was modified.', 'wsal-memberpress' ),
				esc_html__( 'Rule Option was modified in %name%', 'wsal-memberpress' ),
				array(
					esc_html__( 'Rule ID', 'wsal-memberpress' ) => '%ID%',
					esc_html__( 'Option name', 'wsal-memberpress' ) => '%option_name%',
					esc_html__( 'Previous value', 'wsal-memberpress' ) => '%previous_value%',
					esc_html__( 'New value', 'wsal-memberpress' ) => '%value%',
				),
				array(
					esc_html__( 'View Rule', 'wsal-memberpress' ) => '%ViewLink%',
				),
				'memberpress_rules',
				'modified',
			),
			array(
				6208,
				WSAL_HIGH,
				esc_html__( 'A rule was permanently deleted.', 'wsal-memberpress' ),
				esc_html__( 'Permanently deleted the Ruke %name%', 'wsal-memberpress' ),
				array(
					esc_html__( 'Rule ID', 'wsal-memberpress' ) => '%ID%',
				),
				array(),
				'memberpress_rules',
				'deleted',
			),
			array(
				6210,
				WSAL_HIGH,
				esc_html__( 'A setting was modified.', 'wsal-memberpress' ),
				esc_html__( 'Setting %setting_name% was modified.', 'wsal-memberpress' ),
				array(
					esc_html__( 'Previous value', 'wsal-memberpress' ) => '%previous_value%',
					esc_html__( 'New value', 'wsal-memberpress' ) => '%value%',
				),
				array(),
				'memberpress_settings',
				'modified',
			),

			array(
				6211,
				WSAL_HIGH,
				esc_html__( 'A role was created, modified or deleted.', 'wsal-memberpress' ),
				esc_html__( 'Role name: %name%', 'wsal-memberpress' ),
				array(
					esc_html__( 'Role ID', 'wsal-memberpress' ) => '%ID%',
				),
				array(
					esc_html__( 'View role', 'wsal-memberpress' ) => '%RoleLink%',
				),
				'memberpress_roles',
				'modified',
			),
			array(
				6212,
				WSAL_HIGH,
				esc_html__( 'A role was modified.', 'wsal-memberpress' ),
				esc_html__( 'Role name: %name%', 'wsal-memberpress' ),
				array(
					esc_html__( 'Role ID', 'wsal-memberpress' ) => '%ID%',
				),
				array(
					esc_html__( 'View role', 'wsal-memberpress' ) => '%RoleLink%',
				),
				'memberpress_roles',
				'created',
			),

			array(
				6250,
				WSAL_HIGH,
				esc_html__( 'A subscription was created, cancelled or deleted.', 'wsal-memberpress' ),
				esc_html__( 'Subscription number: %name%', 'wsal-memberpress' ),
				array(
					esc_html__( 'Subscription ID', 'wsal-memberpress' ) => '%ID%',
				),
				array(
					esc_html__( 'View Subscription', 'wsal-memberpress' ) => '%SubscriptionLink%',
				),
				'memberpress_subscriptions',
				'created',
			),
			array(
				6251,
				WSAL_HIGH,
				esc_html__( 'A subscription number was modified.', 'wsal-memberpress' ),
				esc_html__( 'Made changes to the subscription number %name%', 'wsal-memberpress' ),
				array(
					esc_html__( 'Subscription ID', 'wsal-memberpress' ) => '%ID%',
				),
				array(
					esc_html__( 'View Subscription', 'wsal-memberpress' ) => '%SubscriptionLink%',
				),
				'memberpress_subscriptions',
				'modified',
			),
			array(
				6252,
				WSAL_HIGH,
				esc_html__( 'A subscription was expired.', 'wsal-memberpress' ),
				esc_html__( 'Subscription number: %name%', 'wsal-memberpress' ),
				array(
					esc_html__( 'Subscription ID', 'wsal-memberpress' ) => '%ID%',
				),
				array(
					esc_html__( 'View Subscription', 'wsal-memberpress' ) => '%SubscriptionLink%',
				),
				'memberpress_subscriptions',
				'expired',
			),
			array(
				6253,
				WSAL_HIGH,
				esc_html__( 'A transaction was created or deleted.', 'wsal-memberpress' ),
				esc_html__( 'Transaction number: %name%', 'wsal-memberpress' ),
				array(
					esc_html__( 'Transaction ID', 'wsal-memberpress' ) => '%ID%',
				),
				array(
					esc_html__( 'View Transaction', 'wsal-memberpress' ) => '%TransactionLink%',
				),
				'memberpress_transactions',
				'modified',
			),
			array(
				6254,
				WSAL_HIGH,
				esc_html__( 'A transaction was modified.', 'wsal-memberpress' ),
				esc_html__( 'Transaction number: %name%', 'wsal-memberpress' ),
				array(
					esc_html__( 'Transaction ID', 'wsal-memberpress' ) => '%ID%',
					esc_html__( 'Previous value', 'wsal-memberpress' ) => '%previous_value%',
					esc_html__( 'New value', 'wsal-memberpress' ) => '%value%',
				),
				array(
					esc_html__( 'View Transaction', 'wsal-memberpress' ) => '%TransactionLink%',
				),
				'memberpress_transactions',
				'created',
			),
			array(
				6255,
				WSAL_HIGH,
				esc_html__( 'A member transaction was created or deleted.', 'wsal-memberpress' ),
				esc_html__( 'Made changes to the transaction number %name%', 'wsal-memberpress' ),
				array(
					esc_html__( 'Transaction ID', 'wsal-memberpress' ) => '%ID%',
					esc_html__( 'Membership', 'wsal-memberpress' ) => '%membershipname%',
				),
				array(
					esc_html__( 'View Members profile page', 'wsal-memberpress' ) => '%MemberLink%',
				),
				'memberpress_members',
				'created',
			),
		),
	),
);
