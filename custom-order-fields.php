<?php

/**
 * Register Taxonomies
*/
function fp_wc_register_tax() {
  $args = array(
    "labels" => array(
      "name" => "Doctors",
      "label" => "Doctors",
    ),
    "hierarchical" => false,
    "show_ui" => true,
    "query_var" => false,
    "rewrite" => array( 'slug' => 'doctor', 'with_front' => false ),
    "show_admin_column" => false,
  );
  register_taxonomy( "doctor", array("shop_order"), $args );
}
add_action( 'init', 'fp_wc_register_tax' );

/**
 * Add doctors sub page
 */
function fp_register_doctors_sub_page() {
  add_submenu_page( 'woocommerce', 'Doctors', 'Doctors', 'edit_others_posts', 'edit-tags.php?taxonomy=doctor&post_type=shop_order');
}
add_action( 'admin_menu', 'fp_register_doctors_sub_page' );

/**
 * Highlight the proper top level menu
 */
function fp_manually_highlight_sub_page() {
  if( 'doctor' == $_GET['taxonomy'] ) { ?>
    <script type="text/javascript">
      jQuery(document).ready( function($) {
        var url = 'edit-tags.php?taxonomy=doctor&post_type=shop_order';
        var link = $('a[href="'+url+'"]');
        link.addClass('current');
        link.parent().addClass('current');
      });     
    </script>
    <?php
  }
}
add_action( 'admin_head-edit-tags.php', 'fp_manually_highlight_sub_page' );

/**
 * Save doctor taxonomy to order
 * woocommerce_order_status_completed, woocommerce_order_status_pending
 */
function fp_add_taxonomy_to_order($order_id) {
  $doctor = get_post_meta( $order_id, '_billing_doctor', true );
  if(!empty($doctor)) {
    wp_set_object_terms( $order_id, $doctor, 'doctor', false );
  }
  return $order_id;
}
add_action('woocommerce_order_status_processing', 'fp_add_taxonomy_to_order', 10, 1);

/**
 * Get doctors from custom fields
 */
function get_fp_doctors() {
  $doctors = get_terms( 'doctor', array( 'hide_empty' => false ) );
  $options = array('' => 'If yes please select your doctor...');
  foreach ( $doctors as $doctor) {
    $options[$doctor->name] = $doctor->name;
  }
  return $options;
}

/**
 * Add custom field to checkout
 * https://docs.woocommerce.com/document/tutorial-customising-checkout-fields-using-actions-and-filters/
 * $fields['shipping'], $fields['billing'], $fields['account'], $fields['order']
 */
function fp_override_checkout_fields( $fields ) {
  $fields['billing']['billing_doctor'] = array(
    'type'      => 'select',
    'label'     => __('Were you referred by a doctor?', 'woocommerce'),
    'required'  => false,
    'class'     => array('form-row-wide'),
    'clear'     => true
  );
  $fields['billing']['billing_doctor']['options'] = get_fp_doctors();
  return $fields;
}
add_filter( 'woocommerce_checkout_fields' , 'fp_override_checkout_fields' );

/**
 * Display field value on profile page
 */
function fp_override_customer_meta_fields( $fields ) {
  $fields['billing']['fields']['billing_doctor'] = array(
    'label'       => __('Referred By', 'woocommerce'),
    'description' => '',
    'type'        => 'select',
    'options'     => get_fp_doctors()
  );
  return $fields;
}
add_filter( 'woocommerce_customer_meta_fields' , 'fp_override_customer_meta_fields' );

/**
 * Display field value on the order edit page
 */
function fp_checkout_field_display_admin_order_meta($order) {
  // var_dump(get_post_custom($order->id));
  $doctor = get_post_meta( $order->id, '_billing_doctor', true );
  if(!empty($doctor)):
    echo '<div class="address"><p><strong>'.__('Referred By').':</strong>'.$doctor.'</p></div>';
  endif;
}
add_action( 'woocommerce_admin_order_data_after_shipping_address', 'fp_checkout_field_display_admin_order_meta', 10, 1 );

/**
 * Display field in email customer details
 */
function fp_email_customer_details_fields( $fields, $sent_to_admin, $order ) {
  $doctor = get_post_meta( $order->id, '_billing_doctor', true );
  if(!empty($doctor)):
    $fields['billing_doctor'] = array('label' => 'Reffered By', 'value' => $doctor );
  endif;
  return $fields;
}
add_action('woocommerce_email_customer_details_fields', 'fp_email_customer_details_fields', 10, 1 );

/**
 * Display field in Order Page customer details
 */
function fp_order_details_after_customer_details($order) {
  // var_dump($order);
  $doctor = get_post_meta( $order->id, '_billing_doctor', true );
  if(!empty($doctor)):
    echo '<tr><th>'.__('Referred By').':</th><td>'.$doctor.'</td></tr>';
  endif;
}
add_action('woocommerce_order_details_after_customer_details', 'fp_order_details_after_customer_details', 10, 1);

/**
 * Customize the field order
 */
function fp_field_order($fields) {
  $order = array(
    'billing_doctor',
    'billing_first_name',
    'billing_last_name',
    'billing_company',
    'billing_email',
    'billing_phone',
    'billing_address_1',
    'billing_address_2',
    'billing_city',
    'billing_postcode',
    'billing_country',
    'billing_state'
  );
  foreach($order as $field) {
    $ordered_fields[$field] = $fields['billing'][$field];
  }
  $fields['billing'] = $ordered_fields;
  return $fields;
}
add_filter('woocommerce_checkout_fields', 'fp_field_order');