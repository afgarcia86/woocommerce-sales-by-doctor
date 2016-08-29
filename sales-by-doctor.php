<?php
/**
 * Plugin Name: WooCommerce Sales by Doctor
 * Plugin URI: https://github.com/afgarcia86/woocommerce-sales-by-doctor
 * Description: Adds a report page to display doctor specific product sales.
 * Version: 1.0
 * Author: AndresTheGiant
 * Author URI: http://andresthegiant.com
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * ----------------------------------------------------------------------
 * Copyright (C) 2016  AndresTheGiant (Email: hello@andresthegiant.com)
 * ----------------------------------------------------------------------
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 * ----------------------------------------------------------------------
 */


// Including WP core file
if ( ! function_exists( 'get_plugins' ) )
	require_once ABSPATH . 'wp-admin/includes/plugin.php';

// Whether plugin active or not
if ( is_plugin_active( 'woocommerce/woocommerce.php' ) ) :

	if ( ! is_wc_old() ) :

		// Taxonomy & Field set up
		require_once plugin_dir_path( __FILE__ ) . 'doctor-taxonomy-and-order-meta.php';

		/**
		 * WooCommerce hook
		 *
		 * @param array   $reports
		 * @return array
		 */
		function sales_by_doctor( $reports ) {
			$reports['orders']['reports']['sales_by_doctor'] = array(
				'title'       => 'Sales by doctor',
				'description' => '',
				'hide_title'  => true,
				'callback'    => 'get_report'
			);
			return $reports;
		}
		add_filter( 'woocommerce_admin_reports', 'sales_by_doctor' );


		/**
		 * Get report.
		 */
		function get_report() {
			// Including Admin Report Class
			if ( ! class_exists( 'WC_Admin_Report' ) )
			  require_once plugin_dir_path( __FILE__ ). '../woocommerce/includes/admin/reports/class-wc-admin-report.php';
			// Including Admin Report Class
			if ( ! class_exists( 'WC_Report_Sales_By_Doctor' ) )
				require_once plugin_dir_path( __FILE__ ) . 'class-wc-report-sales-by-doctor.php';

			$sbd = new WC_Report_Sales_By_Doctor();
			$sbd->output_report();
		}

	else :

		/**
		 * WooCommerce warning message
		 * @desc If WC too old, getting an warning message
		 * 
		 * @return string
		 */
		function wsc_warning() {
			global $current_screen;
			echo '<div class="error"><p>Your <strong>WooCommerce</strong> version is too old. Please update to latest version.</p></div>';
		}
		add_action( 'admin_notices', 'wsc_warning' );

	endif;

else :

	/**
	 * Getting notice if WooCommerce not active
	 * 
	 * @return string
	 */
	function wsc_notice() {
		global $current_screen;
		if ( $current_screen->parent_base == 'plugins' ) {
			echo '<div class="error"><p>'.__( 'The <strong>WooCommerce Sales by Doctor</strong> plugin requires the <a href="http://wordpress.org/plugins/woocommerce" target="_blank">WooCommerce</a> plugin to be activated in order to work. Please <a href="'.admin_url( 'plugin-install.php?tab=search&type=term&s=WooCommerce' ).'" target="_blank">install WooCommerce</a> or <a href="'.admin_url( 'plugins.php' ).'">activate</a> first.' ).'</p></div>';
		}
	}
	add_action( 'admin_notices', 'wsc_notice' );

endif;


function is_wc_old() {
  global $woocommerce;

  $plugin_folder = get_plugins( '/' . 'woocommerce' );
  $plugin_file   = 'woocommerce.php';
  $wc_version    = $plugin_folder[$plugin_file]['Version'];
  $wc_version    = isset( $wc_version ) ? $wc_version : $woocommerce->version;

  return version_compare( $wc_version, '2.1', 'lt' );
}
