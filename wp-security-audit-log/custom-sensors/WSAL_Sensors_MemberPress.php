<?php // phpcs:disable WordPress.Files.FileName.NotHyphenatedLowercase

/**
 * Custom Sensors for memberpress plugin.
 *
 * Class file for alert manager.
 *
 * @since   1.0.0
 * @package wsal
 * @subpackage wsal-memberpress
 */
class WSAL_Sensors_MemberPress extends WSAL_AbstractSensor {

	/**
	 * Array of slugs we wish to monitor
	 *
	 * @var array
	 */
	private $wanted_cpts = array(
		'memberpressproduct',
		'memberpressgroup',
		'memberpressrule',
	);

	/**
	 * Array of event keys used by the memberpress loggging system.
	 *
	 * @var array
	 */
	private $mepr_events = array(
		'login',
		'member-added',
		'member-deleted',
		'subscription-expired',
		'subscription-created',
		'member-signup-completed',
		'member-account-updated',
		'transaction-expired',
		'recurring-transaction-expired',
		'member-signup-completed',
		'transaction-completed',
		'recurring-transaction-completed',
		'renewal-transaction-completed',
		'non-recurring-transaction-completed',
		'subscription-paused',
		'subscription-resumed',
		'subscription-stopped',
		'subscription-upgraded',
		'subscription-changed',
		'subscription-upgraded-to-one-time',
		'subscription-upgraded-to-recurring',
		'subscription-downgraded',
		'subscription-changed',
		'subscription-downgraded-to-one-time',
		'subscription-downgraded-to-recurring',
		'transaction-refunded',
		'recurring-transaction-refunded',
		'transaction-failed',
		'recurring-transaction-failed',
		'_mepr_auto_gen_title',
	);

	/**
	 * Array of keys which we know to treat as bools.
	 *
	 * @var array
	 */
	private $bool_metas = array(
		'_mepr_thank_you_page_enabled',
		'_mepr_customize_payment_methods',
		'_mepr_customize_profile_fields',
		'_mepr_allow_simultaneous_subscriptions',
		'_mepr_product_is_highlighted',
		'_mepr_custom_login_urls_enabled',
		'_mepruserroles_enabled',
		'_mepr_product_trial',
		'_mepr_product_trial_once',
		'_mepr_product_limit_cycles',
		'_mepr_group_is_upgrade_path',
		'_mepr_group_upgrade_path_reset_period',
		'_mepr_use_custom_template',
		'_mepr_rules_drip_enabled',
		'_mepr_rules_expires_enabled',
		'_mepr_group_pricing_page_disabled',
		'redirect_on_unauthorized',
		'redirect_non_singular',
		'unauth_show_excerpts',
		'unauth_show_login',
		'disable_wp_admin_bar',
		'lock_wp_admin',
		'disable_wp_registration_form',
		'coupon_field_enabled',
		'username_is_email',
		'pro_rated_upgrades',
		'disable_grace_init_days',
		'disable_checkout_password_fields',
		'enable_spc',
		'enable_spc_invoice',
		'require_tos',
		'require_privacy_policy',
		'force_login_page_url',
		'currency_symbol_after',
		'show_fname_lname',
		'require_fname_lname',
		'show_address_fields',
		'require_address_fields',
		'include_email_privacy_link',
		'opt_in_checked_by_default',
		'authorize_seo_views',
		'seo_unauthorized_noindex',
		'anti_card_testing_enabled',
		'_mepr_tax_exempt',
		'_mepr_disable_address_fields',
		'_mepr_auto_gen_title',
	);

	/**
	 * Array of items we know to treat as array.
	 *
	 * @var array
	 */
	private $array_metas = array(
		'_mepruserroles_roles',
	);

	private $temp_changes_access_conditions = array(
		'old_conditions' => array(),
		'new_conditions' => array(),
	);

	/**
	 * Array of keys which require special treatment.
	 *
	 * @var array
	 */
	private $special_cases = array(
		'_mepr_product_who_can_purchase',
		'_mepr_product_pricing_benefits',
		'_mepr_emails',
		'_mepr_custom_login_urls',
		'_mepr_group_page_style_options',
		'address_fields',
		'integrations',
		'anti_card_testing_blocked',
		'_mepr_custom_profile_fields',
	);

	/**
	 * Here you can code your own custom sensors for triggering your custom events.
	 */
	public function HookEvents() {
		add_action( 'pre_post_update', array( $this, 'get_before_post_edit_data' ), 10, 2 );
		add_action( 'save_post', array( $this, 'event_mepr_post_saved' ), 10, 3 );
		add_action( 'delete_post', array( $this, 'event_mepr_post_deleted' ), 10, 1 );
		add_action( 'wp_trash_post', array( $this, 'event_mepr_post_trashed' ), 10, 1 );
		add_action( 'untrash_post', array( $this, 'event_mepr_post_untrashed' ), 10, 1 );
		add_action( 'mepr-event-create', array( $this, 'event_mepr_logger_triggered' ), 10, 1 );
		add_action( 'updated_option', array( $this, 'event_mepr_settings_updated' ), 10, 3 );
		add_action( 'members_role_added', array( $this, 'event_members_role_added' ), 10, 1 );
		add_action( 'members_role_updated', array( $this, 'event_members_role_updated' ), 10, 1 );
		add_action( 'mepr_subscription_pre_delete', array( $this, 'event_event_deleted' ), 10, 1 );
		add_action( 'mepr-limit-payment-cycles-reached', array( $this, 'event_subscription_expired' ), 10, 1 );
		add_action( 'mepr_subscription_saved', array( $this, 'event_event_store' ), 10, 1 );
		add_filter( 'mepr_update_subscription', array( $this, 'event_subscription_saved' ), 10, 3 );
		add_action( 'mepr-txn-store', array( $this, 'event_transaction_store' ), 10, 2 );
		add_action( 'mepr_pre_delete_transaction', array( $this, 'event_transaction_deleted' ), 10, 1 );
	}

