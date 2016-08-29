<?php
/**
 * @class         WC_Report_Sales_By_Doctor
 * @since         1.0
 * @package       WooCommerce Sales by Doctor
 * @subpackage    Base class
 * @author        AndresTheGiant <hello@andresthegiant.com>
 * @link          https://github.com/afgarcia86
 * @license       http://www.gnu.org/licenses/gpl-3.0.html
 *
 */


if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

// Including WP core file
if ( ! function_exists( 'get_plugins' ) ) {
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
}

// If class not exists
if ( ! class_exists( 'WC_Report_Sales_By_Doctor' ) ) :


// Base class
class WC_Report_Sales_By_Doctor {

	/**
   * Plugin Version
   */
	public $version;


	/**
   * Chart colours.
   *
   * @var array
   */
  public $chart_colours = array();

	/**
	 * Class constructor
	 *
	 * @access public
	 */
	public function __construct() {

		// Sync plugin version
		$sbd_version   = get_plugin_data( plugin_dir_path( __FILE__ ) . 'sales-by-doctor.php' );
		$this->version = $sbd_version['Version'];

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

    $this->doctor = ! empty( $_GET['doctor'] ) ? sanitize_text_field( $_GET['doctor'] ) : NULL;

    if( ! empty( $_GET['doctor'] ) ) $this->get_sales_by_doctor();

		// Enqueue script hook
		add_action( 'admin_enqueue_scripts', array( $this, 'sbd_enqueue' ) );
	}

	/**
	 * Get doctor wise total sales
	 *
	 * @access public
	 * @return object
	 */
	public function get_sales_by_doctor() {
		global $wpdb;

		var_dump($this->group_by_query);

		$begin = new DateTime("@$this->start_date");
		$end = new DateTime(date( 'Y-m-d', strtotime('+1 days', $this->end_date)));

		$interval = DateInterval::createFromDateString($this->group_by_query);
		$period = new DatePeriod($begin, $interval, $end);

		$total_sales = 0;
		$order_total = 0;
		$sales = array();
		$order_item_counts = array();
		$order_item_amounts = array();

    var_dump($interval);
    var_dump($period);

		$days = 0;
		foreach ( $period as $dt ){
			$days++;

			$args = array(
				'post_type' => 'shop_order',
				'post_status' => 'published',
				'meta_query' => array(
				   array(
		        'key' => '_billing_doctor',
		        'value'   => $this->doctor,
		        'compare' => '=='
			    )
				),
				'posts_per_page' => -1
			);

      if($this->chart_groupby == 'month') {
        $args['date_query'] = array(
          array(
           'after' => date( 'Y-m-d', strtotime('-1 days', $this->start_date)),
           'before' => date( 'Y-m-d', strtotime('+1 days', $this->end_date))
          ),
          'inclusive' => true,
        );
      } elseif($this->chart_groupby == 'week') {
        $args['date_query'] = array(
          array(
           'after' => date( 'Y-m-d', strtotime('-1 days', $this->start_date)),
           'before' => date( 'Y-m-d', strtotime('+1 days', $this->end_date))
          ),
          'inclusive' => true,
        );
      } else {
        $args['date_query'] = array(
          array(
            'year'  => intval($dt->format('Y')),
            'month' => intval($dt->format('m')),
            'day'   => intval($dt->format('d'))
          )
        );
      }

			// var_dump($order_query);

			$order_query = new WP_Query( $args );

			$order_item_amount = 0;
			foreach ($order_query->get_posts() as $order) {
				// var_dump($order);
				// var_dump(get_post_custom($order->ID));
				$order_total = get_post_meta( $order->ID, '_order_total', true );
				$order_item_amount = number_format($order_item_amount+$order_total, 2);
			}

			$total_orders = $total_orders+$order_query->found_posts;
			array_push($order_item_counts, array($dt->format('U')*1000, $order_query->found_posts));

			$total_sales = number_format($total_sales+$order_item_amount, 2);
			array_push($order_item_amounts, array($dt->format('U')*1000, $order_item_amount));

		}

		var_dump($days);

		$this->total_orders = $total_orders;
		$this->total_sales = $total_sales;
		$this->order_item_counts = $order_item_counts;
		$this->order_item_amounts = $order_item_amounts;
	}

