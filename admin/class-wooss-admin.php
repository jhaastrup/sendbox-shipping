<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       #
 * @since      1.0.0
 *
 * @package    wooss
 * @subpackage wooss/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Wooss
 * @subpackage Wooss/admin
 * @author     jastrup <jhaastrup21@gmail.com>
 */
class Wooss_Admin
{


	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string $plugin_name       The name of this plugin.
	 * @param      string $version    The version of this plugin.
	 */
	public function __construct($plugin_name, $version)
	{

		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles()
	{
		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in wooss_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The wooss_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */
		wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/wooss-admin.css', array(), $this->version, 'all');
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts()
	{
		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in wooss_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The wooss_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */
		wp_enqueue_script('jquery-ui-dialog');
		wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/wooss-admin.js', array('jquery'), $this->version, false);
		wp_localize_script(
			$this->plugin_name,
			'wooss_ajax_object',
			[
				'wooss_ajax_url'      => admin_url('admin-ajax.php'),
				'wooss_ajax_security' => wp_create_nonce('wooss-ajax-security-nonce'),
			]
		);
	}

	/**
	 * Function to check if woo-commerce is installed.
	 */
	public function check_wc()
	{
		if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
			?>
			<div class=" notice notice-error">
				<p>
					<?php
					esc_html_e('Sendbox Shipping require WooCommerce for working. ', 'wooss');
					?>
				</p>
			</div>
		<?php

		}
	}

	/**
	 * Add ship with Sendbox as a meta box on the dashboard.
	 *
	 * @param  mixed $order Shipping Methods Lists.
	 *
	 * @return array
	 */

	public function add_sendbox_metabox()
	{
	     if (!isset($_GET['post']))
		        return;
		        
	     global $post_type;
		if (is_admin() && "shop_order" == $post_type) {
			$order_id = $_GET['post'];
			$_order = wc_get_order($order_id);
			$method_id = "";
			foreach ($_order->get_items('shipping') as $item_id => $shipping_item_obj) {
				$method_id .= $shipping_item_obj->get_method_id();
			}

			if ($method_id == "wooss") {
				add_meta_box('wooss_ship_sendbox', __('Ship with Sendbox', 'wooss'), array($this, 'field_for_request_shipments'), 'shop_order', 'side', 'high');
			}
		}
	}

	public function field_for_request_shipments($order)
	{
		global $post_type;
		$order_id = $order->ID;
		if ('shop_order' == $post_type) {
			$_order              = new WC_Order($order_id);
			$destination_name    = $_order->get_formatted_billing_full_name();
			$destination_phone   = $_order->get_billing_phone();
			$destination_street  = $_order->get_billing_address_1();
			$destination_city    = $_order->get_billing_city();
			$destination_state   = $_order->get_billing_state();
			$destination_country = $_order->get_billing_country();
			$destination_email   = $_order->get_billing_email();

			$countries_obj = new WC_Countries();

			$states = $countries_obj->get_states($destination_country);
			foreach ($states as $state_code => $state_name) {
				if ($destination_state == $state_code) {
					$destination_state = $state_name;
					break;
				}
			}

		$country = $countries_obj->get_countries();
			foreach ($country as $country_code => $country_name) {
				if ($destination_country == $country_code) {
					$destination_country = $country_code;
					break;
				}
			}
			

			$customer_products = $_order->get_items();
			$items_lists       = [];

			$fee      = 0;
			$quantity = 0;

			foreach ($customer_products as $products_data) {
				$product_data                  = $products_data->get_data();
				$product_id                    = $product_data['product_id'];
				$_product                      = wc_get_product($product_id);
				$items_data                    = new stdClass();
				$items_data->name              = $product_data['name'];
				$items_data->quantity          = $product_data['quantity'];
				$items_data->value             = $_product->get_price();
				$items_data->amount_to_receive = $_product->get_price();
				$items_data->package_size_code = 'medium';
				$items_data->item_type_code    = strip_tags(wc_get_product_category_list($product_id));

				$product_weight = $_product->get_weight();
				if (null != $product_weight) {
					$weight = $product_weight;
				} else {
					$weight = 0;
				}
				
				$fee               += round($_product->get_price());
				$quantity          += $product_data['quantity'];
				$items_data->weight = $weight * $quantity;
				array_push($items_lists, $items_data);
			}

			$api_call                   = new Wooss_Sendbox_Shipping_API();
			$auth_header                = get_option('wooss_basic_auth');
			$args                       = array(
				'headers' => array(
					'Content-Type'  => 'application/json',
					'Authorization' => $auth_header,
				),
			);
			$profile_api_url            = 'https://api.sendbox.ng/v1/merchant/profile';
			$profile_data_response_code = $api_call->get_api_response_code($profile_api_url, $args, 'GET');
			$profile_data_response_body = $api_call->get_api_response_body($profile_api_url, $args, 'GET');
			$wooss_origin_name          = '';
			$wooss_origin_email         = '';
			$wooss_origin_phone         = '';
			if (200 == $profile_data_response_code) {
				$wooss_origin_name  = $profile_data_response_body->name;
				$wooss_origin_email = $profile_data_response_body->email;
				$wooss_origin_phone = $profile_data_response_body->phone;
			}
			$wc_city             = get_option('woocommerce_store_city');
			$wc_store_address    = get_option('woocommerce_store_address');
			$wooss_origin_city   = get_option('wooss_origin_city');
			$wooss_origin_street = get_option('wooss_origin_street');
			if (null == $wooss_origin_city) {
				$wooss_origin_city = $wc_city;
			}
			if (null == $wooss_origin_street) {
				$wooss_origin_street = $wc_store_address;
			}
			$wooss_origin_states_selected = get_option('wooss_states_selected');
			if (null == $wooss_origin_states_selected) {
				$wooss_origin_states_selected = '';
			}
			$wooss_origin_country = get_option('wooss_origin_country');
			if (null == $wooss_origin_country) {
				$wooss_origin_country = 'Nigeria';
			}

			$wooss_pickup_type = get_option('wooss_pickup_type');
			if (null == $wooss_pickup_type) {
				$wooss_pickup_type = 'pickup';
			}

			$incoming_option_code = get_option('wooss_pickup_type');
			if (null == $incoming_option_code) {
				return;
			}

			$date = new DateTime();
			$date->modify('+1 day');
			$method_id = "";
			$pickup_date = $date->format(DateTime::ATOM);



			$method_id = "";
			foreach ($_order->get_items('shipping') as $item_id => $shipping_item_obj) {
				$method_id .= $shipping_item_obj->get_method_id();
			}

			if ($method_id == "wooss") {

				?>

				<div style="display:none;" id="wooss_shipments_data">

					<span style="display:none;"><strong><?php esc_html_e('Origin Details : ', 'wooss'); ?></strong>
						<i>This represents your store details</i>
						<br />
						<strong><label for="wooss_origin_name"><?php esc_html_e('Name : ', 'wooss'); ?></label></strong>
						<input type="text" name="wooss_origin_name" id="wooss_origin_name" class="wooss-text" value="<?php echo (esc_attr($wooss_origin_name)); ?>" readonly>
						&nbsp
						<strong><label for="wooss_origin_phone"><?php esc_html_e('Phone : ', 'wooss'); ?></label></strong>
						<input type="text" name="wooss_origin_phone" id="wooss_origin_phone" class="wooss-text" value="<?php echo (esc_attr($wooss_origin_phone)); ?>" readonly>
						<br />&nbsp

						<br /><strong><label for="wooss_origin_email"><?php esc_html_e('Email : ', 'wooss'); ?></label></strong>
						<input type="text" name="wooss_origin_email" id="wooss_origin_email" class="wooss-text" value="<?php echo (esc_attr($wooss_origin_email)); ?>" readonly>
						&nbsp
						<strong><label for="wooss_origin_street"><?php esc_html_e('Street : ', 'wooss'); ?></label></strong>
						<input type="text" name="wooss_origin_street" id="wooss_origin_street" class="wooss-text" class="wooss-text" value="<?php echo (esc_attr($wc_store_address)); ?>" readonly>
						<br />&nbsp
						<br /><strong><label for="wooss_origin_country"><?php esc_html_e('Country : ', 'wooss'); ?></label></strong>
						<input type="text" name="wooss_origin_country" id="wooss_origin_country" class="wooss-text" value="<?php echo (esc_attr($wooss_origin_country)); ?>" readonly>
						&nbsp
						<strong><label for="wooss_origin_state"><?php esc_html_e('States : ', 'wooss'); ?></label></strong>
						<input type="text" name="wooss_origin_state" id="wooss_origin_state" class="wooss-text" value="<?php echo (esc_attr($wooss_origin_states_selected)); ?>" readonly>
						&nbsp

						<br />&nbsp


						<br /><strong><label for="wooss_origin_city"><?php esc_html_e('City : ', 'wooss'); ?></label></strong>
						<input type="text" name="wooss_origin_city" id="wooss_origin_city" class="wooss-text" value="<?php echo (esc_attr($wooss_origin_city)); ?>" readonly>
					</span>


					<br />
					<br />
					<span><strong><?php esc_html_e('Destination Details : ', 'wooss'); ?></strong>
						<i>This represents your customer details</i>
						<br />
						<strong><label for="wooss_destination_name"><?php esc_html_e('Name : ', 'wooss'); ?></label></strong>
						<input type="text" name="wooss_destination_name" id="wooss_destination_name" class="wooss-text" value="<?php esc_html_e($destination_name); ?>" readonly>
						&nbsp
						<label><label for="wooss_destination_phone"><?php esc_html_e('Phone : ', 'wooss'); ?></label></label>
						<input type="text" name="wooss_destination_phone" id="wooss_destination_phone" class="wooss-text" value="<?php esc_html_e($destination_phone); ?>" readonly>
						<br />&nbsp

						<br /><strong><label for="wooss_destination_email"><?php esc_html_e('Email : ', 'wooss'); ?></label></strong>
						<input type="text" name="wooss_destination_email" id="wooss_destination_email" class="wooss-text" value="<?php esc_html_e($destination_email); ?>" readonly>
						&nbsp
						<strong><label for="wooss_destination_street"><?php esc_html_e('Street : ', 'wooss'); ?></label></strong>
						<input type="text" name="wooss_destination_street" id="wooss_destination_street" class="wooss-text" value="<?php esc_html_e($destination_street); ?>" readonly>
						<br />&nbsp
						<br /><strong><label for="wooss_destination_country"><?php esc_html_e('Country : ', 'wooss'); ?></label></strong>
						<input type="text" name="wooss_destination_country" id="wooss_destination_country" class="wooss-text" value="<?php esc_html_e($destination_country); ?>" readonly>
						&nbsp
						<strong><label for="wooss_destination_state"><?php esc_html_e('State : ', 'wooss'); ?></label></strong>
						<input type="text" name="wooss_destination_state" id="wooss_destination_state" class="wooss-text" value="<?php esc_html_e($destination_state); ?>" readonly>
						<br />&nbsp
						 <br /><strong><label for="wooss_destination_city"><?php esc_html_e('City : ', 'wooss'); ?></label></strong>
						<input type="text" name="wooss_destination_city" id="wooss_destination_city" class="wooss-text" value="<?php esc_html_e($destination_city); ?>" readonly>
 				</span>

					<?php
					$product_content = '';
					foreach ($items_lists as $lists_id => $list_data) {
						$product_name              = $list_data->name;
						$product_quantity          = $list_data->quantity;
						$product_value             = $list_data->value;
						$product_amount            = $list_data->amount_to_receive;
						$product_package_size_code = $list_data->package_size_code;
						$product_item_type_code    = $list_data->item_type_code;
						$product_weights           = $list_data->weight;
						$product_content          .= '{"name" : "' . $product_name . '", "quantity" :"' . $product_quantity . '", "value" :"' . $product_value . '", "amount_to_receive" :"' . $product_amount . '", "package_size_code":"' . $product_package_size_code . '", "item_type":"' . $product_item_type_code . '"," weight" :"' . $product_weights . '"}';
					}

					// loading the payload

					$payload_data                         = new stdClass();
					$payload_data->origin_name      = $wooss_origin_name;
					$payload_data->destination_country    = $destination_country;
					$payload_data->destination_country_code = $destination_country;
					$payload_data->destination_state_code = ' ';
					$payload_data->destination_state      = $destination_state;
					$payload_data->destination_city       = $destination_city;
					$payload_data->destination_street     = $destination_street;
					$payload_data->destination_name       = $destination_name;
					$payload_data->destination_phone      = $destination_phone;
					$payload_data->items                  = $items_lists;
					$payload_data->weight                 = (int) $weight * $quantity;
					$payload_data->amount_to_receive      = (int) $fee;
					$payload_data->origin_country         = $wooss_origin_country;
					$payload_data->origin_state           = $wooss_origin_states_selected;

					$payload_data->origin_phone    = $wooss_origin_phone;
					$payload_data->origin_street         = $wc_store_address;
					$payload_data->origin_city     = $wooss_origin_city;
					$payload_data->deliver_priority_code = 'next_day';
					$payload_data->pickup_date           = $pickup_date;
					$payload_data->incoming_option_code  = $incoming_option_code;
					$payload_data->payment_option_code   = 'prepaid';
					$payload_data->deliver_type_code     = 'last_mile';

					$payload_data_json = wp_json_encode($payload_data);
					$delivery_args     = array(
						'headers' => array(
							'Content-Type'  => 'application/json',
							'Authorization' => $auth_header,
						),
						'body'    => $payload_data_json, 
					
					);
						
					?>
				</div>
				<span>
					<span>
						<!--<label?php _e('Items : ', 'wooss'); ?></label> --->
						<textarea style="display:none;" cols="50" class="wooss-textarea" id="wooss_items_list" value="<?php esc_html_e($product_content); ?>" data-id="<?php echo esc_attr($order_id); ?>"><?php trim(esc_html_e($product_content)); ?></textarea>
						<?php
						$quote_api_url =  $api_call->get_sendbox_api_url('delivery_quote');
						$quote_body    = $api_call->get_api_response_body($quote_api_url, $delivery_args, 'POST');

                             
						$quotes_rates = $quote_body->rates;
						//print_r($quote_body);
						
                           
						$wallet = $quote_body->wallet_balance;
              
						foreach ($quotes_rates as $rates_id => $rates_values) {
							$rates_names = $rates_values->name;
							$rates_fee   = $rates_values->fee;
							$rates_id   = $rates_values->id;
							//print_r($rates_values);
						}

						?>
						<span id="wooss_shipments_data">
							<div style="text-align : left; display: flex; flex-direction: row; justify-content: space-between; align-items: center;">

								<b>
									<?php
									esc_attr_e("WALLET BALANCE");
									?>
								</b>

								<b>
									<?php
									esc_attr_e("₦" . $wallet);
									
									?>
								</b>

							</div> 
                            
						<select id="wooss_selected_courier" >
								<option value=""> <?php esc_attr_e("Select a courier... "); ?> </option> 
								<?php
                                
								foreach ($quotes_rates as $rates_id => $rates_values) {
									$rates_names = $rates_values->name;
									$rates_fee   = $rates_values->fee;
									$rates_id   = $rates_values->courier_id;

									?>
									<option data-courier-price="<?php esc_attr_e($rates_fee, 'wooss'); ?> " value="<?php esc_attr_e($rates_id, 'wooss'); ?>" id="<?php _e($rates_id, 'wooss'); ?>"><?php esc_attr_e($rates_names) ?></option>
                                           
								<?php
								}
								?>
							
							</select>
							
							<?php
				           
						
						//for the countries	
						
						unset($args['headers']['Authorization']);
						$countries_api_url = add_query_arg( 'page_by', '{"per_page":264}', 'https://api.sendbox.co/auth/countries' );
						
					    $countries_data_obj =	wp_remote_get($countries_api_url, $args);
					    
					   
	
//	https://api.sendbox.co/auth/states?page_by={"per_page":10000}&country_code=NG
                        $countries_body    = json_decode(wp_remote_retrieve_body($countries_data_obj));
                        ?>
                        	
                        	<select  id = "wooss_destination_country">
                        	    <option data-country-code="default" value=""> 
                        	    <?php _e('Please select destination country'); ?>
                        	    </option>
                        	  
                        	    <?php
                        foreach($countries_body as $key => $value){
                          if (is_array($value)){
                              sort($value);
                              foreach($value as $ship_key => $ship_value){
                                echo "<option data-country-code='".$ship_value->code."' value='".$ship_value->name."'>".$ship_value->name." </option>";
                                
                              }
                          }
                        }
                        ?>
                        </select>  
                        
                        <?//end of countries?> 
                        
                        <?php
                        
                        $country_code_default = "NG";
                        
                        $states_api_url = add_query_arg( array( 'page_by'=> '{"per_page":10000}' , 'filter_by' => '{"country_code":"'.$country_code_default.'"}'), 'https://api.sendbox.co/auth/states' );
                        $states_data_obj =	wp_remote_get($states_api_url, $args);
                          
                           $states_body    = json_decode(wp_remote_retrieve_body($states_data_obj));
                          
                       
                        ?>
                        
                        
                        	<select id = "wooss_destination_state" >
							   <option data-state-code="default" value = ""> 
                        	    <?php _e('Please select destination state'); ?>
                        	    </option>
                        	  
                        	    <?php
                        foreach($states_body as $states_key => $states_values){
                          if (is_array($states_values)){
                              sort($states_values);
                              
                              foreach($states_values as $ship_state_key => $ship_state_value){
                                 
                                if (!empty($ship_state_value)){ 
                                   echo "<option data-states-code='".$ship_value->code."' value=".$ship_state_value->name.">".$ship_state_value->name." </option>"; 
                               }
                          }
                        }  }
                        	?>
							</select>
							
					
							
	<?php  
	
	                        $country_code_default = "NG";
	                        if (get_option('wooss_selected_country_code_used')){
	                            $country_code_default = get_option('wooss_selected_country_code_used');
	                        }
                        
                        $city_api_url = add_query_arg( array( 'page_by'=> '{"per_page":10000}' , 'filter_by' => '{"country_code":"'.$country_code_default.'"}'), 'https://api.sendbox.co/auth/cities' );
                        $city_data_obj =	wp_remote_get($city_api_url, $args);
                          
                           $city_body    = json_decode(wp_remote_retrieve_body($city_data_obj));
	
	
	?>
					<input id= "wooss_destination_city_name" type="text" placeholder="Enter Destination City"  value="<?php esc_html_e($destination_city); ?>">
						
							<?php
							
							
									
                                  $postal_code = $_order->get_billing_postcode();
                                 

							if (!empty($postal_code)){
							    
							    echo "<input id= 'wooss_postal_code' type='text' value='".$postal_code."' placeholder='Enter Postal Code'>";
							}
							
							?>

							<br />
							<div style="text-align : left; display: flex; flex-direction: row; justify-content: space-between; align-items: center;">

								<b id="dabs">
									<?php
									esc_attr_e("Fee: 0.0");
									?>
								</b>

								<button id="wooss_request_shipment" class="button-primary"><?php esc_html_e('Request Shipment'); ?></button>
							</div>
						</span>
					</span>
				<?php
				}
			}
		}

		/**
		 * AJAX function to request final shipment to Sendbox.
		 *
		 * @return void
		 */
		public function request_shipments()
		{
			if (isset($_POST['data'])) {
				$data              = wp_unslash($_POST['data']);
				$order_id          = sanitize_text_field($data['wooss_order_id']);
				$_order            = new WC_Order($order_id);
				$customer_products = $_order->get_items();
				$items_lists       = [];

				$fee      = 0;
				$quantity = 0;

				foreach ($customer_products as $products_data) {
					$product_data                  = $products_data->get_data();
					$product_id                    = $product_data['product_id'];
					$_product                      = wc_get_product($product_id);
					$items_data                    = new stdClass();
					$items_data->name              = $product_data['name'];
					$items_data->quantity          = $product_data['quantity'];
					$items_data->value             = $_product->get_price();
					$items_data->amount_to_receive = $_product->get_price();
					$items_data->package_size_code = 'medium';
					$items_data->item_type_code    = strip_tags(wc_get_product_category_list($product_id));

					$product_weight = $_product->get_weight();
					if (null != $product_weight) {
						$weight = $product_weight;
					} else {
						$weight = 0;
					}
				
					$fee               += round($_product->get_price());
					$quantity          += $product_data['quantity'];
					$items_data->weight = $weight * $quantity;
					array_push($items_lists, $items_data);
				}

				$courier_selected = sanitize_text_field($data['wooss_selected_courier']);

				$destination_name    = sanitize_text_field($data['wooss_destination_name']);
				$destination_phone   = sanitize_text_field($data['wooss_destination_phone']);
				$destination_email   = sanitize_text_field($data['wooss_destination_email']);
				$destination_city    = sanitize_text_field($data['wooss_destination_city']);
				$destination_country = sanitize_text_field($data['wooss_destination_country']);
				$destination_state   = sanitize_text_field($data['wooss_destination_state']);
				$destination_street  = sanitize_text_field($data['wooss_destination_street']);

				$origin_name    = sanitize_text_field($data['wooss_origin_name']);
				$origin_phone   = sanitize_text_field($data['wooss_origin_phone']);
				$origin_email   = sanitize_text_field($data['wooss_origin_email']);
				$origin_city    = sanitize_text_field($data['wooss_origin_city']);
				$origin_state   = sanitize_text_field($data['wooss_origin_state']);
				$origin_street  = sanitize_text_field($data['wooss_origin_street']);
				$origin_country = sanitize_text_field($data['wooss_origin_country']); 
				
				$destination_post_code = sanitize_text_field($data['wooss_postal_code']);

				$webhook_url = get_site_url() . '/wp-json/wooss/v2/shipping';

				$payload_data = new stdClass();

				$payload_data->selected_courier_id = $courier_selected;

				$payload_data->destination_name    = $destination_name;
				$payload_data->destination_phone   = $destination_phone;
				$payload_data->destination_email   = $destination_email;
				$payload_data->destination_city    = $destination_city;
				$payload_data->destination_country = $destination_country;
				$payload_data->destination_state   = $destination_state;
				$payload_data->destination_street  = $destination_street;

				$payload_data->origin_name       = $origin_name;
				$payload_data->origin_phone      = $origin_phone;
				$payload_data->origin_email      = $origin_email;
				$payload_data->origin_city       = $origin_city;
				$payload_data->origin_state      = $origin_state;
				$payload_data->origin_street     = $origin_street;
				$payload_data->origin_country    = $origin_country;
				$payload_data->items             = $items_lists;
				$payload_data->reference_code    = trim(str_replace('#', '', $order_id));
				$payload_data->amount_to_receive = $_order->get_shipping_total();
				$payload_data->delivery_callback = $webhook_url;
				$payload_data->destination_post_code = $destination_post_code;

				$date = new DateTime();
				$date->modify('+1 day');
				$pickup_date = $date->format('c');

				$payload_data->deliver_priority_code = 'next_day';
				$payload_data->pickup_date           = $pickup_date;

				$api_call    = new Wooss_Sendbox_Shipping_API();
				$auth_header = get_option('wooss_basic_auth');

				$payload_data_json = wp_json_encode($payload_data);

				$shipments_args = array(
					'headers' => array(
						'Content-Type'  => 'application/json',
						'Authorization' => $auth_header,
					),
					'body'    => $payload_data_json,
				);

				$shipments_url = $api_call->get_sendbox_api_url('shipments');


				$shipments_details = $api_call->get_api_response_body($shipments_url, $shipments_args, 'POST');

				if (isset($shipments_details)) {

					$tracking_code = $shipments_details->code;
					$status_codes =  $shipments_details->status_code;
					$successfull       = 0;
					if ('pending' == 	$status_codes) {
						update_post_meta($order_id, 'wooss_tracking_code', 'Your tracking code for this order is : ' . $tracking_code);
						$successfull = 1;
					} elseif ('drafted' ==	$status_codes) {
						$successfull = 2;
					} else {
						$successfull = 3;
					}
				}
				echo esc_attr($successfull); 
			
			}
			wp_die();
		}

		/**
		 * This function creates the webhook url that allows sendbox
		 * post data back to sendbox-shipping plugin.
		 */
		public function register_routes()
		{
			register_rest_route(
				'wooss/v2',
				'/shipping',
				array(
					'methods'  => 'POST',
					'callback' => array($this, 'post_data'),
				)
			);
		}

		/**
		 * This function updates order status after a post has been made to the webhook url.
		 *
		 * @param    int $order_id Order ID.
		 * @param    string $status_code Status Code.
		 *
		 * @return object
		 */
		public function update_shipment_status($order_id, $status_code)
		{
			$order = wc_get_order($order_id);
			if (null === $status_code) {
				$order->update_status('on-hold');
			}

			if ('accepted' === $status_code) {
				$order->update_status('processing');
			}

			if ('delivered' === $status_code) {
				$order->update_status('completed');
			}
			if ('rejected' === $status_code) {
				$order->update_status('failed');
			}

			if ('cancelled' === $status_code) {
				$order->update_status('cancelled');
			}

			return $order;
		}

		/**
		 * This function process the data posted from sendbox to the plugin
		 *
		 * @param  mixed $request WP_REST_Request.
		 *
		 * @return mixed
		 */
		public function post_data(WP_REST_Request $request)
		{

			$all = $request->get_json_params();

			$order_id          = $all['package']['reference_code'];
			$status_code       = $all['status_code'];
			$shipment          = $this->update_shipment_status($order_id, $status_code);
			$payload_data_json = wp_json_encode($shipment);

			return $payload_data_json;
		} 
		
			/**
		 * This function gets states from the sendbox call
		 */
		public function request_states(){
		    

		    
		    // this control will check if the value gotten from ajax is really there
		    if (isset($_POST["data"]) && wp_verify_nonce( $_POST['security'], 'wooss-ajax-security-nonce' )){
		        
		        
		        $country_code_default = sanitize_text_field($_POST['data']);
		        update_option('wooss_selected_country_code_used',$country_code_default);
                $args                       = array(
                    'headers' => array(
                        'Content-Type'  => 'application/json',
                    ),
                );
		        $states_api_url = add_query_arg(
		            array( 'page_by'=> '{"per_page":10000}' , 'filter_by' => '{"country_code":"'.$country_code_default.'"}'), 'https://api.sendbox.co/auth/states' );
                        $states_data_obj =	wp_remote_get($states_api_url, $args);
                          
                           $states_body    = json_decode(wp_remote_retrieve_body($states_data_obj));
                          $html = "";
                           foreach($states_body as $key => $value){
                            if (is_array($value)){
                                sort($value);
                                foreach($value as $ship_key => $ship_value){
          
                                    if (isset($ship_value->code) && isset($ship_value->name)){     
                                        
                                        $html.="<option data-country-code-name='".$ship_value->code."'>".$ship_value->name."</option><br/>";
                                        
                                    }
                            
                                }
                            }
                          }
                          
                       echo $html;
		    }
		    wp_die();
		}
	}