	/**
	 * Trigger event when a transaction was stored.
	 *
	 * @param object $txn
	 * @param object $old_txn
	 * @return void
	 */
	public function event_transaction_store( $txn, $old_txn ) {
		$details = $txn->rec;
		$old_details = $old_txn->rec;

		$editor_link = esc_url(
			add_query_arg(
				array(
					'action' => 'edit',
					'id' => $details->id,
				),
				admin_url( 'admin.php?page=memberpress-trans&action=edit' )
			)
		);

		if ( $old_details->id > 0 ) {
			$details_arr     = (array) $details;
			$old_details_arr = (array) $old_details;
			foreach ( $details_arr as $item => $detail ) {
				if ( trim( $old_details_arr[$item] ) != trim( $details_arr[$item] ) ) {
					$variables = array(
						'EventType'       => 'modified',
						'name'            => esc_html( $details->trans_num ),
						'ID'              => esc_html( $details->id ),
						'previous_value'  => esc_html( $old_details_arr[$item] ),
						'value'           => esc_html( $details_arr[$item] ),
						'TransactionLink' => $editor_link,
					);
			
					$this->plugin->alerts->trigger_event( 6254, $variables );
				}
			}

		} else {			
			$variables = array(
				'EventType'       => 'created',
				'name'            => esc_html( $details->trans_num ),
				'ID'              => esc_html( $details->id ),
				'TransactionLink' => $editor_link,
			);
	
			$this->plugin->alerts->trigger_event( 6253, $variables );
		}
	}

	/**
	 * Trigger event when a transaction was deleted.
	 *
	 * @param object $txn
	 * @return void
	 */
	public function event_transaction_deleted( $txn ) {
		$details = $txn->rec;

		$editor_link = esc_url(
			add_query_arg(
				array(
					'action' => 'edit',
					'id' => $details->id,
				),
				admin_url( 'admin.php?page=memberpress-trans&action=edit' )
			)
		);

		$variables = array(
			'EventType'       => 'deleted',
			'name'            => esc_html( $details->trans_num ),
			'ID'              => esc_html( $details->id ),
			'TransactionLink' => $editor_link,
		);

		$this->plugin->alerts->trigger_event( 6253, $variables );
	}

	/**
	 * Trigger event when a subscription was stored.
	 *
	 * @param object $txn
	 * @param object $old_txn
	 * @return void
	 */
	public function event_event_store( $event ) {
		$details = $event->rec;
		$old_data = MeprSubscription::get_one_by_subscr_id( $details->subscr_id );

		$editor_link = esc_url(
			add_query_arg(
				array(
					'action' => 'edit',
					'id' => $details->id,
				),
				admin_url( 'admin.php?page=memberpress-subscriptions' )
			)
		);

		$variables = array(
			'EventType'        => 'created',
			'name'             => esc_html( $details->subscr_id ),
			'ID'               => esc_html( $details->id ),
			'SubscriptionLink' => $editor_link,
		);

		if ( ! self::was_triggered_recently( 6251 ) ) {
			$this->plugin->alerts->trigger_event( 6250, $variables );
		}
	}

	/**
	 * Trigger event when a transaction was stored.
	 *
	 * @param object $txn
	 * @param object $old_txn
	 * @return void
	 */
	public function event_event_deleted( $event_id ) {
		$details = MeprSubscription::get_one( $event_id );
		$editor_link = esc_url(
			add_query_arg(
				array(
					'action' => 'edit',
					'id' => $details->id,
				),
				admin_url( 'admin.php?page=memberpress-subscriptions' )
			)
		);

		$variables = array(
			'EventType'        => 'deleted',
			'name'             => esc_html( $details->subscr_id ),
			'ID'               => esc_html( $details->id ),
			'SubscriptionLink' => $editor_link,
		);

		$this->plugin->alerts->trigger_event( 6250, $variables );
	}

	/**
	 * Trigger event when a subscription was updated.
	 *
	 * @param object $txn
	 * @param object $old_txn
	 * @return void
	 */
	public function event_subscription_saved( $event, $args, $user_id ) {
		$event   = $event;

		$editor_link = esc_url(
			add_query_arg(
				array(
					'action' => 'edit',
					'id' => $args['id'],
				),
				admin_url( 'admin.php?page=memberpress-subscriptions' )
			)
		);

		$old_data = MeprSubscription::get_one_by_subscr_id( $args['subscr_id'] );

		$variables = array(
			'name'             => esc_html( $args['subscr_id'] ),
			'ID'               => esc_html( $args['id'] ),
			'previous_value'   => esc_html( $args['subscr_id'] ),
			'value'            => esc_html( $args['subscr_id'] ),
			'SubscriptionLink' => $editor_link,
		);

		$this->plugin->alerts->trigger_event( 6251, $variables );

		return $event;
	}

	/**
	 * Trigger event when a subscription was expired.
	 *
	 * @param object $txn
	 * @param object $old_txn
	 * @return void
	 */
	public function event_subscription_expired( $subscription ) {
		$details = $subscription;
		$editor_link = esc_url(
			add_query_arg(
				array(
					'action' => 'edit',
					'id' => $details->id,
				),
				admin_url( 'admin.php?page=memberpress-subscriptions' )
			)
		);

		$variables = array(
			'EventType'        => 'expired',
			'name'             => esc_html( $details->subscr_id ),
			'ID'               => esc_html( $details->id ),
			'SubscriptionLink' => $editor_link,
		);

		$this->plugin->alerts->trigger_event( 6250, $variables );
	}

	/**
	 * Trigger event when a role is created.
	 *
	 * @param  string $role
	 * @return void
	 */
	public function event_members_role_added( $role ) {
		$editor_link = esc_url(
			add_query_arg(
				array(
					'action' => 'edit',
					'role'   => $role,
				),
				admin_url( 'admin.php?page=roles' )
			)
		);

		$variables = array(
			'EventType' => 'created',
			'name'      => esc_html( ucfirst( str_replace( '_', ' ', $role ) ) ),
			'ID'        => esc_html( $role ),
			'RoleLink'  => $editor_link,
		);

		$this->plugin->alerts->trigger_event( 6211, $variables );
	}

	/**
	 * Trigger event when a role is modified.
	 *
	 * @param  string $role
	 * @return void
	 */
	public function event_members_role_updated( $role ) {
		$editor_link = esc_url(
			add_query_arg(
				array(
					'action' => 'edit',
					'role'   => $role,
				),
				admin_url( 'admin.php?page=roles' )
			)
		);

		$variables = array(
			'EventType' => 'modified',
			'name'      => esc_html( ucfirst( str_replace( '_', ' ', $role ) ) ),
			'ID'        => esc_html( $role ),
			'RoleLink'  => $editor_link,
		);

		$this->plugin->alerts->trigger_event( 6212, $variables );
	}

