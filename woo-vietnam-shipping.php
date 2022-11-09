<?php
/*
 * Plugin Name: Woocommerce Vietnam Shipping
 * Plugin URI: https://namncn.com/woo-vietnam-shipping/
 * Version: 1.0.0
 * Description: Add province/city, district, commune/ward/town to checkout form and simplify checkout form
 * Author: Nam Truong
 * Author URI: https://namncn.com
 * Text Domain: woo-vietnam-shipping
 * Domain Path: /languages
 * WC requires at least: 3.0.0
 * WC tested up to: 7.0.*
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl-3.0
Woocommerce Vietnam Shipping

Copyright (C) 2017 Nam Truong - www.namncn.com

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

if ( ! defined( 'WVS_FILE' ) ) {
	define( 'WVS_FILE', __FILE__ );
}

class Woo_Vietnam_Shipping {

	protected static $_instance;

	public $version         = '1.0.0';
	public $_optionName     = 'wvs_woo_district';
	public $_optionGroup    = 'wvs-district-options-group';
	public $_defaultOptions = array(
		'active_village'               => '',
		'required_village'             => '',
		'to_vnd'                       => '',
		'remove_methob_title'          => '',
		'freeship_remove_other_methob' => '',
		'khoiluong_quydoi'             => '6000',
		'tinhthanh_default'            => '01',
		'vnd_usd_rate'                 => '22745',
		'vnd2usd_currency'             => 'USD',

		'alepay_support'               => 0,
		'enable_firstname'             => 0,
		'enable_country'               => 0,
		'enable_postcode'              => 0,

		'enable_getaddressfromphone'   => 0,
		'enable_recaptcha'             => 0,
		'active_filter_order'          => 0,
		'recaptcha_sitekey'            => '',
		'recaptcha_secretkey'          => '',

		'license_key'                  => '',
	);

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	public function __construct() {
		$this->includes();
		$this->init_hooks();
	}

	public function includes() {
		require_once 'cities/tinh_thanhpho.php';
		require_once 'includes/apps.php';
	}

	public function init_hooks() {
		add_filter( 'woocommerce_checkout_fields', array( $this, 'custom_override_checkout_fields' ), 999999 );
		add_filter( 'woocommerce_states', array( $this, 'vietnam_cities_woocommerce' ), 99999 );

		add_action( 'wp_enqueue_scripts', array( $this, 'wvs_enqueue_UseAjaxInWp' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );

		add_action( 'wp_ajax_load_diagioihanhchinh', array( $this, 'load_diagioihanhchinh_func' ) );
		add_action( 'wp_ajax_nopriv_load_diagioihanhchinh', array( $this, 'load_diagioihanhchinh_func' ) );

		add_filter( 'woocommerce_localisation_address_formats', array( $this, 'wvs_woocommerce_localisation_address_formats' ), 99999 );
		add_filter( 'woocommerce_order_formatted_billing_address', array( $this, 'wvs_woocommerce_order_formatted_billing_address' ), 10, 2 );

		add_action( 'woocommerce_admin_order_data_after_shipping_address', array( $this, 'wvs_after_shipping_address' ), 10, 1 );
		add_action( 'save_post', array( $this, 'save_shipping_phone_meta' ), 10, 3 );
		add_filter( 'woocommerce_order_formatted_shipping_address', array( $this, 'wvs_woocommerce_order_formatted_shipping_address' ), 10, 2 );

		add_filter( 'woocommerce_order_details_after_customer_details', array( $this, 'wvs_woocommerce_order_details_after_customer_details' ), 10 );

		// my account
		add_filter( 'woocommerce_my_account_my_address_formatted_address', array( $this, 'wvs_woocommerce_my_account_my_address_formatted_address' ), 10, 3 );
		add_filter( 'woocommerce_default_address_fields', array( $this, 'wvs_custom_override_default_address_fields' ), 99999 );
		add_filter( 'woocommerce_get_country_locale', array( $this, 'wvs_woocommerce_get_country_locale' ), 99999 );

		// More action
		add_filter( 'default_checkout_billing_country', array( $this, 'change_default_checkout_country' ), 9999 );
		add_filter( 'woocommerce_customer_get_shipping_country', array( $this, 'change_default_checkout_country' ), 9999 );
		// add_filter( 'default_checkout_billing_state', array($this, 'change_default_checkout_state'), 99 );

		// Options
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'admin_init', array( $this, 'register_mysettings' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( dirname( WVS_FILE ) ), array( $this, 'plugin_action_links' ) );

		add_filter( 'woocommerce_package_rates', array( $this, 'wvs_hide_shipping_when_shipdisable' ), 100 );

		add_option( $this->_optionName, $this->_defaultOptions );

		// admin order address, form billing
		add_filter( 'woocommerce_admin_billing_fields', array( $this, 'wvs_woocommerce_admin_billing_fields' ), 99 );
		add_filter( 'woocommerce_admin_shipping_fields', array( $this, 'wvs_woocommerce_admin_shipping_fields' ), 99 );

		add_filter( 'woocommerce_form_field_select', array( $this, 'wvs_woocommerce_form_field_select' ), 10, 4 );

		add_filter( 'woocommerce_shipping_calculator_enable_postcode', '__return_false' );

		add_filter( 'woocommerce_get_order_address', array( $this, 'wvs_woocommerce_get_order_address' ), 99, 2 );  // API V1
		add_filter( 'woocommerce_rest_prepare_shop_order_object', array( $this, 'wvs_woocommerce_rest_prepare_shop_order_object' ), 99, 3 ); // API V2
		add_filter( 'woocommerce_api_order_response', array( $this, 'wvs_woocommerce_api_order_response' ), 99, 2 ); // API V3
		// woocommerce_api_customer_response

		add_filter( 'woocommerce_formatted_address_replacements', array( $this, 'wvs_woocommerce_formatted_address_replacements' ), 9 );

		add_action( 'init', array( $this, 'load_plugin_textdomain' ), 0 );
	}

	/**
	 * Load Localisation files.
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain( 'woo-vietnam-shipping', false, plugin_basename( dirname( WVS_FILE ) ) . '/languages' );
	}

	public function admin_menu() {
		add_submenu_page(
			'woocommerce',
			__( 'Woocommerce Vietnam Shipping', 'woo-vietnam-shipping' ),
			__( 'Vietnam Shipping', 'woo-vietnam-shipping' ),
			'manage_woocommerce',
			'woo-vietnam-shipping',
			array(
				$this,
				'woo_vietnam_shipping',
			)
		);
	}

	public function register_mysettings() {
		register_setting( $this->_optionGroup, $this->_optionName );
	}

	public function woo_vietnam_shipping() {
		include 'includes/options-page.php';
	}

	public function vietnam_cities_woocommerce( $states ) {
		global $tinh_thanhpho;
		$states['VN'] = apply_filters( 'wvs_states_vn', $tinh_thanhpho );

		return $states;
	}

	public function custom_override_checkout_fields( $fields ) {
		global $tinh_thanhpho;

		WC()->customer->set_shipping_country( 'VN' );

		if ( ! $this->get_options( 'enable_firstname' ) ) {
			// Billing
			$fields['billing']['billing_last_name'] = array(
				'label'       => __( 'Full name', 'woo-vietnam-shipping' ),
				'placeholder' => _x( 'Type Full name', 'placeholder', 'woo-vietnam-shipping' ),
				'required'    => true,
				'class'       => array( 'form-row-wide' ),
				'clear'       => true,
				'priority'    => 10,
			);
		}
		if ( isset( $fields['billing']['billing_phone'] ) ) {
			$fields['billing']['billing_phone']['class']       = array( 'form-row-first' );
			$fields['billing']['billing_phone']['placeholder'] = __( 'Type your phone', 'woo-vietnam-shipping' );
		}
		if ( isset( $fields['billing']['billing_email'] ) ) {
			$fields['billing']['billing_email']['class']       = array( 'form-row-last' );
			$fields['billing']['billing_email']['placeholder'] = __( 'Type your email', 'woo-vietnam-shipping' );
		}
		$fields['billing']['billing_state'] = array(
			'label'       => __( 'Province/City', 'woo-vietnam-shipping' ),
			'required'    => true,
			'type'        => 'select',
			'class'       => array( 'form-row-first', 'address-field', 'update_totals_on_change' ),
			'placeholder' => _x( 'Select Province/City', 'placeholder', 'woo-vietnam-shipping' ),
			'options'     => array( '' => __( 'Select Province/City', 'woo-vietnam-shipping' ) ) + apply_filters( 'wvs_states_vn', $tinh_thanhpho ),
			'priority'    => 30,
		);
		$fields['billing']['billing_city']  = array(
			'label'       => __( 'District', 'woo-vietnam-shipping' ),
			'required'    => true,
			'type'        => 'select',
			'class'       => array( 'form-row-last', 'update_totals_on_change' ),
			'placeholder' => _x( 'Select District', 'placeholder', 'woo-vietnam-shipping' ),
			'options'     => array(
				'' => '',
			),
			'priority'    => 40,
		);
		if ( ! $this->get_options() ) {
			$fields['billing']['billing_address_2'] = array(
				'label'       => __( 'Commune/Ward/Town', 'woo-vietnam-shipping' ),
				'required'    => true,
				'type'        => 'select',
				'class'       => array( 'form-row-first', 'update_totals_on_change' ),
				'placeholder' => _x( 'Select Commune/Ward/Town', 'placeholder', 'woo-vietnam-shipping' ),
				'options'     => array(
					'' => '',
				),
				'priority'    => 50,
			);
			if ( $this->get_options( 'required_village' ) ) {
				$fields['billing']['billing_address_2']['required'] = false;
			}
		}
		$fields['billing']['billing_address_1']['placeholder'] = _x( 'Ex: No. 20, 90 Alley', 'placeholder', 'woo-vietnam-shipping' );
		$fields['billing']['billing_address_1']['class']       = array( 'form-row-last' );

		$fields['billing']['billing_address_1']['priority'] = 60;
		if ( isset( $fields['billing']['billing_phone'] ) ) {
			$fields['billing']['billing_phone']['priority'] = 20;
		}
		if ( isset( $fields['billing']['billing_email'] ) ) {
			$fields['billing']['billing_email']['priority'] = 21;
		}
		if ( ! $this->get_options( 'enable_firstname' ) ) {
			unset( $fields['billing']['billing_first_name'] );
		}
		if ( ! $this->get_options( 'enable_country' ) ) {
			unset( $fields['billing']['billing_country'] );
		} else {
			$fields['billing']['billing_country']['priority'] = 22;
		}
		unset( $fields['billing']['billing_company'] );

		// Shipping
		if ( ! $this->get_options( 'enable_firstname' ) ) {
			$fields['shipping']['shipping_last_name'] = array(
				'label'       => __( 'Recipient full name', 'woo-vietnam-shipping' ),
				'placeholder' => _x( 'Recipient full name', 'placeholder', 'woo-vietnam-shipping' ),
				'required'    => true,
				'class'       => array( 'form-row-first' ),
				'clear'       => true,
				'priority'    => 10,
			);
		}
		$fields['shipping']['shipping_phone'] = array(
			'label'       => __( 'Recipient phone', 'woo-vietnam-shipping' ),
			'placeholder' => _x( 'Recipient phone', 'placeholder', 'woo-vietnam-shipping' ),
			'required'    => false,
			'class'       => array( 'form-row-last' ),
			'clear'       => true,
			'priority'    => 20,
		);
		if ( $this->get_options( 'enable_firstname' ) ) {
			$fields['shipping']['shipping_phone']['class'] = array( 'form-row-wide' );
		}
		$fields['shipping']['shipping_state'] = array(
			'label'       => __( 'Province/City', 'woo-vietnam-shipping' ),
			'required'    => true,
			'type'        => 'select',
			'class'       => array( 'form-row-first', 'address-field', 'update_totals_on_change' ),
			'placeholder' => _x( 'Select Province/City', 'placeholder', 'woo-vietnam-shipping' ),
			'options'     => array( '' => __( 'Select Province/City', 'woo-vietnam-shipping' ) ) + apply_filters( 'wvs_states_vn', $tinh_thanhpho ),
			'priority'    => 30,
		);
		$fields['shipping']['shipping_city']  = array(
			'label'       => __( 'District', 'woo-vietnam-shipping' ),
			'required'    => true,
			'type'        => 'select',
			'class'       => array( 'form-row-last' ),
			'placeholder' => _x( 'Select District', 'placeholder', 'woo-vietnam-shipping' ),
			'options'     => array(
				'' => '',
			),
			'priority'    => 40,
		);
		if ( ! $this->get_options() ) {
			$fields['shipping']['shipping_address_2'] = array(
				'label'       => __( 'Commune/Ward/Town', 'woo-vietnam-shipping' ),
				'required'    => true,
				'type'        => 'select',
				'class'       => array( 'form-row-first' ),
				'placeholder' => _x( 'Select Commune/Ward/Town', 'placeholder', 'woo-vietnam-shipping' ),
				'options'     => array(
					'' => '',
				),
				'priority'    => 50,
			);
			if ( $this->get_options( 'required_village' ) ) {
				$fields['shipping']['shipping_address_2']['required'] = false;
			}
		}
		$fields['shipping']['shipping_address_1']['placeholder'] = _x( 'Ex: No. 20, 90 Alley', 'placeholder', 'woo-vietnam-shipping' );
		$fields['shipping']['shipping_address_1']['class']       = array( 'form-row-last' );
		$fields['shipping']['shipping_address_1']['priority']    = 60;
		if ( ! $this->get_options( 'enable_firstname' ) ) {
			unset( $fields['shipping']['shipping_first_name'] );
		}
		if ( ! $this->get_options( 'enable_country' ) ) {
			unset( $fields['shipping']['shipping_country'] );
		} else {
			$fields['shipping']['shipping_country']['priority'] = 22;
		}
		unset( $fields['shipping']['shipping_company'] );

		uasort( $fields['billing'], array( $this, 'sort_fields_by_order' ) );
		uasort( $fields['shipping'], array( $this, 'sort_fields_by_order' ) );

		return apply_filters( 'wvs_checkout_fields', $fields );
	}

	public function sort_fields_by_order( $a, $b ) {
		if ( ! isset( $b['priority'] ) || ! isset( $a['priority'] ) || $a['priority'] == $b['priority'] ) {
			return 0;
		}

		return ( $a['priority'] < $b['priority'] ) ? -1 : 1;
	}

	public function search_in_array( $array, $key, $value ) {
		$results = array();

		if ( is_array( $array ) ) {
			if ( isset( $array[ $key ] ) && $array[ $key ] == $value ) {
				$results[] = $array;
			} elseif ( isset( $array[ $key ] ) && is_serialized( $array[ $key ] ) && in_array( $value, maybe_unserialize( $array[ $key ] ) ) ) {
				$results[] = $array;
			}
			foreach ( $array as $subarray ) {
				$results = array_merge( $results, $this->search_in_array( $subarray, $key, $value ) );
			}
		}

		return $results;
	}

	public function check_file_open_status( $file_url = '' ) {
		if ( ! $file_url ) {
			return false;
		}
		$cache_key = '_check_get_address_file_status';
		$status    = get_transient( $cache_key );

		if ( false !== $status ) {
			return $status;
		}

		$response = wp_safe_remote_get(
			esc_url_raw( $file_url ),
			array(
				'redirection' => 0,
			)
		);

		$response_code = intval( wp_remote_retrieve_response_code( $response ) );

		if ( $response_code === 200 ) {
			set_transient( $cache_key, $response_code, 1 * DAY_IN_SECONDS );

			return $response_code;
		}

		return false;
	}

	public function wvs_enqueue_UseAjaxInWp() {
		$page_id      = array( wc_get_page_id( 'checkout' ), wc_get_page_id( 'cart' ) );
		$edit_address = get_option( 'woocommerce_myaccount_page_id' );
		if ( ( $page_id && in_array( get_the_ID(), $page_id ) ) || ( $edit_address && is_page( $edit_address ) ) ) {
			wp_enqueue_style( 'dwas_styles', plugins_url( '/assets/css/wvs_dwas_style.css', __FILE__ ), array(), $this->version, 'all' );

			wp_enqueue_script( 'wvs_tinhthanhpho', plugins_url( 'assets/js/wvs_tinhthanh.js', __FILE__ ), array( 'jquery', 'select2' ), $this->version, true );

			$get_address = plugins_url( __FILE__ ) . 'get-address.php';
			if ( $this->check_file_open_status( $get_address ) != 200 ) {
				$get_address = admin_url( 'admin-ajax.php' );
			}

			$php_array = array(
				'admin_ajax'        => admin_url( 'admin-ajax.php' ),
				'get_address'       => $get_address,
				'home_url'          => home_url(),
				'formatNoMatches'   => __( 'No value', 'woo-vietnam-shipping' ),
				'phone_error'       => __( 'Phone number is incorrect', 'woo-vietnam-shipping' ),
				'loading_text'      => __( 'Loading...', 'woo-vietnam-shipping' ),
				'loadaddress_error' => __( 'Phone number does not exist', 'woo-vietnam-shipping' ),
			);
			wp_localize_script( 'wvs_tinhthanhpho', 'vncheckout_array', $php_array );
		}
	}

	public function load_diagioihanhchinh_func() {
		$matp = isset( $_POST['matp'] ) ? wc_clean( wp_unslash( $_POST['matp'] ) ) : '';
		$maqh = isset( $_POST['maqh'] ) ? intval( $_POST['maqh'] ) : '';
		if ( $matp ) {
			$result = $this->get_list_district( $matp );
			wp_send_json_success( $result );
		}
		if ( $maqh ) {
			$result = $this->get_list_village( $maqh );
			wp_send_json_success( $result );
		}
		wp_send_json_error();
		die();
	}

	public function wvs_get_name_location( $arg = array(), $id = '', $key = '' ) {
		if ( is_array( $arg ) && ! empty( $arg ) ) {
			$nameQuan = $this->search_in_array( $arg, $key, $id );
			$nameQuan = isset( $nameQuan[0]['name'] ) ? $nameQuan[0]['name'] : '';

			return $nameQuan;
		}

		return false;
	}

	public function get_name_city( $id = '' ) {
		global $tinh_thanhpho;
		$tinh_thanhpho = apply_filters( 'wvs_states_vn', $tinh_thanhpho );
		if ( is_numeric( $id ) ) {
			$id_tinh = sprintf( '%02d', intval( $id ) );
			if ( ! is_array( $tinh_thanhpho ) || empty( $tinh_thanhpho ) ) {
				include 'cities/tinh_thanhpho_old.php';
			}
		} else {
			$id_tinh = wc_clean( wp_unslash( $id ) );
		}
		$tinh_thanhpho_name = ( isset( $tinh_thanhpho[ $id_tinh ] ) ) ? $tinh_thanhpho[ $id_tinh ] : '';

		return $tinh_thanhpho_name;
	}

	public function get_name_district( $id = '' ) {
		include 'cities/quan_huyen.php';
		$id_quan = sprintf( '%03d', intval( $id ) );
		if ( is_array( $quan_huyen ) && ! empty( $quan_huyen ) ) {
			$nameQuan = $this->search_in_array( $quan_huyen, 'maqh', $id_quan );
			$nameQuan = isset( $nameQuan[0]['name'] ) ? $nameQuan[0]['name'] : '';

			return $nameQuan;
		}

		return false;
	}

	public function get_name_village( $id = '' ) {
		include 'cities/xa_phuong_thitran.php';
		$id_xa = sprintf( '%05d', intval( $id ) );
		if ( is_array( $xa_phuong_thitran ) && ! empty( $xa_phuong_thitran ) ) {
			$name = $this->search_in_array( $xa_phuong_thitran, 'xaid', $id_xa );
			$name = isset( $name[0]['name'] ) ? $name[0]['name'] : '';

			return $name;
		}

		return false;
	}

	public function wvs_woocommerce_localisation_address_formats( $arg ) {
		unset( $arg['default'] );
		unset( $arg['VN'] );
		$arg['default'] = "{name}\n{company}\n{address_1}\n{address_2}\n{city}\n{state}\n{country}";
		$arg['VN']      = "{name}\n{company}\n{address_1}\n{address_2}\n{city}\n{state}\n{country}";

		return $arg;
	}

	public function wvs_woocommerce_order_formatted_billing_address( $eArg, $eThis ) {
		if ( ! $eArg ) {
			return '';
		}

		if ( $this->check_woo_version() ) {
			$orderID = $eThis->get_id();
		} else {
			$orderID = $eThis->id;
		}

		$nameTinh = $this->get_name_city( get_post_meta( $orderID, '_billing_state', true ) );
		$nameQuan = $this->get_name_district( get_post_meta( $orderID, '_billing_city', true ) );
		$nameXa   = $this->get_name_village( get_post_meta( $orderID, '_billing_address_2', true ) );

		unset( $eArg['state'] );
		unset( $eArg['city'] );
		unset( $eArg['address_2'] );

		$eArg['state']     = $nameTinh;
		$eArg['city']      = $nameQuan;
		$eArg['address_2'] = $nameXa;

		return $eArg;
	}

	public function wvs_woocommerce_order_formatted_shipping_address( $eArg, $eThis ) {
		if ( ! $eArg ) {
			return '';
		}

		if ( $this->check_woo_version() ) {
			$orderID = $eThis->get_id();
		} else {
			$orderID = $eThis->id;
		}

		$nameTinh = $this->get_name_city( get_post_meta( $orderID, '_shipping_state', true ) );
		$nameQuan = $this->get_name_district( get_post_meta( $orderID, '_shipping_city', true ) );
		$nameXa   = $this->get_name_village( get_post_meta( $orderID, '_shipping_address_2', true ) );

		unset( $eArg['state'] );
		unset( $eArg['city'] );
		unset( $eArg['address_2'] );

		$eArg['state']     = $nameTinh;
		$eArg['city']      = $nameQuan;
		$eArg['address_2'] = $nameXa;

		return $eArg;
	}

	public function wvs_woocommerce_my_account_my_address_formatted_address( $args, $customer_id, $name ) {
		if ( ! $args ) {
			return '';
		}

		$nameTinh = $this->get_name_city( get_user_meta( $customer_id, $name . '_state', true ) );
		$nameQuan = $this->get_name_district( get_user_meta( $customer_id, $name . '_city', true ) );
		$nameXa   = $this->get_name_village( get_user_meta( $customer_id, $name . '_address_2', true ) );

		unset( $args['address_2'] );
		unset( $args['city'] );
		unset( $args['state'] );

		$args['state']     = $nameTinh;
		$args['city']      = $nameQuan;
		$args['address_2'] = $nameXa;

		return $args;
	}

	public function natorder( $a, $b ) {
		return strnatcasecmp( $a['name'], $b['name'] );
	}

	public function get_list_district( $matp = '' ) {
		if ( ! $matp ) {
			return false;
		}
		if ( is_numeric( $matp ) ) {
			include 'cities/quan_huyen_old.php';
			$matp = sprintf( '%02d', intval( $matp ) );
		} else {
			include 'cities/quan_huyen.php';
			$matp = wc_clean( wp_unslash( $matp ) );
		}
		$result = $this->search_in_array( $quan_huyen, 'matp', $matp );
		usort( $result, array( $this, 'natorder' ) );

		return $result;
	}

	public function get_list_district_select( $matp = '' ) {
		$district_select       = array();
		$district_select_array = $this->get_list_district( $matp );
		if ( $district_select_array && is_array( $district_select_array ) ) {
			foreach ( $district_select_array as $district ) {
				$district_select[ $district['maqh'] ] = $district['name'];
			}
		}

		return $district_select;
	}

	public function get_list_district_selects( $args = array() ) {
		$arrays = array();

		if ( $args ) {
			foreach( $args as $arg ) {
				$arrays += $this->get_list_district_select( $arg );
			}
		}

		return $arrays;
	}

	public function get_districts() {
		include 'cities/quan_huyen.php';

		return wp_list_pluck( $quan_huyen, 'name', 'maqh' );
	}

	public function get_villages() {
		include 'cities/xa_phuong_thitran.php';

		return wp_list_pluck( $xa_phuong_thitran, 'name', 'xaid' );
	}

	public function get_list_village( $maqh = '' ) {
		if ( ! $maqh ) {
			return false;
		}
		include 'cities/xa_phuong_thitran.php';
		$id_xa  = sprintf( '%05d', intval( $maqh ) );
		$result = $this->search_in_array( $xa_phuong_thitran, 'maqh', $id_xa );
		usort( $result, array( $this, 'natorder' ) );

		return $result;
	}

	public function get_list_village_select( $maqh = '' ) {
		$village_select       = array();
		$village_select_array = $this->get_list_village( $maqh );
		if ( $village_select_array && is_array( $village_select_array ) ) {
			foreach ( $village_select_array as $village ) {
				$village_select[ $village['xaid'] ] = $village['name'];
			}
		}

		return $village_select;
	}

	public function get_list_village_selects( $args = array() ) {
		$arrays = array();

		if ( $args ) {
			foreach( $args as $arg ) {
				$arrays += $this->get_list_village_select( $arg );
			}
		}

		return $arrays;
	}

	public function wvs_after_shipping_address( $order ) {
		if ( $this->check_woo_version() ) {
			$orderID = $order->get_id();
		} else {
			$orderID = $order->id;
		}
		echo '<p><label for="_shipping_phone">' . __( 'Phone number of the recipient', 'woo-vietnam-shipping' ) . ':</label> <br>
	<input type="text" class="short" style="" name="_shipping_phone" id="_shipping_phone" value="' . get_post_meta( $orderID, '_shipping_phone', true ) . '" placeholder=""></p>';

		// echo $order->get_checkout_order_received_url();
	}

	public function wvs_woocommerce_order_details_after_customer_details( $order ) {
		ob_start();
		if ( $this->check_woo_version() ) {
			$orderID = $order->get_id();
		} else {
			$orderID = $order->id;
		}
		$sdtnguoinhan = get_post_meta( $orderID, '_shipping_phone', true );
		if ( $sdtnguoinhan ) : ?>
		<tr>
			<th><?php _e( 'Shipping Phone:', 'woo-vietnam-shipping' ); ?></th>
			<td><?php echo esc_html( $sdtnguoinhan ); ?></td>
		</tr>
			<?php
		endif;
		echo ob_get_clean();
	}

	public function get_options( $option = 'active_village' ) {
		$flra_options = wp_parse_args( get_option( $this->_optionName ), $this->_defaultOptions );

		return isset( $flra_options[ $option ] ) ? $flra_options[ $option ] : false;
	}

	public function admin_enqueue_scripts() {
		wp_enqueue_style( 'woocommerce_district_shipping_styles', plugins_url( '/assets/css/admin.css', __FILE__ ), array(), $this->version, 'all' );
		wp_register_script( 'woocommerce_district_shipping_rate_rows', plugins_url( '/assets/js/admin-district-shipping.js', __FILE__ ), array( 'jquery', 'wp-util' ), $this->version, true );
		wp_localize_script(
			'woocommerce_district_shipping_rate_rows',
			'woocommerce_district_shipping_rate_rows',
			array(
				'i18n'             => array(
					'delete_rates' => __( 'Delete the selected boxes?', 'woocommerce-table-rate-shipping' ),
				),
				'delete_box_nonce' => wp_create_nonce( 'delete-box' ),
			)
		);
		wp_enqueue_script( 'woocommerce_district_admin_order', plugins_url( '/assets/js/admin-district-admin-order.js', __FILE__ ), array( 'jquery', 'select2' ), $this->version, true );
		wp_localize_script(
			'woocommerce_district_admin_order',
			'woocommerce_district_admin',
			array(
				'ajaxurl'         => admin_url( 'admin-ajax.php' ),
				'formatNoMatches' => __( 'No value', 'woo-vietnam-shipping' ),
			)
		);
	}

	/*Check version*/
	public function wvs_district_zone_shipping_check_woo_version( $minimum_required = '2.6' ) {
		$woocommerce = WC();
		$version     = $woocommerce->version;
		$active      = version_compare( $version, $minimum_required, 'ge' );

		return $active;
	}

	public function dwas_sort_desc_array( $input = array(), $keysort = 'dk' ) {
		$sort = array();
		if ( $input && is_array( $input ) ) {
			foreach ( $input as $k => $v ) {
				$sort[ $keysort ][ $k ] = $v[ $keysort ];
			}
			array_multisort( $sort[ $keysort ], SORT_DESC, $input );
		}

		return $input;
	}

	public function dwas_sort_asc_array( $input = array(), $keysort = 'dk' ) {
		$sort = array();
		if ( $input && is_array( $input ) ) {
			foreach ( $input as $k => $v ) {
				$sort[ $keysort ][ $k ] = $v[ $keysort ];
			}
			array_multisort( $sort[ $keysort ], SORT_ASC, $input );
		}

		return $input;
	}

	public function dwas_format_key_array( $input = array() ) {
		$output = array();
		if ( $input && is_array( $input ) ) {
			foreach ( $input as $k => $v ) {
				$output[] = $v;
			}
		}

		return $output;
	}

	public function dwas_search_bigger_in_array( $array, $key, $value ) {
		$results = array();

		if ( is_array( $array ) ) {
			if ( isset( $array[ $key ] ) && ( $array[ $key ] <= $value ) ) {
				$results[] = $array;
			}

			foreach ( $array as $subarray ) {
				$results = array_merge( $results, $this->dwas_search_bigger_in_array( $subarray, $key, $value ) );
			}
		}

		return $results;
	}

	public function dwas_search_bigger_in_array_weight( $array, $key, $value ) {
		$results = array();

		if ( is_array( $array ) ) {
			if ( isset( $array[ $key ] ) && ( $array[ $key ] >= $value ) ) {
				$results[] = $array;
			}

			foreach ( $array as $subarray ) {
				$results = array_merge( $results, $this->dwas_search_bigger_in_array_weight( $subarray, $key, $value ) );
			}
		}

		return $results;
	}

	public static function plugin_action_links( $links ) {
		$action_links = array(
			'upgrade_pro' => '<a href="https://levantoan.com/plugin-tinh-phi-van-chuyen-cho-quan-huyen-trong-woocommerce/"  target="_blank" style="color: #e64a19; font-weight: bold; font-size: 108%%;" title="' . esc_attr( __( 'Upgrade to Pro', 'woo-vietnam-shipping' ) ) . '">' . __( 'Upgrade to Pro', 'woo-vietnam-shipping' ) . '</a>',
			'settings'    => '<a href="' . admin_url( 'admin.php?page=wvs-district-address' ) . '" title="' . esc_attr( __( 'Settings', 'woo-vietnam-shipping' ) ) . '">' . __( 'Settings', 'woo-vietnam-shipping' ) . '</a>',
		);

		return array_merge( $action_links, $links );
	}

	public function check_woo_version( $version = '3.0.0' ) {
		if ( defined( 'WOOCOMMERCE_VERSION' ) && version_compare( WOOCOMMERCE_VERSION, $version, '>=' ) ) {
			return true;
		}

		return false;
	}

	public function change_default_checkout_country() {
		return 'VN';
	}

	public function wvs_woocommerce_get_country_locale( $args ) {
		$field_s = array(
			'state'     => array(
				'label'    => __( 'Province/City', 'woo-vietnam-shipping' ),
				'priority' => 41,
			),
			'city'      => array(
				'priority' => 42,
			),
			'address_1' => array(
				'priority' => 44,
			),
		);
		if ( ! $this->get_options() ) {
			$field_s['address_2'] = array(
				'hidden'   => false,
				'priority' => 43,
			);
		}
		$args['VN'] = $field_s;

		return $args;
	}

	public function change_default_checkout_state() {
		$state = $this->get_options( 'tinhthanh_default' );

		return ( $state ) ? $state : '01';
	}

	public function wvs_hide_shipping_when_shipdisable( $rates ) {
		$shipdisable = array();
		foreach ( $rates as $rate_id => $rate ) {
			if ( 'shipdisable' === $rate->id ) {
				$shipdisable[ $rate_id ] = $rate;
				break;
			}
		}

		return ! empty( $shipdisable ) ? $shipdisable : $rates;
	}

	public function wvs_custom_override_default_address_fields( $address_fields ) {
		if ( ! $this->get_options( 'enable_firstname' ) ) {
			unset( $address_fields['first_name'] );
			$address_fields['last_name'] = array(
				'label'       => __( 'Full name', 'woo-vietnam-shipping' ),
				'placeholder' => _x( 'Type Full name', 'placeholder', 'woo-vietnam-shipping' ),
				'required'    => true,
				'class'       => array( 'form-row-wide' ),
				'clear'       => true,
			);
		}
		if ( ! $this->get_options( 'enable_postcode' ) ) {
			unset( $address_fields['postcode'] );
		}
		$address_fields['city'] = array(
			'label'       => __( 'District', 'woo-vietnam-shipping' ),
			'type'        => 'select',
			'required'    => true,
			'class'       => array( 'form-row-wide' ),
			'priority'    => 20,
			'placeholder' => _x( 'Select District', 'placeholder', 'woo-vietnam-shipping' ),
			'options'     => array(
				'' => '',
			),
		);
		if ( ! $this->get_options() ) {
			$address_fields['address_2'] = array(
				'label'       => __( 'Commune/Ward/Town', 'woo-vietnam-shipping' ),
				'type'        => 'select',
				'class'       => array( 'form-row-wide' ),
				'priority'    => 30,
				'placeholder' => _x( 'Select Commune/Ward/Town', 'placeholder', 'woo-vietnam-shipping' ),
				'options'     => array(
					'' => '',
				),
			);
		} else {
			unset( $address_fields['address_2'] );
		}
		$address_fields['address_1']['class'] = array( 'form-row-wide' );

		return $address_fields;
	}

	public function wvs_woocommerce_admin_billing_fields( $billing_fields ) {
		global $thepostid, $post;
		$thepostid      = empty( $thepostid ) ? $post->ID : $thepostid;
		$city           = get_post_meta( $thepostid, '_billing_state', true );
		$district       = get_post_meta( $thepostid, '_billing_city', true );
		$billing_fields = array(
			'first_name' => array(
				'label' => __( 'First name', 'woocommerce' ),
				'show'  => false,
			),
			'last_name'  => array(
				'label' => __( 'Last name', 'woocommerce' ),
				'show'  => false,
			),
			'company'    => array(
				'label' => __( 'Company', 'woocommerce' ),
				'show'  => false,
			),
			'country'    => array(
				'label'   => __( 'Country', 'woocommerce' ),
				'show'    => false,
				'class'   => 'js_field-country select short',
				'type'    => 'select',
				'options' => array( '' => __( 'Select a country&hellip;', 'woocommerce' ) ) + WC()->countries->get_allowed_countries(),
			),
			'state'      => array(
				'label' => __( 'Tỉnh/thành phố', 'woocommerce' ),
				'class' => 'js_field-state select short',
				'show'  => false,
			),
			'city'       => array(
				'label'   => __( 'Quận/huyện', 'woocommerce' ),
				'class'   => 'js_field-city select short',
				'type'    => 'select',
				'show'    => false,
				'options' => array( '' => __( 'Chọn quận/huyện&hellip;', 'woocommerce' ) ) + $this->get_list_district_select( $city ),
			),
			'address_2'  => array(
				'label'   => __( 'Xã/phường/thị trấn', 'woocommerce' ),
				'show'    => false,
				'class'   => 'js_field-address_2 select short',
				'type'    => 'select',
				'options' => array( '' => __( 'Chọn xã/phường/thị trấn&hellip;', 'woocommerce' ) ) + $this->get_list_village_select( $district ),
			),
			'address_1'  => array(
				'label' => __( 'Address line 1', 'woocommerce' ),
				'show'  => false,
			),
			'email'      => array(
				'label' => __( 'Email address', 'woocommerce' ),
			),
			'phone'      => array(
				'label' => __( 'Phone', 'woocommerce' ),
			),
		);
		if ( $this->get_options() ) {
			unset( $billing_fields['address_2'] );
		}

		return $billing_fields;
	}

	public function wvs_woocommerce_admin_shipping_fields( $shipping_fields ) {
		global $thepostid, $post;
		$thepostid      = empty( $thepostid ) ? $post->ID : $thepostid;
		$city           = get_post_meta( $thepostid, '_shipping_state', true );
		$district       = get_post_meta( $thepostid, '_shipping_city', true );
		$billing_fields = array(
			'first_name' => array(
				'label' => __( 'First name', 'woocommerce' ),
				'show'  => false,
			),
			'last_name'  => array(
				'label' => __( 'Last name', 'woocommerce' ),
				'show'  => false,
			),
			'company'    => array(
				'label' => __( 'Company', 'woocommerce' ),
				'show'  => false,
			),
			'country'    => array(
				'label'   => __( 'Country', 'woocommerce' ),
				'show'    => false,
				'type'    => 'select',
				'class'   => 'js_field-country select short',
				'options' => array( '' => __( 'Select a country&hellip;', 'woocommerce' ) ) + WC()->countries->get_shipping_countries(),
			),
			'state'      => array(
				'label' => __( 'Tỉnh/thành phố', 'woocommerce' ),
				'class' => 'js_field-state select short',
				'show'  => false,
			),
			'city'       => array(
				'label'   => __( 'Quận/huyện', 'woocommerce' ),
				'class'   => 'js_field-city select short',
				'type'    => 'select',
				'show'    => false,
				'options' => array( '' => __( 'Chọn quận/huyện&hellip;', 'woocommerce' ) ) + $this->get_list_district_select( $city ),
			),
			'address_2'  => array(
				'label'   => __( 'Xã/phường/thị trấn', 'woocommerce' ),
				'show'    => false,
				'class'   => 'js_field-address_2 select short',
				'type'    => 'select',
				'options' => array( '' => __( 'Chọn xã/phường/thị trấn&hellip;', 'woocommerce' ) ) + $this->get_list_village_select( $district ),
			),
			'address_1'  => array(
				'label' => __( 'Address line 1', 'woocommerce' ),
				'show'  => false,
			),
		);
		if ( $this->get_options() ) {
			unset( $billing_fields['address_2'] );
		}

		return $billing_fields;
	}

	public function wvs_woocommerce_form_field_select( $field, $key, $args, $value ) {
		if ( in_array( $key, array( 'billing_city', 'shipping_city', 'billing_address_2', 'shipping_address_2' ) ) ) {
			if ( in_array( $key, array( 'billing_city', 'shipping_city' ) ) ) {
				if ( ! is_checkout() && is_user_logged_in() ) {
					if ( 'billing_city' === $key ) {
						$state = wc_get_post_data_by_key( 'billing_state', get_user_meta( get_current_user_id(), 'billing_state', true ) );
					} else {
						$state = wc_get_post_data_by_key( 'shipping_state', get_user_meta( get_current_user_id(), 'shipping_state', true ) );
					}
				} else {
					$state = WC()->checkout->get_value( 'billing_city' === $key ? 'billing_state' : 'shipping_state' );
				}
				$city            = array( '' => ( $args['placeholder'] ) ? $args['placeholder'] : __( 'Choose an option', 'woocommerce' ) ) + $this->get_list_district_select( $state );
				$args['options'] = $city;
			} elseif ( in_array( $key, array( 'billing_address_2', 'shipping_address_2' ) ) ) {
				if ( ! is_checkout() && is_user_logged_in() ) {
					if ( 'billing_address_2' === $key ) {
						$city = wc_get_post_data_by_key( 'billing_city', get_user_meta( get_current_user_id(), 'billing_city', true ) );
					} else {
						$city = wc_get_post_data_by_key( 'shipping_city', get_user_meta( get_current_user_id(), 'shipping_city', true ) );
					}
				} else {
					$city = WC()->checkout->get_value( 'billing_address_2' === $key ? 'billing_city' : 'shipping_city' );
				}
				$village         = array( '' => ( $args['placeholder'] ) ? $args['placeholder'] : __( 'Choose an option', 'woocommerce' ) ) + $this->get_list_village_select( $city );
				$args['options'] = $village;
			}

			if ( $args['required'] ) {
				$args['class'][] = 'validate-required';
				$required        = ' <abbr class="required" title="' . esc_attr__( 'required', 'woocommerce' ) . '">*</abbr>';
			} else {
				$required = '';
			}

			if ( is_string( $args['label_class'] ) ) {
				$args['label_class'] = array( $args['label_class'] );
			}

			// Custom attribute handling.
			$custom_attributes         = array();
			$args['custom_attributes'] = array_filter( (array) $args['custom_attributes'], 'strlen' );

			if ( $args['maxlength'] ) {
				$args['custom_attributes']['maxlength'] = absint( $args['maxlength'] );
			}

			if ( ! empty( $args['autocomplete'] ) ) {
				$args['custom_attributes']['autocomplete'] = $args['autocomplete'];
			}

			if ( true === $args['autofocus'] ) {
				$args['custom_attributes']['autofocus'] = 'autofocus';
			}

			if ( ! empty( $args['custom_attributes'] ) && is_array( $args['custom_attributes'] ) ) {
				foreach ( $args['custom_attributes'] as $attribute => $attribute_value ) {
					$custom_attributes[] = esc_attr( $attribute ) . '="' . esc_attr( $attribute_value ) . '"';
				}
			}

			if ( ! empty( $args['validate'] ) ) {
				foreach ( $args['validate'] as $validate ) {
					$args['class'][] = 'validate-' . $validate;
				}
			}

			$label_id        = $args['id'];
			$sort            = $args['priority'] ? $args['priority'] : '';
			$field_container = '<p class="form-row %1$s" id="%2$s" data-priority="' . esc_attr( $sort ) . '">%3$s</p>';

			$options = $field = '';

			if ( ! empty( $args['options'] ) ) {
				foreach ( $args['options'] as $option_key => $option_text ) {
					if ( '' === $option_key ) {
						// If we have a blank option, select2 needs a placeholder.
						if ( empty( $args['placeholder'] ) ) {
							$args['placeholder'] = $option_text ? $option_text : __( 'Choose an option', 'woocommerce' );
						}
						$custom_attributes[] = 'data-allow_clear="true"';
					}
					$options .= '<option value="' . esc_attr( $option_key ) . '" ' . selected( $value, $option_key, false ) . '>' . esc_attr( $option_text ) . '</option>';
				}

				$field .= '<select name="' . esc_attr( $key ) . '" id="' . esc_attr( $args['id'] ) . '" class="select ' . esc_attr( implode( ' ', $args['input_class'] ) ) . '" ' . implode( ' ', $custom_attributes ) . ' data-placeholder="' . esc_attr( $args['placeholder'] ) . '">
			' . $options . '
		</select>';
			}

			if ( ! empty( $field ) ) {
				$field_html = '';

				if ( $args['label'] && 'checkbox' != $args['type'] ) {
					$field_html .= '<label for="' . esc_attr( $label_id ) . '" class="' . esc_attr( implode( ' ', $args['label_class'] ) ) . '">' . $args['label'] . $required . '</label>';
				}

				$field_html .= $field;

				if ( $args['description'] ) {
					$field_html .= '<span class="description">' . esc_html( $args['description'] ) . '</span>';
				}

				$container_class = esc_attr( implode( ' ', $args['class'] ) );
				$container_id    = esc_attr( $args['id'] ) . '_field';
				$field           = sprintf( $field_container, $container_class, $container_id, $field_html );
			}

			return $field;
		}

		return $field;
	}

	public function convert_weight_to_kg( $weight ) {
		switch ( get_option( 'woocommerce_weight_unit' ) ) {
			case 'g':
				$weight = $weight * 0.001;
				break;
			case 'lbs':
				$weight = $weight * 0.45359237;
				break;
			case 'oz':
				$weight = $weight * 0.02834952;
				break;
		}

		return $weight; // return kg
	}

	public function convert_dimension_to_cm( $dimension ) {
		switch ( get_option( 'woocommerce_dimension_unit' ) ) {
			case 'm':
				$dimension = $dimension * 100;
				break;
			case 'mm':
				$dimension = $dimension * 0.1;
				break;
			case 'in':
				$dimension = $dimension * 2.54;
				// no break
			case 'yd':
				$dimension = $dimension * 91.44;
				break;
		}

		return $dimension; // return cm
	}

	public function wvs_woocommerce_get_order_address( $value, $type ) {
		if ( $type == 'billing' || $type == 'shipping' ) {
			if ( isset( $value['state'] ) && $value['state'] ) {
				$state          = $value['state'];
				$value['state'] = $this->get_name_city( $state );
			}
			if ( isset( $value['city'] ) && $value['city'] ) {
				$city          = $value['city'];
				$value['city'] = $this->get_name_district( $city );
			}
			if ( isset( $value['address_2'] ) && $value['address_2'] ) {
				$address_2          = $value['address_2'];
				$value['address_2'] = $this->get_name_village( $address_2 );
			}
		}

		return $value;
	}

	public function wvs_woocommerce_rest_prepare_shop_order_object( $response, $order, $request ) {
		if ( empty( $response->data ) ) {
			return $response;
		}

		$fields = array(
			'billing',
			'shipping',
		);

		foreach ( $fields as $field ) {
			if ( isset( $response->data[ $field ]['state'] ) && $response->data[ $field ]['state'] ) {
				$state                             = $response->data[ $field ]['state'];
				$response->data[ $field ]['state'] = $this->get_name_city( $state );
			}

			if ( isset( $response->data[ $field ]['city'] ) && $response->data[ $field ]['city'] ) {
				$city                             = $response->data[ $field ]['city'];
				$response->data[ $field ]['city'] = $this->get_name_district( $city );
			}

			if ( isset( $response->data[ $field ]['address_2'] ) && $response->data[ $field ]['address_2'] ) {
				$address_2                             = $response->data[ $field ]['address_2'];
				$response->data[ $field ]['address_2'] = $this->get_name_village( $address_2 );
			}
		}

		return $response;
	}

	public function wvs_woocommerce_api_order_response( $order_data, $order ) {
		if ( isset( $order_data['customer'] ) ) {
			// billing
			if ( isset( $order_data['customer']['billing_address']['city'] ) && $order_data['customer']['billing_address']['city'] ) {
				$order_data['customer']['billing_address']['city'] = $this->get_name_district( $order_data['customer']['billing_address']['city'] );
			}
			if ( isset( $order_data['customer']['billing_address']['address_2'] ) && $order_data['customer']['billing_address']['address_2'] ) {
				$order_data['customer']['billing_address']['address_2'] = $this->get_name_village( $order_data['customer']['billing_address']['address_2'] );
			}

			// shipping
			if ( isset( $order_data['customer']['shipping_address']['city'] ) && $order_data['customer']['shipping_address']['city'] ) {
				$order_data['customer']['shipping_address']['city'] = $this->get_name_district( $order_data['customer']['shipping_address']['city'] );
			}
			if ( isset( $order_data['customer']['shipping_address']['address_2'] ) && $order_data['customer']['shipping_address']['address_2'] ) {
				$order_data['customer']['shipping_address']['address_2'] = $this->get_name_village( $order_data['customer']['shipping_address']['address_2'] );
			}
		}

		return $order_data;
	}

	public function wvs_modify_plugin_update_message( $plugin_data, $response ) {
		$license_key = sanitize_text_field( $this->get_options( 'license_key' ) );
		if ( $license_key && isset( $plugin_data['package'] ) && $plugin_data['package'] ) {
			return;
		}
		$PluginURI = isset( $plugin_data['PluginURI'] ) ? $plugin_data['PluginURI'] : '';
		echo '<br />' . sprintf( __( '<strong>Mua bản quyền để được tự động update. <a href="%1$s" target="_blank">Xem thêm thông tin mua bản quyền</a></strong> hoặc liên hệ mua trực tiếp qua <a href="%2$s" target="_blank">facebook</a>', 'woo-vietnam-shipping' ), $PluginURI, 'http://m.me/levantoan.wp' );
	}

	public function wvs_woocommerce_formatted_address_replacements( $replace ) {
		if ( isset( $replace['{city}'] ) && is_numeric( $replace['{city}'] ) ) {
			$oldCity           = isset( $replace['{city}'] ) ? $replace['{city}'] : '';
			$replace['{city}'] = $this->get_name_district( $oldCity );
		}

		if ( isset( $replace['{city_upper}'] ) && is_numeric( $replace['{city_upper}'] ) ) {
			$oldCityUpper            = isset( $replace['{city_upper}'] ) ? $replace['{city_upper}'] : '';
			$replace['{city_upper}'] = strtoupper( $this->get_name_district( $oldCityUpper ) );
		}

		if ( isset( $replace['{address_2}'] ) && is_numeric( $replace['{address_2}'] ) ) {
			$oldCity                = isset( $replace['{address_2}'] ) ? $replace['{address_2}'] : '';
			$replace['{address_2}'] = $this->get_name_village( $oldCity );
		}

		if ( isset( $replace['{address_2_upper}'] ) && is_numeric( $replace['{address_2_upper}'] ) ) {
			$oldCityUpper                 = isset( $replace['{address_2_upper}'] ) ? $replace['{address_2_upper}'] : '';
			$replace['{address_2_upper}'] = strtoupper( $this->get_name_village( $oldCityUpper ) );
		}

		if ( is_cart() && ! is_checkout() ) {
			$replace['{address_1}']       = '';
			$replace['{address_1_upper}'] = '';
			$replace['{address_2}']       = '';
			$replace['{address_2_upper}'] = '';
		}

		return $replace;
	}

	public function save_shipping_phone_meta( $post_id, $post, $update ) {
		$post_type = get_post_type( $post_id );
		if ( 'shop_order' != $post_type ) {
			return;
		}
		if ( isset( $_POST['_shipping_phone'] ) ) {
			update_post_meta( $post_id, '_shipping_phone', sanitize_text_field( $_POST['_shipping_phone'] ) );
		}
	}

	public function remove_http( $url ) {
		$disallowed = array( 'http://', 'https://', 'https://www.', 'http://www.' );
		foreach ( $disallowed as $d ) {
			if ( strpos( $url, $d ) === 0 ) {
				return str_replace( $d, '', $url );
			}
		}

		return $url;
	}
}

