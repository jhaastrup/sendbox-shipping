<?php

/**
 * WooCommerce Sendbox Shipping Method.
 *
 * @return mixed $string
 */
function wooss_shipping_method()
{
	if (!class_exists('Wooss_Shipping_Method')) {
		/**
		 * WooCommerce Sendbox Shipping Method Init Class.
		 *
		 * @return mixed $string
		 */
		class Wooss_Shipping_Method extends WC_Shipping_Method
		{

			/**
			 * Class constructor
			 *
			 * @param  mixed $instance_id Instance id.
			 *
			 * @return void
			 */
			public function __construct($instance_id = 0)
			{

				$this->id                   = 'wooss';
				$this->instance_id          = absint($instance_id);
				$this->method_title         = __('Sendbox Shipping', 'wooss');
				$this->method_description   = __('Sendbox Custom Shipping Method for Woocommerce', 'wooss');
				$this->single_rate          = 0;
				$this->supports             = array(
					'shipping-zones',
					'instance-settings',
					'settings',
					'instance-settings-modal',
				);
				$this->version              = WOOSS_VERSION;
				$this->init_form_fields();
				$this->init_settings();
				$this->enabled          = isset($this->settings['enabled']) ? $this->settings['enabled'] : 'yes';
				$this->instance_form_fields = array(
					'enabled' => array(
						'title'   => __('Enable/Disable', 'wooss'),
						'type'    => 'checkbox',
						'label'   => __('Enable this shipping method'),
						'default' => $this->enabled,
					),
				);
				$this->title            = isset($this->settings['title']) ? $this->settings['title'] : __('Sendbox Shipping', 'wooss');
				$this->shipping_options = 'wooss_eee';
				if (null !== $this->enabled) {
					update_option('wooss_option_enable', $this->enabled);
				}
				add_action('woocommerce_update_options_shipping_' . $this->id, array($this, 'process_admin_options'));
			}


			/**
			 * Init methods shipping forms.
			 *
			 * @return void
			 */
			function init_form_fields()
			{

				$this->form_fields = array(
					'enabled' => array(
						'title'   => __('Enable/Disable'),
						'type'    => 'checkbox',
						'label'   => __('Enable this shipping method'),
						'default' => 'yes',
					),
				);
				add_action('woocommerce_update_options_shipping_' . $this->id, array($this, 'process_admin_options'));
			}

			/**
			 * Calculate  fees for the shipping method on the frontend.
			 *
			 * @param  mixed $package : Current order data.
			 *
			 * @return void
			 */
			public function calculate_shipping($package = array())
			{
				if (is_cart() || is_checkout()) {
					$api_call         = new Wooss_Sendbox_Shipping_API();
					$quotes_fee       = 0;
					$fee              = 0;
					$quantity         = 0;
					$items_lists      = [];
					$wooss_extra_fees = esc_attr(get_option('wooss_extra_fees'));
					foreach ($package['contents'] as $item_id => $values) {
						if (!empty($values['data']->get_weight())) {
							$weight = $values['data']->get_weight();
						} else {
							$weight = 0;
						}
						$fee      += round($values['line_total']);
						$quantity += $values['quantity'];

						$outputs                    = new stdClass();
						$outputs->name              = $values['data']->get_name();
						$outputs->weight            = (int) $weight;
						$outputs->package_size_code = 'medium';
						$outputs->quantity          = $values['quantity'];
						$outputs->value             = round($values['line_total']);
						$outputs->amount_to_receive = round($values['line_total']);
						$outputs->item_type         = $values['data']->get_categories();

						array_push($items_lists, $outputs);
					}

					$auth_header = get_option('wooss_basic_auth');

					if (!$auth_header){
						wc_add_notice(sprintf('<strong>Unable to get shipping fees at this time.</strong>'), 'error');
					}

					$origin_country = get_option('wooss_country');

					$origin_state = get_option('wooss_states_selected');

					$origin_street = get_option('wooss_store_address');

					$origin_city = get_option('wooss_city');

					$incoming_option_code = get_option('wooss_pickup_type');

					$profile_url                    = $api_call->get_sendbox_api_url('profile');
					$profile_args                   = array(
						'headers' => array(
							'Content-Type'  => 'application/json',
							'Authorization' => $auth_header,
						),
					);
					$response_code_from_profile_api = $api_call->get_api_response_code($profile_url, $profile_args, 'GET');
					$response_body_from_profile_api = $api_call->get_api_response_body($profile_url, $profile_args, 'GET');
					
					if (200 === $response_code_from_profile_api) {
						$origin_name  = $response_body_from_profile_api->name;
						$origin_phone = $response_body_from_profile_api->phone;
						$origin_email = $response_body_from_profile_api->email;
					}
					$date = new DateTime();
					$date->modify('+1 day');
					$pickup_date = $date->format(DateTime::ATOM);

					$destination_state_code = $package['destination']['country'];
					$destination_city       = $package['destination']['city'];
					$destination_street     = $package['destination']['address'];
					if (empty($destination_street)) {
						$destination_street = __('Customer street');
					}
					$destination_name  = __('Customer X', 'wooss');
					$destination_phone = __('00000000', 'wooss');
					

					$destination_country = wooss_get_countries($package,'country');
					$destination_state = wooss_get_countries($package,'state');

					if (preg_match('/\s\(\w+\)/', $destination_country) == true) {
						$destination_country = preg_replace('/\s\(\w+\)/', '', $destination_country);
					}

					if (preg_match('/(\s\(\w+\)\s)|(\s\(\w+\))|(\(\w+\)\s)|(\(\w+\))/', $destination_country) == true) {
						$destination_country = preg_replace('/(\s\(\w+\)\s)|(\s\(\w+\))|(\(\w+\)\s)|(\(\w+\))/', '', $destination_country);
					}

					if (preg_match('/United States/', $destination_country) == true) {
						$destination_country = "United States of America";
					}

					if (empty($destination_state)) {
						$destination_state = $package['destination']['state'];
					}
					if (empty($destination_city)) {
						$destination_city = $package['destination']['city'];
					}

					// if (empty($destination_state)) {
					// 	$destination_state = $destination_state;
					// }
	

					$weight = $quantity * $weight;
					$payload_array_data = array(
						'destination_name' => $destination_name,
						'destination_phone' => $destination_phone,
						'destination_country' => $destination_country,
						'destination_state' => $destination_state,
						'destination_street' => $destination_street,
						'destination_city' => $destination_city,
						'items_list' => $items_lists,
						'weight' => $weight,
						'amount_to_receive' => (int) $fee,
						'origin_country' => $origin_country,
						'origin_name' => $origin_name,
						'origin_street' => $origin_street,
						'origin_state' => $origin_state,
						'origin_phone' => $origin_phone,
						'origin_city' => $origin_city,
						'deliver_priority_code' => 'next_day',
						'deliver_type_code' => 'last_mile',
						'payment_option_code' => 'prepaid',
						'incoming_option_code' => $incoming_option_code,
						'pickup_date' => $pickup_date,
					);

							
					$delivery_quotes_details = wooss_calculate_shipping($api_call,$payload_array_data,$auth_header);
					
					$wooss_rates_type = get_option('wooss_rates_type');
					if ("maximum" == $wooss_rates_type && isset($delivery_quotes_details->max_quoted_fee) ) {
						$quotes_fee = $delivery_quotes_details->max_quoted_fee;
					} elseif ("minimum" == $wooss_rates_type && isset($delivery_quotes_details->min_quoted_fee) ) {
						$quotes_fee = $delivery_quotes_details->min_quoted_fee;
					}

					$quoted_fee = $quotes_fee + $wooss_extra_fees;
					
				
					$destination_country = "United States of America";
					$destination_state = "Washington";
					$destination_city = "New York";
					$destination_postcode = "10001";
					$payload_array_data['destination_country'] = $destination_country;
					$payload_array_data['destination_state'] = $destination_state;
					$payload_array_data['destination_city'] = $destination_city;
					
				
					if (floatval($quoted_fee) == 0){
						$delivery_quotes_details = wooss_calculate_shipping($api_call,$payload_array_data,$auth_header);
						if ("maximum" == $wooss_rates_type && isset($delivery_quotes_details->max_quoted_fee) ) {
							$quotes_fee = $delivery_quotes_details->max_quoted_fee;
						} elseif ("minimum" == $wooss_rates_type && isset($delivery_quotes_details->min_quoted_fee) ) {
							$quotes_fee = $delivery_quotes_details->min_quoted_fee;
						}
	
						$quoted_fee = $quotes_fee + $wooss_extra_fees;
					}
					
					$new_rate = array(
						'id'      => $this->id,
						'label'   => $this->title,
						'cost'    => $quoted_fee,
						'package' => $package,
					);

					$this->add_rate($new_rate);
				}
			}
		}
	}
}
add_action('woocommerce_shipping_init', 'wooss_shipping_method');
/**
 * This function is responsible to display class in the settings.
 *
 * @param  mixed $methods Load
 *
 * @return array
 */