	/**
	 * Simple getter to hold our wanted keys as well as any labels needed.
	 *
	 * @return array $membership_meta_titles - The above data.
	 */
	public function get_item_titles() {
		$membership_meta_titles = array(
			'_mepr_product_price'                      => esc_html__( 'Membership Terms: Price', 'wsal-memberpress' ),
			'_mepr_product_period'                     => esc_html__( 'Period', 'wsal-memberpress' ),
			'_mepr_product_period_type'                => esc_html__( 'Membership Terms: Billing interval', 'wsal-memberpress' ),
			'_mepr_product_signup_button_text'         => esc_html__( 'Registration Button Text', 'wsal-memberpress' ),
			'_mepr_product_limit_cycles'               => esc_html__( 'Membership Terms: Limit payment cycles', 'wsal-memberpress' ),
			'_mepr_product_limit_cycles_num'           => esc_html__( 'Membership Terms: Max number of payment Cycle', 'wsal-memberpress' ),
			'_mepr_product_limit_cycles_action'        => esc_html__( 'Membership Terms: Access After Last Cycle', 'wsal-memberpress' ),
			'_mepr_product_limit_cycles_expires_after' => esc_html__( 'Cyles Limit Expires After', 'wsal-memberpress' ),
			'_mepr_product_limit_cycles_expires_type'  => esc_html__( 'Cyles Limit Expires Denominator', 'wsal-memberpress' ),
			'_mepr_product_trial'                      => esc_html__( 'Membership Terms: Trial', 'wsal-memberpress' ),
			'_mepr_product_trial_days'                 => esc_html__( 'Membership Terms: Trial length', 'wsal-memberpress' ),
			'_mepr_product_trial_amount'               => esc_html__( 'Membership Terms: Trial price', 'wsal-memberpress' ),
			'_mepr_product_trial_once'                 => esc_html__( 'Membership Terms: Allow Only One Trial', 'wsal-memberpress' ),
			'_mepr_group_id'                           => esc_html__( 'Group', 'wsal-memberpress' ),
			'_mepr_group_order'                        => esc_html__( 'Group Order', 'wsal-memberpress' ),
			'_mepr_product_is_highlighted'             => esc_html__( 'Is Highlighted', 'wsal-memberpress' ),
			'_mepr_product_pricing_title'              => esc_html__( 'Pricing Title', 'wsal-memberpress' ),
			'_mepr_product_pricing_display'            => esc_html__( 'Pricing Display', 'wsal-memberpress' ),
			'_mepr_product_custom_price'               => esc_html__( 'Custom Price', 'wsal-memberpress' ),
			'_mepr_product_pricing_heading_text'       => esc_html__( 'Pricing heading text', 'wsal-memberpress' ),
			'_mepr_product_pricing_footer_text'        => esc_html__( 'Pricing footer text', 'wsal-memberpress' ),
			'_mepr_product_pricing_button_text'        => esc_html__( 'Pricing button text', 'wsal-memberpress' ),
			'_mepr_product_pricing_button_position'    => esc_html__( 'Pricing button position', 'wsal-memberpress' ),
			'_mepr_product_pricing_benefits'           => esc_html__( 'Pricing benefits', 'wsal-memberpress' ),
			'_mepr_register_price_action'              => esc_html__( 'Membership Pricing Terms', 'wsal-memberpress' ),
			'_mepr_register_price'                     => esc_html__( 'Custom Registration Pricing Term', 'wsal-memberpress' ),
			'_mepr_thank_you_page_enabled'             => esc_html__( 'Enable custom thank you page message', 'wsal-memberpress' ),
			'_mepr_thank_you_page_type'                => esc_html__( 'Thank you page type', 'wsal-memberpress' ),
			'_mepr_product_thank_you_message'          => esc_html__( 'Custom Thank you message', 'wsal-memberpress' ),
			'_mepr_product_thank_you_page_id'          => esc_html__( 'Custom Thank you page ID', 'wsal-memberpress' ),
			'_mepr_custom_login_urls_enabled'          => esc_html__( 'Custom Login Redirect Enabled', 'wsal-memberpress' ),
			'_mepr_custom_login_urls_default'          => esc_html__( 'Custom Login Redirect URLs', 'wsal-memberpress' ),
			'_mepr_custom_login_urls'                  => esc_html__( 'Custom Login URLs', 'wsal-memberpress' ),
			'_mepr_expire_type'                        => esc_html__( 'Membership Terms: Access Type', 'wsal-memberpress' ),
			'_mepr_expire_after'                       => esc_html__( 'Expires After', 'wsal-memberpress' ),
			'_mepr_expire_unit'                        => esc_html__( 'Expiry Unit', 'wsal-memberpress' ),
			'_mepr_expire_fixed'                       => esc_html__( 'Exiry Fixes', 'wsal-memberpress' ),
			'_mepr_tax_exempt'                         => esc_html__( 'This Membership is Tax Exempt', 'wsal-memberpress' ),
			'_mepr_tax_class'                          => esc_html__( 'Tax Class', 'wsal-memberpress' ),
			'_mepr_allow_renewal'                      => esc_html__( 'Allow Renewals', 'wsal-memberpress' ),
			'_mepr_access_url'                         => esc_html__( 'Membership Access URL', 'wsal-memberpress' ),
			'_mepr_emails'                             => esc_html__( 'Send Membership-Specific Welcome Email to User', 'wsal-memberpress' ),
			'_mepr_disable_address_fields'             => esc_html__( 'Disable Address Fields', 'wsal-memberpress' ),
			'_mepr_allow_simultaneous_subscriptions'   => esc_html__( 'Allow users to create multiple, active subscriptions to this membership', 'wsal-memberpress' ),
			'_mepr_use_custom_template'                => esc_html__( 'Use Custom Template', 'wsal-memberpress' ),
			'_mepr_custom_template'                    => esc_html__( 'Custom Template', 'wsal-memberpress' ),
			'_mepr_customize_payment_methods'          => esc_html__( 'Customize Payment Methods', 'wsal-memberpress' ),
			'_mepr_customize_profile_fields'           => esc_html__( 'Customize User Information Fields', 'wsal-memberpress' ),
			'_mepr_cannot_purchase_message'            => esc_html__( 'Cannot Purchase Message', 'wsal-memberpress' ),
			'_mepr_plan_code'                          => esc_html__( 'Plan Code', 'wsal-memberpress' ),
			'_mepruserroles_enabled'                   => esc_html__( 'Use Roles for this Membership', 'wsal-memberpress' ),
			'_mepruserroles_roles'                     => esc_html__( 'Membership Roles', 'wsal-memberpress' ),
			'_mepr_product_who_can_purchase'           => esc_html__( 'Who can purchase this Membership', 'wsal-memberpress' ),
			'_mepr_custom_profile_fields'              => esc_html__( 'Custom Profile Fields', 'wsal-memberpress' ),
			// Groups.
			'_mepr_group_pricing_page_disabled'        => esc_html__( 'Enable Pricing Page', 'wsal-memberpress' ),
			'_mepr_group_disable_change_plan_popup'    => esc_html__( 'Disable Change Plan Pop-Up', 'wsal-memberpress' ),
			'_mepr_group_is_upgrade_path'              => esc_html__( 'Enable Group is Upgrade Path', 'wsal-memberpress' ),
			'_mepr_group_upgrade_path_reset_period'    => esc_html__( 'Enable Reset billing period', 'wsal-memberpress' ),
			'_mepr_group_theme'                        => esc_html__( 'Group Theme', 'wsal-memberpress' ),
			'_mepr_fallback_membership'                => esc_html__( 'Group Memberships', 'wsal-memberpress' ),
			'_mepr_page_button_class'                  => esc_html__( 'Custom Button CSS classe', 'wsal-memberpress' ),
			'_mepr_page_button_highlighted_class'      => esc_html__( 'Custom Highlighted Button CSS classe', 'wsal-memberpress' ),
			'_mepr_page_button_disabled_class'         => esc_html__( 'Custom Disabled Button CSS classe', 'wsal-memberpress' ),
			'_mepr_group_page_style_options'           => esc_html__( 'Group Page Style Options', 'wsal-memberpress' ),
			'_mepr-alternate-group-url'                => esc_html__( 'Alernative Group URL', 'wsal-memberpress' ),
			'_mepr_use_custom_template'                => esc_html__( 'Enable Use Custom Page Template', 'wsal-memberpress' ),
			'_mepr_custom_template'                    => esc_html__( 'Page Template', 'wsal-memberpress' ),
			'_mepr_unauthorized_message_type'          => esc_html__( 'Unauthorised Access Message Type', 'wsal-memberpress' ),
			'_mepr_unauthorized_message'               => esc_html__( 'Unauthorised Access Message', 'wsal-memberpress' ),
			'_mepr_unauth_login'                       => esc_html__( 'Unauthorised Access Show Login', 'wsal-memberpress' ),
			'_mepr_unauth_excerpt_type'                => esc_html__( 'Unauthorised Access Excerpt Type', 'wsal-memberpress' ),
			'_mepr_unauth_excerpt_size'                => esc_html__( 'Unauthorised Access Excerpt Length', 'wsal-memberpress' ),
			// Rules.
			'_mepr_rules_type'                         => esc_html__( 'Rule Type', 'wsal-memberpress' ),
			'_mepr_rules_content'                      => esc_html__( 'Rule Content', 'wsal-memberpress' ),
			'_is_mepr_rules_content_regexp'            => esc_html__( 'Rule Rexexp', 'wsal-memberpress' ),
			'_mepr_rules_drip_enabled'                 => esc_html__( 'Enable Drip', 'wsal-memberpress' ),
			'_mepr_rules_drip_amount'                  => esc_html__( 'Drip Amount', 'wsal-memberpress' ),
			'_mepr_rules_drip_unit'                    => esc_html__( 'Drip Unit', 'wsal-memberpress' ),
			'_mepr_rules_drip_after_fixed'             => esc_html__( 'Drip After Fixed Date', 'wsal-memberpress' ),
			'_mepr_rules_drip_after'                   => esc_html__( 'Drip After Type', 'wsal-memberpress' ),
			'_mepr_rules_expires_enabled'              => esc_html__( 'Enable Expiry', 'wsal-memberpress' ),
			'_mepr_rules_expires_amount'               => esc_html__( 'Rule Expires Amount', 'wsal-memberpress' ),
			'_mepr_rules_expires_unit'                 => esc_html__( 'Rule Exires Unit', 'wsal-memberpress' ),
			'_mepr_rules_expires_after'                => esc_html__( 'Rule Expires After', 'wsal-memberpress' ),
			'_mepr_rules_expires_after_fixed'          => esc_html__( 'Expires After Fixed Date', 'wsal-memberpress' ),
			'_mepr_rules_unauth_excerpt_type'          => esc_html__( 'Unauthorised Access Excerpt Type', 'wsal-memberpress' ),
			'_mepr_rules_unauth_excerpt_size'          => esc_html__( 'Unauthorised Access Excerpt Length', 'wsal-memberpress' ),
			'_mepr_rules_unauth_message_type'          => esc_html__( 'Unauthorised Access Message Type', 'wsal-memberpress' ),
			'_mepr_rules_unath_message'                => esc_html__( 'Unauthorised Access Message', 'wsal-memberpress' ),
			'_mepr_rules_unath_login'                  => esc_html__( 'Unauthorised Access Show Login', 'wsal-memberpress' ),
			'_mepr_auto_gen_title'                     => esc_html__( 'Using Autogenerated Title', 'wsal-memberpress' ),
		);
		return $membership_meta_titles;
	}

