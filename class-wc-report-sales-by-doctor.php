<?php

if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}

/**
 * WC_Report_Sales_By_Doctor
 *
 * @author      WooThemes
 * @category    Admin
 * @package     WooCommerce Sales by Doctor
 * @version     1.0
 */

class WC_Report_Sales_By_Doctor extends WC_Admin_Report {

  /**
   * Chart colours.
   *
   * @var array
   */
  public $chart_colours      = array();

  /**
   * Doctor Name.
   *
   * @var array
   */
  public $doctor        = '';

  /**
   * Constructor.
   */
  public function __construct() {
    if ( isset( $_GET['doctor'] ) ) {
      $this->doctor = sanitize_text_field( $_GET['doctor'] );
    }
  }

  /**
   * Get the legend for the main chart sidebar.
   * @return array
   */
  public function get_chart_legend() {

    if ( empty( $this->doctor ) ) {
      return array();
    }

    $legend   = array();

    $total_sales = $this->get_order_report_data( array(
      'data' => array(
        '_line_total' => array(
          'type'            => 'order_item_meta',
          'order_item_type' => 'line_item',
          'function' => 'SUM',
          'name'     => 'order_item_amount'
        )
      ),
      'where_meta' => array(
        'relation' => 'OR',
        array(
          'meta_key'   => '_billing_doctor',
          'meta_value' => $this->doctor,
          'operator'   => 'IN'
        )
      ),
      'query_type'   => 'get_var',
      'filter_range' => true
    ) );

    $total_items = absint( $this->get_order_report_data( array(
      'data' => array(
        '_qty' => array(
          'type'            => 'order_item_meta',
          'order_item_type' => 'line_item',
          'function'        => 'SUM',
          'name'            => 'order_item_count'
        )
      ),
      'where_meta' => array(
        'relation' => 'OR',
        array(
          'meta_key'   => '_billing_doctor',
          'meta_value' => $this->doctor,
          'operator'   => 'IN'
        )
      ),
      'query_type'   => 'get_var',
      'filter_range' => true
    ) ) );

    $legend[] = array(
      'title' => sprintf( __( '%s sales for the selected doctor', 'woocommerce' ), '<strong>' . wc_price( $total_sales ) . '</strong>' ),
      'color' => $this->chart_colours['sales_amount'],
      'highlight_series' => 1
    );

    $legend[] = array(
      'title' => sprintf( __( '%s purchases for the selected doctor', 'woocommerce' ), '<strong>' . ( $total_items ) . '</strong>' ),
      'color' => $this->chart_colours['item_count'],
      'highlight_series' => 0
    );

    return $legend;
  }

  /**
   * Output the report.
   */
  public function output_report() {

    $ranges = array(
      'year'         => __( 'Year', 'woocommerce' ),
      'last_month'   => __( 'Last Month', 'woocommerce' ),
      'month'        => __( 'This Month', 'woocommerce' ),
      '7day'         => __( 'Last 7 Days', 'woocommerce' )
    );

    $this->chart_colours = array(
      'sales_amount' => '#3498db',
      'item_count'   => '#d4d9dc',
    );

    $current_range = ! empty( $_GET['range'] ) ? sanitize_text_field( $_GET['range'] ) : '7day';

    if ( ! in_array( $current_range, array( 'custom', 'year', 'last_month', 'month', '7day' ) ) )
      $current_range = '7day';

    $this->calculate_current_range( $current_range );

    include( WC()->plugin_path() . '/includes/admin/views/html-report-by-date.php');
  }

  /**
   * Get chart widgets.
   *
   * @return array
   */
  public function get_chart_widgets() {

    $widgets = array();

    if ( ! empty( $this->doctor ) ) {
      $widgets[] = array(
        'title'    => __( 'Showing reports for:', 'woocommerce' ),
        'callback' => array( $this, 'current_filters' )
      );
    }

    $widgets[] = array(
      'title'    => '',
      'callback' => array( $this, 'products_widget' )
    );

    return $widgets;
  }

  /**
   * Output current filters.
   */
  public function current_filters() {

    echo '<p>' . ' <strong>' . $this->doctor . '</strong></p>';
    echo '<p><a class="button" href="' . esc_url( remove_query_arg( 'doctor' ) ) . '">' . __( 'Reset', 'woocommerce' ) . '</a></p>';
  }