function add_wooss_shipping_method($methods)
{
	$methods['wooss'] = 'Wooss_Shipping_Method';
	return $methods;
}
add_filter('woocommerce_shipping_methods', 'add_wooss_shipping_method');



add_action('woocommerce_settings_tabs_shipping', 'wooss_form_fields', 100);

/**
 * This function handles the display of the forms only on sendbox shipping page.
 *
 * @return void
 */
function wooss_form_fields()
{
	$shipping_methods_enabled = get_option('wooss_option_enable');
	if (isset($_GET['tab']) && $_GET['tab'] == 'shipping' &&  isset($_GET['section']) && $_GET['section'] == 'wooss' && $shipping_methods_enabled == 'yes') {
		$api_call                   = new Wooss_Sendbox_Shipping_API();
		$auth_header                = esc_attr(get_option('wooss_basic_auth'));
		$args                       = array(
			'headers' => array(
				'Content-Type'  => 'application/json',
				'Authorization' => $auth_header,
			),
		);
		$profile_api_url            = 'https://api.sendbox.ng/v1/merchant/profile';
		$profile_data_response_code = $api_call->get_api_response_code($profile_api_url, $args, 'GET');
		$profile_data_response_body = $api_call->get_api_response_body($profile_api_url, $args, 'GET');
		$wooss_username             = '';
		$wooss_email                = '';
		$wooss_tel                  = '';
		if (200 == $profile_data_response_code) {
			$wooss_username = $profile_data_response_body->name;
			$wooss_email    = $profile_data_response_body->email;
			$wooss_tel      = $profile_data_response_body->phone;
		}
		$wc_city             = get_option('woocommerce_store_city');
		$wc_store_address    = get_option('woocommerce_store_address');
		$wooss_city          = get_option('wooss_city');
		$wooss_store_address = get_option('wooss_store_address');
		$wooss_basic_auth    = get_option('wooss_basic_auth');
		$wc_extra_fees       = (int) get_option('wooss_extra_fees');

		if (null == $wooss_city) {
			$wooss_city = $wc_city;
		}
		if (null == $wooss_store_address) {
			$wooss_store_address = $wc_store_address;
		}
		$wooss_states_selected = get_option('wooss_states_selected');
		if (null == $wooss_states_selected) {
			$wooss_states_selected = '';
		}
		$wooss_country = get_option('wooss_country');
		if (null == $wooss_country) {
			$wooss_country = 'Nigeria';
		}
		$wooss_connection_status = get_option('wooss_basic_auth');
		$custom_styles           = '';
		if (null != $wooss_connection_status) {
			$custom_styles = 'display:none';
		}
		$wooss_display_fields = get_option('wooss_connexion_status');
		if ( $wooss_display_fields) {
			$display_fields = 'display : inline';
			$hide_button    = 'display : none';
		}
		$wooss_pickup_type = get_option('wooss_pickup_type');
		if (null == $wooss_pickup_type) {
			$wooss_pickup_type = 'pickup';
		}

		if (null == $wc_extra_fees) {
			$wc_extra_fees = 0;
		}

		$wooss_rates_type = get_option('wooss_rates_type');
		if (null == $wooss_rates_type) {
			$wooss_rates_type = 'maximum';
		}
		$wooss_rate_type = array("maximum", "minimum");









		$wooss_pickup_types = array('pickup', 'drop-off');
		$nigeria_states     = $api_call->get_nigeria_states();


		?>

		<div class="wooss-shipping-settings" >

			<strong><label for="wooss_basic_auth"><?php esc_attr_e('API KEY :', 'wooss'); ?> </label><input type="text" class="wooss-text" placeholder="Basic X0000X0000000000AH" name="wooss_basic_auth" value="<?php esc_attr_e($wooss_basic_auth, 'wooss'); ?>"></strong> <br />
			<button type="submit" class="button-primary wooss-connect-sendbox wooss_fields" style="<?php esc_attr_e($custom_styles); ?>"><?php esc_attr_e('Connect to Sendbox', 'wooss'); ?></button><br />

       <div class="wooss_necessary_fields" style="<?php  $display_fields = 'display : none'; if ($wooss_display_fields) {$display_fields = 'display : inline'; echo $display_fields;} else { echo $display_fields; }?>">
				<table style="width:100%">

					<tr>
						<td>
							<strong><label for="wooss_username"><?php esc_attr_e('Name : ', 'wooss'); ?> </label></strong>
						</td>
						<td>
							<input readonly type="text" class="wooss-text" placeholder="John Doe" name="wooss_username" id="wooss_username" value="<?php esc_attr_e($wooss_username, 'wooss'); ?>" required>
						</td>
					</tr>


					<tr>
						<td>
							<strong><label for="wooss_tel"><?php esc_attr_e('Phone Number : ', 'wooss'); ?> </label></strong>
						</td>
						<td>
							<input readonly type="tel" class="wooss-text" placeholder="+2340000000000" id="wooss_tel" name="wooss_tel" value="<?php esc_attr_e($wooss_tel, 'wooss'); ?>" required>
						</td>
					</tr>

					<tr>
						<td>
							<strong><label for="wooss_email"><?php esc_attr_e('Email : ', 'wooss'); ?> </label></strong>
						</td>
						<td>
							<input readonly type="email" class="wooss-text" placeholder="johndoe@gmail.com" id="wooss_email" name="wooss_email" value="<?php esc_attr_e($wooss_email, 'wooss'); ?>" required>
						</td>
					</tr>

					<tr>
						<td>
							<strong><label for="wooss_country"><?php esc_html_e('Country : ', 'wooss'); ?></label></strong>
						</td>
						<td>
							<select class="wooss_country_select wooss_selected">
								<option value="<?php esc_attr_e($wooss_country, 'wooss'); ?>" selected><?php esc_html_e($wooss_country, 'wooss'); ?></option>
							</select>
						</td>
					</tr>

					<tr>
						<td>
							<strong><label for="wooss_city"><?php esc_attr_e('City : ', 'wooss'); ?></label></strong>
						</td>
						<td>
							<input type="text" class="wooss-text" name="wooss_city" value="<?php echo esc_attr_e($wc_city); ?>">
						</td>
					</tr>

					<tr>

						<td>
							<strong><label for="wooss_state"><?php esc_attr_e('State : ', 'wooss'); ?></label></strong>
						</td>
						<td>
							<?php
							echo "<select class='wooss_state_dropdown wooss_fields wooss_selected' name='wooss_state_dropdown'>";
							foreach ($nigeria_states as $state) {
								$states_selected = (preg_match("/$wooss_states_selected/", $state) == true) ? 'selected="selected"' : '';
								echo "<option value='$state' $states_selected>$state</option>";
							}
							echo '</select>';
							?>
						</td>
					</tr>

					<tr>
						<td>
							<strong><label for="wooss_state"><?php esc_attr_e('Pickup types : ', 'wooss'); ?></label></strong>
						</td>
						<td>
							<?php
							echo "<select class='wooss_pickup_type wooss_fields wooss_selected' name='wooss_pickup_type'>";
							foreach ($wooss_pickup_types as $pickup_types) {
								$types_selected = (preg_match("/$wooss_pickup_type/", $pickup_types) == true) ? 'selected="selected"' : '';
								echo "<option value='$pickup_types' $types_selected>$pickup_types</option>";
							}
							echo '</select>';
							?>
						</td>
					</tr>

					<tr>
						<td>
							<strong><label for="wooss_state"><?php esc_attr_e('Rates Type: ', 'wooss'); ?></label></strong>
						</td>
						<td>
							<?php
							echo "<select class='wooss_rates_type wooss_fields wooss_selected' name='wooss_rates_type'>";

							foreach ($wooss_rate_type as $rates_type) {
								$types_selected = (preg_match("/$wooss_rates_type/", $rates_type) == true) ? 'selected="selected"' : '';
								echo "<option value='$rates_type' $types_selected>$rates_type</option>";
							}
							echo '</select>';
							?>
						</td>
					</tr>

					<tr>
						<td>
							<strong><label for="wooss_street"><?php esc_attr_e('Street : ', 'wooss'); ?></label></strong>
						</td>
						<td>
							<input type="text" size="100" class="wooss-text" name="wooss_street" value="<?php esc_attr_e($wc_store_address); ?>">
						</td>
					</tr>

					<tr>
						<td>
							<strong><label for="wooss_extra_fees"><?php esc_attr_e('Extra fees : ', 'wooss'); ?></label></strong>
						</td>
						<td>
							<input class="wooss-text" type="number" id="wooss_extra_fees" name="wooss_extra_fees" value="<?php esc_attr_e($wc_extra_fees); ?>">
						</td>
					</tr>
				</table>
				<button type="submit" class="button-primary wooss_save_button"><?php esc_attr_e('Sync changes', 'wooss'); ?></button>

			</div>
			
			<span class="wooss_errors_pages wooss_fields"></span>
		</div>
	<?php
	}
}