	/**
	 * Simple getter for gettings keys as well as there respective labels.
	 *
	 * @return array $mepr_option_key - The above data.
	 */
	public function get_settings_titles() {
		$mepr_option_keys = array(
			'legacy_integrations'               => esc_html__( 'Legacy integrations', 'wsal-memberpress' ),
			'account_page_id'                   => esc_html__( 'Account Page', 'wsal-memberpress' ),
			'login_page_id'                     => esc_html__( 'Login Page', 'wsal-memberpress' ),
			'thankyou_page_id'                  => esc_html__( 'Thank You Page', 'wsal-memberpress' ),
			'force_login_page_url'              => esc_html__( 'Use MemberPress login page URL', 'wsal-memberpress' ),
			'login_redirect_url'                => esc_html__( 'Login Redirect URL', 'wsal-memberpress' ),
			'logout_redirect_url'               => esc_html__( 'Logout Recirect URL', 'wsal-memberpress' ),
			'disable_mod_rewrite'               => esc_html__( 'Disable Modrewrite', 'wsal-memberpress' ),
			'anti_card_testing_enabled'         => esc_html__( 'Enable Card Testing Protection', 'wsal-memberpress' ),
			'anti_card_testing_ip_method'       => esc_html__( 'Card Testing Protection Metod', 'wsal-memberpress' ),
			'anti_card_testing_blocked'         => esc_html__( 'Blocked IPs', 'wsal-memberpress' ),
			'account_css_width'                 => esc_html__( 'Account CSS width', 'wsal-memberpress' ),
			'custom_message'                    => esc_html__( 'Custom Message', 'wsal-memberpress' ),
			'setup_complete'                    => esc_html__( 'Has setup completed', 'wsal-memberpress' ),
			'activated_timestamp'               => esc_html__( 'Activation Timestamp', 'wsal-memberpress' ),
			'currency_code'                     => esc_html__( 'Currency Code', 'wsal-memberpress' ),
			'currency_symbol'                   => esc_html__( 'Currency Symbol', 'wsal-memberpress' ),
			'currency_symbol_after'             => esc_html__( 'Show Currency Symbol After', 'wsal-memberpress' ),
			'language_code'                     => esc_html__( 'Language Code', 'wsal-memberpress' ),
			'integrations'                      => esc_html__( 'Integrations/Payment Methods', 'wsal-memberpress' ),
			'lock_wp_admin'                     => esc_html__( 'Keep members out of the WordPress Dashboard', 'wsal-memberpress' ),
			'enforce_strong_password'           => esc_html__( 'Enforce strong password', 'wsal-memberpress' ),
			'disable_wp_registration_form'      => esc_html__( 'Disable the standard WordPress registration form', 'wsal-memberpress' ),
			'disable_wp_admin_bar'              => esc_html__( 'Disable the WordPress admin bar for members', 'wsal-memberpress' ),
			'pro_rated_upgrades'                => esc_html__( 'Pro-rate subscription prices when a member upgrades', 'wsal-memberpress' ),
			'disable_checkout_password_fields'  => esc_html__( 'Disable Password Fields on membership registration forms', 'wsal-memberpress' ),
			'enable_spc'                        => esc_html__( 'Enable Single Page Checkout', 'wsal-memberpress' ),
			'enable_spc_invoice'                => esc_html__( 'Enable Single Page Checkout Invoice', 'wsal-memberpress' ),
			'coupon_field_enabled'              => esc_html__( 'Enable Coupon Field on membership registration forms', 'wsal-memberpress' ),
			'require_tos'                       => esc_html__( 'Require Terms of Service on membership registration forms', 'wsal-memberpress' ),
			'require_privacy_policy'            => esc_html__( 'Require Privacy Policy acceptance on membership registration forms', 'wsal-memberpress' ),
			'tos_url'                           => esc_html__( 'Terms of Service URL', 'wsal-memberpress' ),
			'tos_title'                         => esc_html__( 'Terms of Service Title', 'wsal-memberpress' ),
			'privacy_policy_title'              => esc_html__( 'Privacy Policy Title', 'wsal-memberpress' ),
			'mail_send_from_name'               => esc_html__( 'Mail Send From Name', 'wsal-memberpress' ),
			'mail_send_from_email'              => esc_html__( 'Mail Send From Email Address', 'wsal-memberpress' ),
			'username_is_email'                 => esc_html__( 'Members must use their email address for their Username', 'wsal-memberpress' ),
			'show_fname_lname'                  => esc_html__( 'Extended User Information Fields: Show Name Fields', 'wsal-memberpress' ),
			'require_fname_lname'               => esc_html__( 'Extended User Information Fields: Require Name Fields', 'wsal-memberpress' ),
			'show_address_fields'               => esc_html__( 'Extended User Information Fields: Show Address Fields', 'wsal-memberpress' ),
			'require_address_fields'            => esc_html__( 'Extended User Information Fields: Require Name Fields', 'wsal-memberpress' ),
			'show_fields_logged_in_purchase'    => esc_html__( 'Show fields to logged in users', 'wsal-memberpress' ),
			'address_fields'                    => esc_html__( 'Address Fields', 'wsal-memberpress' ),
			'product_pages_slug'                => esc_html__( 'Product Pages Slug', 'wsal-memberpress' ),
			'group_pages_slug'                  => esc_html__( 'Group Pages Slug', 'wsal-memberpress' ),
			'admin_email_addresses'             => esc_html__( 'Admin Email Address', 'wsal-memberpress' ),
			'unauthorized_message'              => esc_html__( 'Unaythorsised Access Message', 'wsal-memberpress' ),
			'redirect_on_unauthorized'          => esc_html__( 'Redirect on Unauthorised Access', 'wsal-memberpress' ),
			'unauthorized_redirect_url'         => esc_html__( 'Unauthorised Access Redirect URL', 'wsal-memberpress' ),
			'redirect_non_singular'             => esc_html__( 'Redirect non-singular views', 'wsal-memberpress' ),
			'redirect_method'                   => esc_html__( 'Redirect Method', 'wsal-memberpress' ),
			'unauth_show_excerpts'              => esc_html__( 'Unauthorised Access Show Excerpt', 'wsal-memberpress' ),
			'unauth_excerpt_type'               => esc_html__( 'Unauthorised Access Excerpt Type', 'wsal-memberpress' ),
			'unauth_excerpt_size'               => esc_html__( 'Unauthorised Access Excerpt Size', 'wsal-memberpress' ),
			'unauth_show_login'                 => esc_html__( 'Unauthorised Access Show Login', 'wsal-memberpress' ),
			'authorize_seo_views'               => esc_html__( 'Authorise Search Engines', 'wsal-memberpress' ),
			'seo_unauthorized_noindex'          => esc_html__( 'Block Search Engines', 'wsal-memberpress' ),
			'paywall_enabled'                   => esc_html__( 'Enable Paywal', 'wsal-memberpress' ),
			'paywall_num_free_views'            => esc_html__( 'Number of free views', 'wsal-memberpress' ),
			'disable_summary_email'             => esc_html__( 'Disable Summary Email', 'wsal-memberpress' ),
			'disable_grace_init_days'           => esc_html__( 'Disable the 1 day grace period after signup', 'wsal-memberpress' ),
			'grace_init_days'                   => esc_html__( 'Grace Period', 'wsal-memberpress' ),
			'grace_expire_days'                 => esc_html__( 'Grace Expiry Days', 'wsal-memberpress' ),
			'allow_cancel_subs'                 => esc_html__( 'Allow Members to Cancel their own subscriptions', 'wsal-memberpress' ),
			'allow_suspend_subs'                => esc_html__( 'Allow Members to Cancel their own subscriptions', 'wsal-memberpress' ),
			'disable_global_autoresponder_list' => esc_html__( 'Disable Global Autoresponser List', 'wsal-memberpress' ),
			'opt_in_checked_by_default'         => esc_html__( 'Opt In checked by default', 'wsal-memberpress' ),
			'global_styles'                     => esc_html__( 'Use Global Styles', 'wsal-memberpress' ),
			'include_email_privacy_link'        => esc_html__( 'Include email privacy link', 'wsal-memberpress' ),
			'emails'                            => esc_html__( 'Emails', 'wsal-memberpress' ),
		);
		return $mepr_option_keys;
	}