  /**
   * Output products widget.
   */
  public function products_widget() {
    ?>
    <h4 class="section_title"><span><?php _e( 'Doctor Search', 'woocommerce' ); ?></span></h4>
    <div class="section">
      <form method="GET">
        <div>
          <select class="wc-doctor-search" style="width:203px;" name="doctor">
            <?php
              $doctors = get_terms( 'doctor', array( 'hide_empty' => false ) );
              echo '<option value="">Search for a doctor&hellip;</option>';
              foreach ( $doctors as $doctor) {
                echo '<option value="'.$doctor->name.'">'.$doctor->name.'</option>';
              }
            ?>
          </select>
          <input type="submit" class="submit button" value="<?php esc_attr_e( 'Show', 'woocommerce' ); ?>" />
          <input type="hidden" name="range" value="<?php if ( ! empty( $_GET['range'] ) ) echo esc_attr( $_GET['range'] ) ?>" />
          <input type="hidden" name="start_date" value="<?php if ( ! empty( $_GET['start_date'] ) ) echo esc_attr( $_GET['start_date'] ) ?>" />
          <input type="hidden" name="end_date" value="<?php if ( ! empty( $_GET['end_date'] ) ) echo esc_attr( $_GET['end_date'] ) ?>" />
          <input type="hidden" name="page" value="<?php if ( ! empty( $_GET['page'] ) ) echo esc_attr( $_GET['page'] ) ?>" />
          <input type="hidden" name="tab" value="<?php if ( ! empty( $_GET['tab'] ) ) echo esc_attr( $_GET['tab'] ) ?>" />
          <input type="hidden" name="report" value="<?php if ( ! empty( $_GET['report'] ) ) echo esc_attr( $_GET['report'] ) ?>" />
        </div>
      </form>
    </div>
    <h4 class="section_title"><span><?php _e( 'Top Doctors', 'woocommerce' ); ?></span></h4>
    <div class="section">
      <table cellspacing="0">
        <?php
        $top_doctors = get_terms( 'doctor', array( 'hide_empty' => true, 'number' => 12, 'orderby' => 'count', 'order' => 'DESC' ) );
        if ( $top_doctors ) {
          $i = 0;
          foreach ( $top_doctors as $doctor ) { $i++;
            echo '<tr class="' . ( $doctor->name == $this->doctor ? 'active' : '' ) . '">
                    <td class="count">' . $i . '</td>
                    <td class="name"><a href="' . esc_url( add_query_arg( 'doctor', $doctor->name ) ) . '">' . $doctor->name . '</a></td>
                  </tr>';
          }
        } else {
          echo '<tr><td colspan="3">' . __( 'No doctors found', 'woocommerce' ) . '</td></tr>';
        }
        ?>
      </table>
    </div>
    <script type="text/javascript">
      jQuery('.wc-doctor-search').select2({
          minimumInputLength: 3
      });
      jQuery('.section_title').click(function(){
        var next_section = jQuery(this).next('.section');

        if ( jQuery(next_section).is(':visible') )
          return false;

        jQuery('.section:visible').slideUp();
        jQuery('.section_title').removeClass('open');
        jQuery(this).addClass('open').next('.section').slideDown();

        return false;
      });
      jQuery('.section').slideUp( 100, function() {
        <?php if ( empty( $this->product_ids ) ) : ?>
          jQuery('.section_title:eq(1)').click();
        <?php endif; ?>
      });
    </script>
    <?php
  }

  /**
   * Output an export link.
   */
  public function get_export_button() {
    $current_doctor = sanitize_text_field( $_GET['doctor'] );
    $current_range = ! empty( $_GET['range'] ) ? sanitize_text_field( $_GET['range'] ) : '7day';
    if(!empty($current_doctor)) { ?>
      <a
        href="#"
        download="<?php $this->doctor; ?>-report-<?php echo esc_attr( $current_range ); ?>-<?php echo date_i18n( 'Y-m-d', current_time('timestamp') ); ?>.csv"
        class="export_csv"
        data-export="chart"
        data-xaxes="<?php esc_attr_e( 'Date', 'woocommerce' ); ?>"
        data-groupby="<?php echo $this->chart_groupby; ?>"
      >
        <?php _e( 'Export CSV', 'woocommerce' ); ?>
      </a>
    <?php
    }
  }