	/**
	 * Reset Widget
	 */
	public function reset_widget() {
		if ( ! empty( $this->doctor ) ) { ?>
			<li class="chart-widget">
				<h4>Showing reports for:</h4>
				<p><strong><?php echo $this->doctor; ?></strong></p>
				<p><a href="/wp-admin/admin.php?range=<?php echo $_GET['range']; ?>&amp;start_date=<?php echo $_GET['start_date']; ?>&amp;end_date=<?php echo $_GET['end_date']; ?>&amp;page=wc-reports&amp;tab=orders&amp;report=sales_by_doctor" class="button">Reset</a></p>
			</li>
			<?php
		}
	}

	/**
	 * Output products widget.
	 */
  public function doctors_widget() { ?>
  	<li class="chart-widget">
	    <h4 class="section_title"><span><?php _e( 'Doctor Search', 'woocommerce' ); ?></span></h4>
	    <div class="section">
	      <form method="GET">
	        <div>
	          <select class="sbd-doctor-search" style="width:203px;" name="doctor">
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
	        <tbody>
	        	<?php
	          	$doctors = get_terms( 'doctor', array( 'hide_empty' => false, 'number' => 5, 'orderby' => 'count' ) );
	          	$i = 0;
						  foreach ( $doctors as $doctor) { $i++; ?>
						    <tr class="<?php echo ($this->doctor == $doctor->name ? 'active' : ''); ?>">
			            <td class="count"><?php echo $i; ?></td>
			            <td class="name">
			            	<a href="/wp-admin/admin.php?range=<?php echo $_GET['range']; ?>&amp;start_date=<?php echo $_GET['start_date']; ?>&amp;end_date=<?php echo $_GET['end_date']; ?>&amp;page=wc-reports&amp;tab=orders&amp;report=sales_by_doctor&amp;doctor=<?php echo $doctor->name; ?>"><?php echo $doctor->name; ?></a>
			            </td>
			          </tr>
						  <?php
							}
						?>
	        </tbody>
	      </table>
	    </div>
			<script type="text/javascript">
				
				jQuery('.sbd-doctor-search').select2({
					minimumInputLength: 3
				  // minimumResultsForSearch: 3
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
	        <?php if ( empty( $this->doctor ) ) : ?>
	          jQuery('.section_title:eq(1)').click();
	        <?php endif; ?>
	      });
	      
	    </script>
	  </li>
   <?php
  }

   /**
   * Output an export link.
   */
  public function get_export_button() {
    $current_range = ! empty( $_GET['range'] ) ? sanitize_text_field( $_GET['range'] ) : '7day'; ?>
    <a
      href="#"
      download="report-<?php echo esc_attr( $current_range ); ?>-<?php echo date_i18n( 'Y-m-d', current_time('timestamp') ); ?>.csv"
      class="export_csv"
      data-export="chart"
      data-xaxes="<?php esc_attr_e( 'Date', 'woocommerce' ); ?>"
      data-groupby="<?php echo $this->chart_groupby; ?>"
    >
      <?php _e( 'Export CSV', 'woocommerce' ); ?>
    </a>
    <?php
  }

  /**
   * Build chart legend
   */
  public function get_chart_legend() {
  	if ( ! empty( $this->doctor ) ) { ?>
			<ul class="chart-legend">
				<li data-tip="" data-series="1" class="highlight_series " style="border-color: #3498db;">
					<strong><?php echo get_woocommerce_currency_symbol() . $this->total_sales;  ?></strong> sales for the selected items
				</li>
				<li data-tip="" data-series="0" class="highlight_series " style="border-color: #d4d9dc;">
					<strong><?php echo $this->total_orders; ?></strong> purchases reffered by the selected doctor
				</li>
			</ul>
			<?php 
		}
  }


	/**
	 * Check WooCommerce version
	 *
	 * @access public
	 * @return boolean
	 */
	public function is_wc_old() {
		global $woocommerce;

		$plugin_folder = get_plugins( '/' . 'woocommerce' );
		$plugin_file   = 'woocommerce.php';
		$wc_version    = $plugin_folder[$plugin_file]['Version'];
		$wc_version    = isset( $wc_version ) ? $wc_version : $woocommerce->version;

		return version_compare( $wc_version, '2.1', 'lt' );
	}

	/**
	 * Plugin script enqueue function
	 *
	 * @access public
	 * @return void
	 */
	public function sbd_enqueue() {
		wp_enqueue_script( 'sbd_datepicker', plugins_url( 'script.js', __FILE__ ), array(), $this->version, true );
	}

	/* COPIED WOOCOMMERCE FUNCTIONS ============================================================= */