	/**
	 * Get a copy of a posts meta data prior to update for later compariso.
	 *
	 * @param int $post_id - Post ID.
	 * @return void
	 */
	public function get_before_post_edit_data( $post_id ) {
		$post_id = absint( $post_id ); // Making sure that the post id is integer.
		$post    = get_post( $post_id ); // Get post.

		// If post exists.
		if ( ! empty( $post ) && $post instanceof WP_Post ) {
			$access_array           = array();
			$this->_old_access_list = $access_array;
			$this->_old_post        = $post;
			$this->_old_rules       = ( 'memberpressrule' === $post->post_type ) ? MeprRule::get_rules( $post ) : false;
			
			if ( 'memberpressrule' === $post->post_type ) {
				$mepr_db = new MeprDb();
				$data =  $mepr_db->get_records( $mepr_db->rule_access_conditions, array( 'rule_id' => $post_id ) );

				if ( 'memberpressrule' === $post->post_type ) {				
					foreach( $data as $condition ) {
						$condition = $condition;
						if( !isset( $access_array[ $condition->access_type ] ) ) {
							$access_array[ $condition->access_type ] = array();
						}
						// Make sure they're unique
						if( !in_array( $condition->access_condition, $access_array[ $condition->access_type ] ) ) {
							array_push( $access_array[ $condition->access_type ], $condition->access_condition );
						}
					}
				}

				$this->_old_access_list = $access_array;
			}	
			
			$this->_old_post_meta   = get_post_meta( $post_id );

		}
	}

