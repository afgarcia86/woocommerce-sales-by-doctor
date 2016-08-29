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

// Including base class
if ( ! class_exists( 'WC_Report_Sales_By_Doctor' ) )
	require_once plugin_dir_path( __FILE__ ) . 'class-wc-report-sales-by-doctor.php';

// Whether plugin active or not
if ( is_plugin_active( 'woocommerce/woocommerce.php' ) ) :

	// The object
	$sbd = new WC_Report_Sales_By_Doctor();

	if ( ! $sbd->is_wc_old() ) :

		// Taxonomy & Field set up
		require_once plugin_dir_path( __FILE__ ) . 'custom-order-fields.php';

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
				'callback'    => 'wc_doctor_sales'
			);
			return $reports;
		}
		add_filter( 'woocommerce_admin_reports', 'sales_by_doctor' );


		/**
		 * Function to hook into WooCommerce
		 * 
		 * @return string
		 */
		function wc_doctor_sales() {
			global $sbd;
			$current_range = ! empty( $_GET['range'] ) ? sanitize_text_field( $_GET['range'] ) : '7day';
			?>
			<div id="poststuff" class="woocommerce-reports-wide">
				<div class="postbox">
					<div class="stats_range">
				    <?php $sbd->get_export_button(); ?>
			    	<ul>
							<li class="<?php echo($current_range ==  'year' ? 'active':'');?>">
								<a href="/wp-admin/admin.php?page=wc-reports&amp;tab=orders&amp;report=sales_by_doctor&amp;doctor=<?php echo $_GET['doctor']; ?>&amp;range=year">Year</a>
							</li>
							<li class="<?php echo($current_range ==  'last_month' ? 'active':'');?>">
								<a href="/wp-admin/admin.php?page=wc-reports&amp;tab=orders&amp;report=sales_by_doctor&amp;doctor=<?php echo $_GET['doctor']; ?>&amp;range=last_month">Last Month</a>
							</li>
							<li class="<?php echo($current_range ==  'month' ? 'active':'');?>">
								<a href="/wp-admin/admin.php?page=wc-reports&amp;tab=orders&amp;report=sales_by_doctor&amp;doctor=<?php echo $_GET['doctor']; ?>&amp;range=month">This Month</a>
							</li>
							<li class="<?php echo($current_range ==  '7day' ? 'active':'');?>">
								<a href="/wp-admin/admin.php?page=wc-reports&amp;tab=orders&amp;report=sales_by_doctor&amp;doctor=<?php echo $_GET['doctor']; ?>&amp;range=7day">Last 7 Days</a>
							</li>
							<li class="<?php echo($current_range ==  'custom' ? 'active custom':'custom');?>">
								Custom: 
								<form method="get">
									<div>
										<input type="hidden" value="wc-reports" name="page">
										<input type="hidden" value="orders" name="tab">
										<input type="hidden" value="sales_by_doctor" name="report">
										<input type="hidden" value="custom" name="range">
										<input type="hidden" name="doctor" value="<?php if ( ! empty( $_GET['doctor'] ) ) echo esc_attr( $_GET['doctor'] ); ?>">
										<input type="text" size="9" placeholder="yyyy-mm-dd" name="start_date" class="range_datepicker from" value="<?php if ( ! empty( $_GET['start_date'] ) ) echo esc_attr( $_GET['start_date'] ); ?>" >
										<input type="text" size="9" placeholder="yyyy-mm-dd" name="end_date" class="range_datepicker to" value="<?php if ( ! empty( $_GET['end_date'] ) ) echo esc_attr( $_GET['end_date'] ); ?>">
										<input type="submit" value="Go" class="button">
									</div>
								</form>
							</li>
						</ul>
					</div>
					<div class="inside chart-with-sidebar">
						<div class="chart-sidebar">
							<?php $sbd->get_chart_legend(); ?>
							<ul class="chart-widgets">
								<?php $sbd->reset_widget(); ?>
  							<?php $sbd->doctors_widget(); ?>
							</ul>
						</div>
						<div class="main">
							<?php $sbd->get_main_chart(); ?>
						</div>
					</div>
				</div>
			</div>

	<?php

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