  /**
   * Get the main chart.
   *
   * @return string
   */
  public function get_main_chart() {
    global $wp_locale;

    if ( empty( $this->doctor ) ) {
      ?>
      <div class="chart-container">
        <p class="chart-prompt"><?php _e( '&larr; Choose a doctor to view stats', 'woocommerce' ); ?></p>
      </div>
      <?php
    } else {
      // Get orders and dates in range - we want the SUM of order totals, COUNT of order items, COUNT of orders, and the date
      $order_item_counts = $this->get_order_report_data( array(
        'data' => array(
          '_qty' => array(
            'type'            => 'order_item_meta',
            'order_item_type' => 'line_item',
            'function'        => 'SUM',
            'name'            => 'order_item_count'
          ),
          'post_date' => array(
            'type'     => 'post_data',
            'function' => '',
            'name'     => 'post_date'
          )
        ),
        'where_meta' => array(
          'relation' => 'OR',
          array(
            'meta_key'   => '_billing_doctor',
            'meta_value' => $this->doctor,
            'operator'   => 'IN'
          ),
        ),
        'group_by'     => $this->group_by_query,
        'order_by'     => 'post_date ASC',
        'query_type'   => 'get_results',
        'filter_range' => true
      ) );

      $order_item_amounts = $this->get_order_report_data( array(
        'data' => array(
          '_line_total' => array(
            'type'            => 'order_item_meta',
            'order_item_type' => 'line_item',
            'function' => 'SUM',
            'name'     => 'order_item_amount'
          ),
          'post_date' => array(
            'type'     => 'post_data',
            'function' => '',
            'name'     => 'post_date'
          ),
        ),
        'where_meta' => array(
          'relation' => 'OR',
          array(
            'meta_key'   => '_billing_doctor',
            'meta_value' => $this->doctor,
            'operator'   => 'IN'
          ),
        ),
        'group_by'     => $this->group_by_query,
        'order_by'     => 'post_date ASC',
        'query_type'   => 'get_results',
        'filter_range' => true
      ) );

      // Prepare data for report
      $order_item_counts  = $this->prepare_chart_data( $order_item_counts, 'post_date', 'order_item_count', $this->chart_interval, $this->start_date, $this->chart_groupby );
      $order_item_amounts = $this->prepare_chart_data( $order_item_amounts, 'post_date', 'order_item_amount', $this->chart_interval, $this->start_date, $this->chart_groupby );

      // Encode in json format
      $chart_data = json_encode( array(
        'order_item_counts'  => array_values( $order_item_counts ),
        'order_item_amounts' => array_values( $order_item_amounts )
      ) );
      ?>
      <div class="chart-container">
        <div class="chart-placeholder main"></div>
      </div>
      <script type="text/javascript">
        var main_chart;

        jQuery(function(){
          var order_data = jQuery.parseJSON( '<?php echo $chart_data; ?>' );

          var drawGraph = function( highlight ) {

            var series = [
              {
                label: "<?php echo esc_js( __( 'Number of items sold', 'woocommerce' ) ) ?>",
                data: order_data.order_item_counts,
                color: '<?php echo $this->chart_colours['item_count']; ?>',
                bars: { fillColor: '<?php echo $this->chart_colours['item_count']; ?>', fill: true, show: true, lineWidth: 0, barWidth: <?php echo $this->barwidth; ?> * 0.5, align: 'center' },
                shadowSize: 0,
                hoverable: false
              },
              {
                label: "<?php echo esc_js( __( 'Sales amount', 'woocommerce' ) ) ?>",
                data: order_data.order_item_amounts,
                yaxis: 2,
                color: '<?php echo $this->chart_colours['sales_amount']; ?>',
                points: { show: true, radius: 5, lineWidth: 3, fillColor: '#fff', fill: true },
                lines: { show: true, lineWidth: 4, fill: false },
                shadowSize: 0,
                <?php echo $this->get_currency_tooltip(); ?>
              }
            ];

            if ( highlight !== 'undefined' && series[ highlight ] ) {
              highlight_series = series[ highlight ];

              highlight_series.color = '#9c5d90';

              if ( highlight_series.bars )
                highlight_series.bars.fillColor = '#9c5d90';

              if ( highlight_series.lines ) {
                highlight_series.lines.lineWidth = 5;
              }
            }

            main_chart = jQuery.plot(
              jQuery('.chart-placeholder.main'),
              series,
              {
                legend: {
                  show: false
                },
                grid: {
                  color: '#aaa',
                  borderColor: 'transparent',
                  borderWidth: 0,
                  hoverable: true
                },
                xaxes: [ {
                  color: '#aaa',
                  position: "bottom",
                  tickColor: 'transparent',
                  mode: "time",
                  timeformat: "<?php if ( $this->chart_groupby == 'day' ) echo '%d %b'; else echo '%b'; ?>",
                  monthNames: <?php echo json_encode( array_values( $wp_locale->month_abbrev ) ) ?>,
                  tickLength: 1,
                  minTickSize: [1, "<?php echo $this->chart_groupby; ?>"],
                  font: {
                    color: "#aaa"
                  }
                } ],
                yaxes: [
                  {
                    min: 0,
                    minTickSize: 1,
                    tickDecimals: 0,
                    color: '#ecf0f1',
                    font: { color: "#aaa" }
                  },
                  {
                    position: "right",
                    min: 0,
                    tickDecimals: 2,
                    alignTicksWithAxis: 1,
                    color: 'transparent',
                    font: { color: "#aaa" }
                  }
                ],
              }
            );

            jQuery('.chart-placeholder').resize();
          }

          drawGraph();

          jQuery('.highlight_series').hover(
            function() {
              drawGraph( jQuery(this).data('series') );
            },
            function() {
              drawGraph();
            }
          );
        });
      </script>
      <?php
    }
  }
  
}