	/**
	 * Handles triggering events when any of the memberpress CPTs are updated.
	 *
	 * @param  int $post_id - Post ID.
	 * @param  object $post - Post data.
	 * @param  bool $update - Is an update.
	 * @return void
	 */
	public function event_mepr_post_saved( $post_id, $post, $update ) {

		if ( 'auto-draft' === $post->post_status ) {
			return;
		}

		$is_new_post = false;

		if ( ( isset( $this->_old_post ) && 'auto-draft' === $this->_old_post->post_status && 'draft' === $post->post_status ) // Saving draft.
			|| isset( $this->_old_post ) && ( 'draft' === $this->_old_post->post_status && 'publish' === $post->post_status ) // Publishing post.
			|| isset( $this->_old_post ) && ( 'auto-draft' === $this->_old_post->post_status && 'publish' === $post->post_status ) ) {
				$is_new_post = true;
		}

		$editor_link = esc_url(
			add_query_arg(
				array(
					'action' => 'edit',
					'post'   => $post_id,
				),
				admin_url( 'post.php' )
			)
		);

		// Memberships.
		if ( 'memberpressproduct' === $post->post_type ) {
			if ( ! $update || $is_new_post ) {

				$variables = array(
					'EventType' => 'created',
					'name'      => $post->post_title,
					'ID'        => $post_id,
					'ViewLink'  => $editor_link,
				);

				$this->plugin->alerts->trigger_event( 6200, $variables );
				return;
			} elseif ( $update ) {
				$this->check_meta_changes( $post_id );
			}
		}
		// Groups.
		if ( 'memberpressgroup' === $post->post_type ) {
			if ( ! $update || $is_new_post ) {

				$variables = array(
					'EventType' => 'created',
					'name'      => $post->post_title,
					'ID'        => $post_id,
					'ViewLink'  => $editor_link,
				);

				$this->plugin->alerts->trigger_event( 6203, $variables );
				return;
			} elseif ( $update ) {
				$this->check_meta_changes( $post_id );
			}
		}
		// Rules.
		if ( 'memberpressrule' === $post->post_type ) {
			if ( ! $update || $is_new_post ) {
				$variables = array(
					'EventType' => 'created',
					'name'      => $post->post_title,
					'ID'        => $post_id,
					'ViewLink'  => $editor_link,
				);

				$this->plugin->alerts->trigger_event( 6206, $variables );
				return;
			} elseif ( $update ) {
				$this->check_meta_changes( $post_id );
			}
		}
	}

	/**
	 * Handles checking and reporting changes to metadata for our CPTs.
	 *
	 * @param int $post_id - Post ID.
	 * @return void
	 */
	public function check_meta_changes( $post_id ) {
		$mepr_meta     = get_post_meta( $post_id );
		$old_mepr_meta = $this->_old_post_meta;
		$post          = get_post( $post_id );
		$current_metas = $this->get_item_titles();

		// Create empty arrays which will be fill with mapped data.
		$updated_meta  = array();
		$previous_meta = array();

		foreach ( $current_metas as $key => $label ) {
			$updated_value[ $key ]  = isset( $mepr_meta[ $key ] ) ? $mepr_meta[ $key ] : '';
			$previous_value[ $key ] = isset( $old_mepr_meta[ $key ] ) ? $old_mepr_meta[ $key ] : '';
		}

		if ( 'memberpressproduct' === $post->post_type ) {
			$event_id = 6201;
		}

		if ( 'memberpressgroup' === $post->post_type ) {
			$event_id = 6204;
		}

		if ( 'memberpressrule' === $post->post_type ) {
			$event_id = 6207;
			$previous_access_settings  = $this->_old_access_list;
			$access_array = array();

			$mepr_db = new MeprDb();
			$data =  $mepr_db->get_records( $mepr_db->rule_access_conditions, array( 'rule_id' => $post_id ) );

			if ( ! empty( $data ) ) {
				foreach( $data as $condition ) {
					$condition = $condition;
					if( !isset( $access_array[ $condition->access_type ] ) ) {
						$access_array[ $condition->access_type ] = array();
					}
					// Make sure they're unique
					if( !in_array( $condition->access_condition, $access_array[ $condition->access_type ] ) ) {
						array_push( $access_array[ $condition->access_type ], $condition->access_condition );
					}
				}
			}

			$updated_access_settings = $access_array;
			$this->check_access_condition_changes( $previous_access_settings, $updated_access_settings, $post );
		}

		$editor_link = esc_url(
			add_query_arg(
				array(
					'action' => 'edit',
					'post'   => $post_id,
				),
				admin_url( 'post.php' )
			)
		);

		foreach ( $mepr_meta as $meta_key => $meta_value ) {
			if ( substr( $meta_key, 0, 5 ) === '_mepr' ) {
				if ( $updated_value[ $meta_key ] !== $previous_value[ $meta_key ] ) {
					$variables = array(
						'EventType'      => 'modified',
						'name'           => $post->post_title,
						'ID'             => $post_id,
						'option_name'    => ( empty( $current_metas[ $meta_key ] ) ) ? $meta_key : $current_metas[ $meta_key ],
						'previous_value' => $this->tidy_meta_values( $meta_key, $previous_value[ $meta_key ][0] ),
						'value'          => $this->tidy_meta_values( $meta_key, $updated_value[ $meta_key ][0] ),
						'ViewLink'       => $editor_link,
					);

					$this->plugin->alerts->trigger_event( $event_id, $variables );
				}
			}
		}

		if ( 'memberpressproduct' === $post->post_type || 'memberpressgroup' === $post->post_type ) {
			$post_details_keys = array(
				'post_date',
				'post_title',
				'post_status',
				'post_parent',
				'comment_status',
				'post_author',
				'post_name',	
			);

			$post_array        = (array) $post;
			$old_post_array    = (array) $this->_old_post;
		
			foreach ( $post_details_keys as $key ) {
				if ( isset( $old_post_array[ $key ] ) && $post_array[ $key ] != $old_post_array[ $key ] ) {
					$key_title = ( 'post_name' === $key ) ? 'post_slug' : $key;
					$variables = array(
						'EventType'      => 'modified',
						'name'           => $post->post_title,
						'ID'             => $post_id,
						'option_name'    => ucwords( str_replace( '_', ' ', $key_title ) ),
						'previous_value' => $old_post_array[ $key ],
						'value'          => $post_array[ $key ],
						'ViewLink'       => $editor_link,
					);

					if ( 6201 === $event_id && ! self::was_triggered_recently( 6200 ) || 6204 === $event_id && ! self::was_triggered_recently( 6203 )  ) {
						$this->plugin->alerts->trigger_event( $event_id, $variables );
					}
				}
			}
		}
	}

