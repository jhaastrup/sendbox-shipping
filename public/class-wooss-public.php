<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @link  #
 * @since 1.0.0
 *
 * @package    WooSS
 * @subpackage WooSS/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    WooSS
 * @subpackage WooSS/public
 * @author     jastrup <jhaastrup21@gmail.com>
 */
class WooSS_Public {


	/**
	 * The ID of this plugin.
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since 1.0.0
	 * @param string $plugin_name The name of the plugin.
	 * @param string $version     The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;

	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Wooss_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Wooss_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/wooss-public.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Wooss_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Wooss_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/wooss-public.js', array( 'jquery' ), $this->version, false );

	}

	/**
	 * This function is responsible to display the extra fees that the shop owner adds.
	 *
	 * @param mixed $order_id Order ID.
	 *
	 * @return void
	 */
	public function add_extra_fees_to_order( $order_id ) {
		$sendbox_data = get_option('sendbox_data');
		$wooss_extra_fees = $sendbox_data[ 'wooss_extra_fees' ];
		update_post_meta( $order_id, 'wooss_extra_fees', 'Your additionnal fees is : ' . $wooss_extra_fees . get_woocommerce_currency() );
	}
}