add_action('wp_ajax_connect_to_sendbox', 'connect_to_sendbox');

/**
 * AJAX function used to get status code from sendbox.
 *
 * @return void
 */
function connect_to_sendbox()
{
	$response_code    = 0;
	if (isset($_POST['data'])) {
		$data             = wp_unslash($_POST['data']);
		$wooss_basic_auth = $data['wooss_basic_auth'];
		$api_call               = new Wooss_Sendbox_Shipping_API();
		$api_url                = $api_call->get_sendbox_api_url('profile');
		$args                   = array(
			'headers' => array(
				'Content-Type'  => 'application/json',
				'Authorization' => $wooss_basic_auth,
			),
		);
		$response_code_from_api = $api_call->get_api_response_code($api_url, $args, 'GET');
		if (200 === $response_code_from_api) {
			$response_code = 1;
			update_option('wooss_connexion_status', $response_code);
			update_option('wooss_basic_auth', $wooss_basic_auth);
		}
	}
	esc_attr_e($response_code);
	wp_die();
}

add_action('wp_ajax_save_fields_by_ajax', 'save_fields_by_ajax');
/**
 * Function  for saving fields into db using ajax.
 *
 * @return mixed $string
 */
function save_fields_by_ajax()
{
	$operation_success = 0;
	if (isset($_POST['data']) &&  wp_verify_nonce($_POST['security'], 'wooss-ajax-security-nonce')) {
		$data              =  wp_unslash($_POST['data']);
		$wooss_country     = sanitize_text_field($data['wooss_country']);
		$wooss_state       = sanitize_text_field($data['wooss_state_name']);
		$wooss_city        = sanitize_text_field($data['wooss_city']);
		$wooss_street      = sanitize_text_field($data['wooss_street']);
		$wooss_basic_auth  = sanitize_text_field($data['wooss_basic_auth']);
		$wooss_pickup_type = sanitize_text_field($data['wooss_pickup_type']);
		$wooss_extra_fees  = sanitize_text_field($data['wooss_extra_fees']);
		$wooss_rates_type = sanitize_text_field($data['wooss_rates_type']);

		if (isset($wooss_city)) {
			update_option('wooss_city', $wooss_city);
			$operation_success = 1;
		}
		if (isset($wooss_extra_fees)) {
			update_option('wooss_extra_fees', $wooss_extra_fees);
			$operation_success = 1;
		}
		if (isset($wooss_country)) {
			update_option('wooss_country', $wooss_country);
			$operation_success = 1;
		}
		if (isset($wooss_state)) {
			update_option('wooss_states_selected', $wooss_state);
			$operation_success = 1;
		}
		if (isset($wooss_street)) {
			update_option('wooss_store_address', $wooss_street);
			$operation_success = 1;
		}
		if (isset($wooss_basic_auth)) {
			update_option('wooss_basic_auth', $wooss_basic_auth);
			$operation_success = 1;
		}
		if (isset($wooss_pickup_type)) {
			update_option('wooss_pickup_type', $wooss_pickup_type);
			$operation_success = 1;
		}



		if (isset($wooss_rates_type)) {
			update_option('wooss_rates_type', $wooss_rates_type);
			$operation_success = 1;
		}
		update_option('wooss_display_fields', $operation_success);
	}
	esc_attr_e($operation_success);
	wp_die();
}