	/**
	 * Get the current range and calculate the start and end dates.
	 *
	 * @param  string $current_range
	 */
	public function calculate_current_range( $current_range ) {

		switch ( $current_range ) {

			case 'custom' :
				$this->start_date = strtotime( sanitize_text_field( $_GET['start_date'] ) );
				$this->end_date   = strtotime( 'midnight', strtotime( sanitize_text_field( $_GET['end_date'] ) ) );

				if ( ! $this->start_date ) {
					$this->start_date = strtotime( '-6 days', current_time( 'timestamp' ));
				}

				if ( ! $this->end_date ) {
					$this->end_date = current_time( 'timestamp' );
				}

				$interval = 0;
				$min_date = $this->start_date;

				while ( ( $min_date = strtotime( "+1 WEEK", $min_date ) ) <= $this->end_date ) {
					$interval ++;
				}

        var_dump($interval);

				// 3 months max for day view
				if ( $interval > 12 ) {
					$this->chart_groupby = 'month';
				} elseif ( $interval > 4)  {
					$this->chart_groupby = 'week';
				} else {
					$this->chart_groupby = 'day';
				}
				
			break;

			case 'year' :
				$this->start_date    = strtotime( date( 'Y-01-01', current_time('timestamp') ) );
				$this->end_date      = strtotime( 'midnight', current_time( 'timestamp' ) );
				$this->chart_groupby = 'month';
			break;

			case 'last_month' :
				$first_day_current_month = strtotime( date( 'Y-m-01', current_time( 'timestamp' ) ) );
				$this->start_date        = strtotime( date( 'Y-m-01', strtotime( '-1 DAY', $first_day_current_month ) ) );
				$this->end_date          = strtotime( date( 'Y-m-t', strtotime( '-1 DAY', $first_day_current_month ) ) );
				$this->chart_groupby     = 'day';
			break;

			case 'month' :
				$this->start_date    = strtotime( date( 'Y-m-01', current_time('timestamp') ) );
				$this->end_date      = strtotime( 'midnight', current_time( 'timestamp' ) );
				$this->chart_groupby = 'day';
			break;

			case '7day' :
				$this->start_date    = strtotime( '-6 days', current_time( 'timestamp' ) );
				$this->end_date      = strtotime( 'midnight', current_time( 'timestamp' ) );
				$this->chart_groupby = 'day';
			break;
		}

		// Group by
		switch ( $this->chart_groupby ) {

			case 'day' :
				$this->group_by_query = '1 day';
				// $this->chart_interval = absint( ceil( max( 0, ( $this->end_date - $this->start_date ) / ( 60 * 60 * 24 ) ) ) );
				$this->barwidth = 60 * 60 * 24 * 1000;
			break;

			case 'week' :
				$this->group_by_query = '7 day';
				// $this->chart_interval = 0;
				$min_date             = $this->start_date;

				while ( ( $min_date   = strtotime( "+1 WEEK", $min_date ) ) <= $this->end_date ) {
					$this->chart_interval ++;
				}

				$this->barwidth = 60 * 60 * 24 * 7 * 1000;
			break;

			case 'month' :
				$this->group_by_query = '1 Month';
				$this->chart_interval = 0;
				// $min_date             = $this->start_date;

				while ( ( $min_date   = strtotime( "+1 MONTH", $min_date ) ) <= $this->end_date ) {
					$this->chart_interval ++;
				}

				$this->barwidth = 60 * 60 * 24 * 7 * 4 * 1000;
			break;
		}
	}

	/**
	 * Return currency tooltip JS based on WooCommerce currency position settings.
	 *
	 * @return string
	 */
	public function get_currency_tooltip() {
		switch( get_option( 'woocommerce_currency_pos' ) ) {
			case 'right':
				$currency_tooltip = 'append_tooltip: "' . get_woocommerce_currency_symbol() . '"'; break;
			case 'right_space':
				$currency_tooltip = 'append_tooltip: "&nbsp;' . get_woocommerce_currency_symbol() . '"'; break;
			case 'left':
				$currency_tooltip = 'prepend_tooltip: "' . get_woocommerce_currency_symbol() . '"'; break;
			case 'left_space':
			default:
				$currency_tooltip = 'prepend_tooltip: "' . get_woocommerce_currency_symbol() . '&nbsp;"'; break;
		}

		return $currency_tooltip;
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

      // Encode in json format
      $chart_data = json_encode( array(
        'order_item_counts'  => array_values( $this->order_item_counts ),
        'order_item_amounts' => array_values( $this->order_item_amounts )
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

endif;