	/**
	 * Check if the rule has had changes to its access settings since last edit.
	 *
	 * @param array   $previous_access_settings
	 * @param array   $updated_access_settings
	 * @param WP_Post $post
	 * @return void
	 */
	public function check_access_condition_changes( $previous_access_settings, $updated_access_settings, $post ) {

		$old_string = $this->flatten_to_string( $previous_access_settings );
		$new_string = $this->flatten_to_string( $updated_access_settings );

		if ( $old_string !== $new_string ) {

			$editor_link = esc_url(
				add_query_arg(
					array(
						'action' => 'edit',
						'post'   => $post_id,
					),
					admin_url( 'post.php' )
				)
			);

			$variables = array(
				'EventType'      => 'modified',
				'name'           => $post->post_title,
				'ID'             => $post->ID,
				'option_name'    => 'Access Conditions',
				'previous_value' => $old_string,
				'value'          => $new_string,
				'ViewLink'       => $post->ID,
			);

			$this->plugin->alerts->trigger_event( 6207, $variables );
		}
	}

	/**
	 * Flat the access arrays into a nice, readable string.
	 *
	 * @param array $array
	 * @return string
	 */
	private function flatten_to_string( $array ) {
		return implode( ', ', array_map(
			function ( $v, $k ) {
				if ( is_array( $v ) ) {
					if ( 'membership' == $k ) {
						$membership_names = $this->flatten_memberships_to_string( $v );
						return ucfirst( $k ).' is ' . $membership_names;
					}
					return ucfirst( $k ) .' is ' . ucwords( implode( ', ', $v ) );
				} else {
					return ucfirst( $k ).' is ' . ucfirst( $v );
				}
			},
			$array,
			array_keys( $array )
		));
	}

	/**
	 * Specifically flatten the membership names whilst also gathering the correct title for each as we go.
	 *
	 * @param  array $array
	 * @return string
	 */
	private function flatten_memberships_to_string( $array ) {
		$final = array();
		foreach ( $array as $item ) {
			$membership_post = get_post( $item );
			array_push( $final, $membership_post->post_title );
		}
		return implode( ', ', $final );
	}

	/**
	 * Tidy a given value, using a special treatment based on its key.
	 *
	 * @param string $meta_key - Key we are working.
	 * @param mixed $value - Value we want to tidy up.
	 * @return string $value - Tided value.
	 */
	public function tidy_meta_values( $meta_key, $value ) {

		$get_page_details_keys = array(
			'thankyou_page_id',
			'login_page_id',
			'account_page_id',
		);

		if ( in_array( $meta_key, $this->bool_metas, true ) ) {
			return ( empty( $value ) || ! isset( $value ) ) ? 'Disabled' : 'Enabled';
		}

		if ( in_array( $meta_key, $this->array_metas, true ) ) {
			if ( ! is_array( $value ) ) {
				$value = unserialize( $value );
			}
			return ( empty( $value ) || ! isset( $value ) ) ? 'None selected' : implode( ', ', $value );
		}

		if ( in_array( $meta_key, $this->special_cases, true ) ) {
			return $this->neaten_array( $value );
		}

		if ( is_serialized( $value ) ) {
			return maybe_unserialize( $value );
		}

		if ( in_array( $meta_key, $get_page_details_keys, true ) ) {
			$page_content = get_post( $value );
			return $page_content->post_title . ' (ID:' . $value .')';
		}

		return ( ! isset( $value ) || empty( $value ) ) ? 'Not supplied' : $value;
	}

	/**
	 * Handles triggering an event when a post id deleted.
	 *
	 * @param int $post_id - Post ID>
	 * @return void
	 */
	public function event_mepr_post_deleted( $post_id ) {
		$this->handle_deletion_evenets( $post_id, 'deleted' );
	}

	/**
	 * Handles triggering an event when a post id trashed.
	 *
	 * @param int $post_id - Post ID.
	 * @return void
	 */
	public function event_mepr_post_trashed( $post_id ) {
		$this->handle_deletion_evenets( $post_id, 'trashed' );
	}

	/**
	 * Handles triggering an event when a post id restored.
	 *
	 * @param int $post_id - Post ID.
	 * @return void
	 */
	public function event_mepr_post_untrashed( $post_id ) {
		$this->handle_deletion_evenets( $post_id, 'restored' );
	}

	/**
	 * The actual event handler which triggering WSAL when a post is deleted, or something along that flavour.
	 *
	 * @param int $post_id - Post ID.
	 * @param string $context - Contect of deletion.
	 * @return void
	 */
	public function handle_deletion_evenets( $post_id, $context = 'deleted' ) {
		$alert_code = 0;
		$post_id    = absint( $post_id );
		$post       = get_post( $post_id );
		$event_type = $context;
		if ( 'trashed' === $context ) {
			$event_type = 'deleted';
		}

		if ( in_array( $post->post_type, $this->wanted_cpts, true ) ) {

			$editor_link = esc_url(
				add_query_arg(
					array(
						'action' => 'edit',
						'post'   => $post_id,
					),
					admin_url( 'post.php' )
				)
			);

			$variables = array(
				'EventType' => $event_type,
				'name'      => $post->post_title,
				'ID'        => $post_id,
			);

			if ( 'memberpressproduct' === $post->post_type ) {
				if ( 'deleted' == $context ) {
					$alert_code = 6202;
				} elseif ( 'trashed' == $context || 'restored' == $context ) {
					$variables[ 'ViewLink' ] = $editor_link;
					$alert_code = 6200;
				}
			}

			if ( 'memberpressgroup' === $post->post_type ) {
				if ( 'deleted' == $context ) {
					$alert_code = 6205;
				} elseif ( 'trashed' == $context || 'restored' == $context ) {
					$variables[ 'ViewLink' ] = $editor_link;
					$alert_code = 6203;
				}
			}

			if ( 'memberpressrule' === $post->post_type ) {
				if ( 'deleted' == $context ) {
					$alert_code = 6208;
				} elseif ( 'trashed' == $context || 'restored' == $context ) {
					$variables[ 'ViewLink' ] = $editor_link;
					$alert_code = 6206;
				}
			}		

			$this->plugin->alerts->trigger_event( $alert_code, $variables );
		}
	}