/* add_filter( 'woocommerce_thankyou', 'woosb_update_order_status', 10, 1 );

function woosb_update_order_status( $order_id ) {
  if ( !$order_id ){
    return;
  }
  
  $order = new WC_Order( $order_id );
  if ( 'processing' == $order->get_status() ) {
      if (!current_user_can('administrator')){
        $order->update_status( 'on-hold' );
      }
  }
  return $order_id;
}
 */
function action_wooss_checkout_update_order_review($array, $int){
     
     //DO SOMETHING HERE???

     WC()->cart->calculate_shipping();
    
    return;
}
//add_action('woocommerce_checkout_update_order_review','action_wooss_checkout_update_order_review', 10, 2);



/**
 *	This function get the shipping fees from SendBox.
 *
 * @return object|array
 */
function wooss_calculate_shipping($api_obj,$payload_array_data,$authorization_key){
	$payload_data                        = new stdClass();
	foreach($payload_array_data as $key => $value){
		switch($key){
			case 'destination_name':
				$payload_data->destination_name      = $value;
			break;

			case 'destination_country':
				$payload_data->destination_country       = $value;
			break;

			case 'destination_state' :
				$payload_data->destination_state     = $value;
			break;

			case 'destination_city':
				$payload_data->destination_city     = $value;
			break;

			case 'destination_street':
				$payload_data->destination_street     = $value;
			break;

			case 'destination_phone':
				$payload_data->destination_phone     = $value;
			break;

			case 'items_list':
				$payload_data->items     = $value;
			break;
	
			case 'weight':
				$payload_data->weight     = $value;
			break;

			case 'amount_to_receive':
				$payload_data->amount_to_receive     = $value;
			break;

			case 'origin_country':
				$payload_data->origin_country     = $value;
			break;

			case 'origin_state':
				$payload_data->origin_state     = $value;
			break;

			case 'origin_name':
				$payload_data->origin_name     = $value;
			break;


			case 'origin_phone':
				$payload_data->origin_phone     = $value;
			break;

			case 'origin_street':
				$payload_data->origin_street     = $value;
			break;

			case 'origin_city':
				$payload_data->origin_city     = $value;
			break;

			case 'deliver_priority_code':
				$payload_data->deliver_priority_code     = $value;
			break;


			case 'pickup_date':
				$payload_data->pickup_date     = $value;
			break;

			case 'incoming_option_code':
				$payload_data->incoming_option_code     = $value;
			break;

			case 'payment_option_code':
				$payload_data->payment_option_code     = $value;
			break;

			case 'deliver_type_code':
				$payload_data->deliver_type_code     = $value;
			break;

			default:
			break;
		}
	}
	

	$payload_data_json = json_encode($payload_data);

	$delivery_args = array(
		'headers' => array(
			'Content-Type'  => 'application/json',
			'Authorization' => $authorization_key,
		),
		'body'    => $payload_data_json,
	);

	$delivery_quotes_url = $api_obj->get_sendbox_api_url('delivery_quote');
	$delivery_quotes_details = $api_obj->get_api_response_body($delivery_quotes_url, $delivery_args, 'POST');

	return $delivery_quotes_details;
}

/**
 * This function is for getting countries or state name. 
 *
 * @param array $package
 * @param string $type
 * @return string
 */
function wooss_get_countries($package,$type){

	$countries_obj       = new WC_Countries();
	$destination_states  = $countries_obj->get_states($package['destination']['country']);
	$destination_country = $countries_obj->get_shipping_countries($package['destination']['country']);
	$delivery_state = "";

	if (empty($destination_states)) {
		$destination_states = array('');
	}

	if (empty($destination_country)) {
		$destination_country = array('');
	}

	if('state' == $type){
		foreach ($destination_states as $states_code => $states_name) {
			if ($package['destination']['state'] === $states_code) {
				$delivery_state = $states_name;
				break;
			}
		}
	}elseif('country' == $type){
		foreach ($destination_country as $destination_country_code => $country_name) {
			if ($package['destination']['country'] === $destination_country_code) {
				$delivery_state = $country_name;
				break;
			}
		}
	}
	return $delivery_state;
	
}