function woo_vietnam_shipping() {
	return Woo_Vietnam_Shipping::instance();
}

woo_vietnam_shipping();

require_once 'includes/admin-order-functions.php';


function wvs_shipping_method() {
	class Wvs_Shipping_Method extends WC_Shipping_Method {

		/**
		 * Constructor for your shipping class
		 *
		 * @access public
		 * @return void
		 */
		public function __construct( $instance_id = 0 ) {
			$this->id                 = 'wvs';
			$this->instance_id        = absint( $instance_id );
			$this->method_title       = __( 'WVS Shipping', 'tutsplus' );
			$this->method_description = __( 'Custom Shipping Method for WVS', 'tutsplus' );
			$this->supports           = array(
				'shipping-zones',
				'instance-settings',
				'instance-settings-modal',
			);

			$this->init();
		}

		/**
		 * Init your settings
		 *
		 * @access public
		 * @return void
		 */
		function init() {
			// Load the settings API
			$this->init_form_fields();
			$this->init_settings();

			// Define user set variables.
			$this->title            = $this->get_option( 'title' );
			$this->min_amount       = $this->get_option( 'min_amount', 0 );
			$this->requires         = $this->get_option( 'requires' );
			$this->ignore_discounts = $this->get_option( 'ignore_discounts' );

			// Actions.
			add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
			add_action( 'admin_footer', array( $this, 'enqueue_admin_js' ), 10 ); // Priority needs to be higher than wc_print_js (25).
		}

		/**
		 * Define settings field for this shipping
		 *
		 * @return void
		 */
		function init_form_fields() {
			global $tinh_thanhpho;

			$this->instance_form_fields = array(
				'title'            => array(
					'title'       => __( 'Title', 'woocommerce' ),
					'type'        => 'text',
					'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
					'default'     => 'Free Shipping',
					'desc_tip'    => true,
				),
				'requires'         => array(
					'title'   => __( 'Free shipping requires...', 'woocommerce' ),
					'type'    => 'select',
					'class'   => 'wc-enhanced-select',
					'default' => '',
					'options' => array(
						''           => __( 'N/A', 'woocommerce' ),
						'coupon'     => __( 'A valid free shipping coupon', 'woocommerce' ),
						'min_amount' => __( 'A minimum order amount', 'woocommerce' ),
						'either'     => __( 'A minimum order amount OR a coupon', 'woocommerce' ),
						'both'       => __( 'A minimum order amount AND a coupon', 'woocommerce' ),
					),
				),
				'state'         => array(
					'title'   => __( 'State', 'woocommerce' ),
					'type'    => 'multiselect',
					'class'   => 'wvs-select2',
					'default' => '',
					'options' => $tinh_thanhpho,
				),
				'district'         => array(
					'title'   => __( 'District', 'woocommerce' ),
					'type'    => 'multiselect',
					'class'   => 'wvs-select2',
					'default' => '',
					// 'options' => woo_vietnam_shipping()->get_list_district_selects( $this->get_instance_option( 'state' ) ),
				),
				'village'         => array(
					'title'   => __( 'Village', 'woocommerce' ),
					'type'    => 'multiselect',
					'class'   => 'wvs-select2',
					'default' => '',
					// 'options' => woo_vietnam_shipping()->get_list_village_selects( $this->get_instance_option( 'district' ) ),
				),
				'min_amount'       => array(
					'title'       => __( 'Minimum order amount', 'woocommerce' ),
					'type'        => 'price',
					'placeholder' => wc_format_localized_price( 0 ),
					'description' => __( 'Users will need to spend this amount to get free shipping (if enabled above).', 'woocommerce' ),
					'default'     => '0',
					'desc_tip'    => true,
				),
				'ignore_discounts' => array(
					'title'       => __( 'Coupons discounts', 'woocommerce' ),
					'label'       => __( 'Apply minimum order rule before coupon discount', 'woocommerce' ),
					'type'        => 'checkbox',
					'description' => __( 'If checked, free shipping would be available based on pre-discount order amount.', 'woocommerce' ),
					'default'     => 'no',
					'desc_tip'    => true,
				),
			);

			$this->instance_form_fields['district']['options'] = woo_vietnam_shipping()->get_list_district_selects( $this->get_instance_option( 'state' ) );
			$this->instance_form_fields['village']['options'] = woo_vietnam_shipping()->get_list_village_selects( $this->get_instance_option( 'district' ) );
		}

		/**
		 * Get setting form fields for instances of this shipping method within zones.
		 *
		 * @return array
		 */
		public function get_instance_form_fields() {
			return parent::get_instance_form_fields();
		}

		/**
		 * See if free shipping is available based on the package and cart.
		 *
		 * @param array $package Shipping package.
		 * @return bool
		 */
		public function is_available( $package ) {
			$has_coupon         = false;
			$has_met_min_amount = false;

			if ( in_array( $this->requires, array( 'coupon', 'either', 'both' ), true ) ) {
				$coupons = WC()->cart->get_coupons();

				if ( $coupons ) {
					foreach ( $coupons as $code => $coupon ) {
						if ( $coupon->is_valid() && $coupon->get_free_shipping() ) {
							$has_coupon = true;
							break;
						}
					}
				}
			}

			if ( in_array( $this->requires, array( 'min_amount', 'either', 'both' ), true ) ) {
				$total = WC()->cart->get_displayed_subtotal();

				if ( WC()->cart->display_prices_including_tax() ) {
					$total = $total - WC()->cart->get_discount_tax();
				}

				if ( 'no' === $this->ignore_discounts ) {
					$total = $total - WC()->cart->get_discount_total();
				}

				$total = \Automattic\WooCommerce\Utilities\NumberUtil::round( $total, wc_get_price_decimals() );

				if ( $total >= $this->min_amount ) {
					$has_met_min_amount = true;
				}
			}

			switch ( $this->requires ) {
				case 'min_amount':
					$is_available = $has_met_min_amount;
					break;
				case 'coupon':
					$is_available = $has_coupon;
					break;
				case 'both':
					$is_available = $has_met_min_amount && $has_coupon;
					break;
				case 'either':

					if ( in_array( WC()->customer->get_billing_state(), $this->get_option('state') ) && in_array( WC()->customer->get_billing_city(), $this->get_option('district') ) && in_array( WC()->customer->get_billing_address_2(), $this->get_option('village') ) ) {
						$is_available = $has_met_min_amount || $has_coupon;
					} else {
						$is_available = false;
					}
					break;
				default:
					$is_available = true;
					break;
			}

			return apply_filters( 'woocommerce_shipping_' . $this->id . '_is_available', $is_available, $package, $this );
		}

		/**
		 * Called to calculate shipping rates for this method. Rates can be added using the add_rate() method.
		 *
		 * @uses WC_Shipping_Method::add_rate()
		 *
		 * @param array $package Shipping package.
		 */
		public function calculate_shipping( $package = array() ) {
			$this->add_rate(
				array(
					'label'   => $this->title,
					'cost'    => 0,
					'taxes'   => false,
					'package' => $package,
				)
			);
		}

		/**
		 * Enqueue JS to handle free shipping options.
		 *
		 * Static so that's enqueued only once.
		 */
		public static function enqueue_admin_js() {
			wc_enqueue_js(
				"jQuery( function( $ ) {
					function wcFreeShippingShowHideMinAmountField( el ) {
						var form = $( el ).closest( 'form' );
						var minAmountField = $( '#woocommerce_wvs_min_amount', form ).closest( 'tr' );
						var ignoreDiscountField = $( '#woocommerce_wvs_ignore_discounts', form ).closest( 'tr' );
						if ( 'coupon' === $( el ).val() || '' === $( el ).val() ) {
							minAmountField.hide();
							ignoreDiscountField.hide();
						} else {
							minAmountField.show();
							ignoreDiscountField.show();
						}
					}

					// $('.wvs-select2').select2();

					$( document.body ).on( 'change', '#woocommerce_wvs_requires', function() {
						wcFreeShippingShowHideMinAmountField( this );
					});

					// Change while load.
					$( '#woocommerce_wvs_requires' ).trigger( 'change' );
					$( document.body ).on( 'wc_backbone_modal_loaded', function( evt, target ) {
						if ( 'wc-modal-shipping-method-settings' === target ) {
							$('.wvs-select2').select2();
							wcFreeShippingShowHideMinAmountField( $( '#wc-backbone-modal-dialog #woocommerce_wvs_requires', evt.currentTarget ) );
						}
					} );
				});"
			);
		}
	}
}
add_action( 'woocommerce_shipping_init', 'wvs_shipping_method' );

function add_wvs_shipping_method( $methods ) {
	$methods['wvs'] = 'Wvs_Shipping_Method';
	return $methods;
}
add_filter( 'woocommerce_shipping_methods', 'add_wvs_shipping_method' );