	/**
	 * Dormant function which will trigger events based on when data is logged in the memberpress logger.
	 *
	 * @param array $data - Data thats been logged.
	 * @return void
	 */
	public function event_mepr_logger_triggered( $data ) {
		// For use later.
	}

	/**
	 * Takes and array and spits out something nice to display.
	 *
	 * @param mixed $data - Data to work on .
	 * @return string - Tidied value.
	 */
	public function neaten_array( $data ) {
		$tidy         = '';
		$a            = $data;
		$unserialized = is_array( $data ) ? $data : unserialize( $data );
		if ( isset( $unserialized[0] ) && is_object( $unserialized[0] ) ) {
			$tidy = implode(
				', ',
				array_map(
					function ( $v, $k ) {
						return $this->neaten_string( $k ) . ': ' . $this->neaten_string( $v ) . ' ';
					},
					(array) $unserialized[0],
					array_keys( (array) $unserialized[0] )
				)
			);
		} elseif ( is_array( $unserialized ) ) {
			foreach ( $unserialized as $key => $val ) {
				if ( is_array( $val ) ) {
					foreach ( $val as $k => $v ) {
						$tidy .= $this->neaten_string( $k ) . ': ' . $this->neaten_string( $v ) . ' ';
					}
				} else {
					$tidy .= $this->neaten_string( $key ) . ': ' . $this->neaten_string( $val ) . ' ';
				}
			}
		}

		return ! empty( $tidy ) ? $tidy : $data;
	}

	/**
	 * Tidies up strings into a nice display value.
	 *
	 * @param string $string - Input string.
	 * @return string - Tidied value.
	 */
	public function neaten_string( $string ) {
		// Once last check, for good measure.
		if ( is_array( $string ) ) {
			$string = $this->neaten_array( $string );
		}

		$string = preg_replace( '/(?<!\ )[A-Z]/', ' $0', $string );
		$string = str_replace( 'Mepr', '', $string );
		$string = str_replace( '_', ' ', $string );
		$string = str_replace( ' id', ' ID', $string );
		return ucfirst( $string );
	}

	/**
	 * Handle events when a setting is updated.
	 *
	 * @param string $option - Option name.
	 * @param mixed $old_value - Old value.
	 * @param mixed $value - New value.
	 * @return void
	 */
	public function event_mepr_settings_updated( $option, $old_value, $value ) {
		if ( substr( $option, 0, 5 ) === 'mepr_' ) {
			// We only want to report a specific area for now.
			if ( 'mepr_options' === $option && $value !== $old_value ) {

				// Create empty arrays which will be fill with mapped data.
				$updated_value  = array();
				$previous_value = array();

				// Fill empty array with whatever we have or dont have.
				foreach ( $this->get_settings_titles() as $key => $label ) {
					$updated_value[ $key ]  = isset( $value[ $key ] ) ? $value[ $key ] : '';
					$previous_value[ $key ] = isset( $old_value[ $key ] ) ? $old_value[ $key ] : '';
				}

				// Compare it, triggering events as we go.
				foreach ( $this->get_settings_titles() as $key => $label ) {

					if ( 'unauth_excerpt_size' === $key ) {
						$previous_value[ $key ] = intval( $previous_value[ $key ] );
						$updated_value[ $key ] = intval( $updated_value[ $key ] );
					}

					if ( $this->tidy_meta_values( $key, $updated_value[ $key ] ) !== $this->tidy_meta_values( $key, $previous_value[ $key ] ) ) {

						// Email settings is very complex, handle on its own.
						if ( 'emails' === $key ) {
							foreach ( $updated_value[ $key ] as $email => $settings ) {
								if ( $updated_value[ $key ][ $email ] !== $previous_value[ $key ][ $email ] ) {
									$variables = array(
										'setting_name'   => $this->neaten_string( $email ),
										'previous_value' => $this->neaten_array( $previous_value[ $key ][ $email ] ),
										'value'          => $this->neaten_array( $updated_value[ $key ][ $email ] ),
									);

									if ( ! self::was_triggered_recently( 6210 ) ) {
										$this->plugin->alerts->trigger_event( 6210, $variables );
									}
								}
							}
							continue;
						}

						if ( $this->tidy_meta_values( $key, $previous_value[ $key ] ) !== $this->tidy_meta_values( $key, $updated_value[ $key ] ) ) {
							$variables = array(
								'setting_name'   => ! empty( $label ) ? $label : $key,
								'previous_value' => $this->tidy_meta_values( $key, $previous_value[ $key ] ),
								'value'          => $this->tidy_meta_values( $key, $updated_value[ $key ] ),
							);
	
							$this->plugin->alerts->trigger_event( 6210, $variables );
						}
					}
				}
			}
		}

		if ( 'wp_user_roles' === $option ) {
			if ( count( $value ) < count( $old_value ) ) {
				$diff = $this->array_diff_assoc_recursive( $old_value, $value );
				foreach ( $diff as $removed_role => $details ) {
					$variables = array(
						'EventType' => 'deleted',
						'name'      => esc_html( ucfirst( str_replace( '_', ' ', $removed_role ) ) ),
						'ID'        => esc_html( $removed_role  ),
					);
			
					$this->plugin->alerts->trigger_event( 6211, $variables );
				}
			}
		}
	}

	/**
	 * Check multidimention arrays and return difference.
	 *
	 * @param array $array1
	 * @param array $array2
	 * @return void
	 */
	public function array_diff_assoc_recursive( $array1, $array2 ) {
		foreach( $array1 as $key => $value ) { 
			if( is_array( $value ) ) {
				if( ! isset( $array2[ $key ] ) ) {
					$difference[ $key ] = $value;
				} elseif( ! is_array( $array2[ $key ] ) ) {
					$difference[$key] = $value;
				} else {
					$new_diff = $this->array_diff_assoc_recursive( $value, $array2[ $key ] );
					if( $new_diff != false ) {
						$difference[ $key ] = $new_diff;
					}
				}
			} elseif( ! isset( $array2[ $key ] ) || $array2[ $key ] != $value ) {
				$difference[ $key ] = $value;
			}
		}
		return ! isset( $difference ) ? 0 : $difference;
	}
}
