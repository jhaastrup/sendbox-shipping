<?php

/**
 * This class makes a call to sendbox API
 */
class Wooss_Sendbox_Shipping_API
{
	/**
	 * Connect_to_api.
	 *
	 * @param  mixed $api_url API URL.
	 * @param  mixed $args arguments.
	 * @param  mixed $method_type GET|POST.
	 *
	 * @return mixed
	 */
	public function connect_to_api($api_url, $args, $method_type)
	{
		if ('GET' == $method_type) {
			$response = wp_remote_get(esc_url_raw($api_url), $args);
		} elseif ('POST' == $method_type) {
			$response = wp_remote_post(esc_url_raw($api_url), $args);
		}

		return $response;
	}

	/**
	 * This function allow to get response code from  api
	 */
	/**
	 * Get_api_response_code.
	 *
	 * @param  mixed $api_url API URL.
	 * @param  mixed $args Parameters, Array is required with credentials.
	 * @param  mixed $method (GET|POST).
	 *
	 * @return object
	 */
	public function get_api_response_code($api_url, $args, $method)
	{
		$api_call      = $this->connect_to_api($api_url, $args, $method);
		$response_code = wp_remote_retrieve_response_code($api_call);
		return $response_code;
	}

	/**
	 * This function gets body content from  api.
	 *
	 * @param  mixed $api_url API URL.
	 * @param  mixed $args Parameters, Array is required with credentials.
	 * @param  mixed $method (GET|POST).
	 *
	 * @return object
	 */
	public function get_api_response_body($api_url, $args, $method)
	{
		$api_call      = $this->connect_to_api($api_url, $args, $method);
		$response_body = json_decode(wp_remote_retrieve_body($api_call));
		return $response_body;
	}

	/**
	 * This function returns the necessary url that needs Sendbox.
	 *
	 * @param  mixed $url_type URL Type.
	 *
	 * @return string
	 */
	public function get_sendbox_api_url($url_type)
	{
		if ('delivery_quote' == $url_type) {
			$url = 'https://live.sendbox.co/shipping/shipment_delivery_quote';
		} elseif ('countries' == $url_type) {
			$url = 'https://api.sendbox.co/auth/countries?page_by={' . '"per_page"' . ':264}';
		} elseif ('states' == $url_type) {
			$url = 'https://api.sendbox.co/auth/states';
		} elseif ('shipments' == $url_type) {
			$url = 'https://live.sendbox.co/shipping/shipments';
		} elseif ('item_type' == $url_type) {
			$url = 'https://api.sendbox.ng/v1/item_types';
		} elseif ('profile' == $url_type) {
			$url = 'https://live.sendbox.co/oauth/profile';
		} else {
			$url = '';
		}
		return $url;
	}


	/**
 * 
 * This function checks sendbox oauth 
 */

public static function checkAuth(){ 

	$api_key = get_option('sendbox_data')['sendbox_auth_token'];
	$profile_url = "https://live.sendbox.co/oauth/profile";

	$profile_res = wp_remote_get( $profile_url ,
	array( 'timeout' => 40,
   'headers' => array( 'Content-Type' => 'application/json',
					  'Authorization'=> $api_key ) 
	));
	//var_dump($profile_res);
	$profile_obj = json_decode($profile_res['body']);

   if(isset($profile_obj->title)){
   
   //make a new request to oauth 
   $s_url = 'https://live.sendbox.co/oauth/access/access_token/refresh?';
   //('sendbox_data')['sendbox_auth_token']
   $app_id = get_option('sendbox_data')['sendbox_app_id'];
   $client_secret = get_option('sendbox_data')['sendbox_client_secret'];
   $url_oauth = $s_url.'app_id='.$app_id.'&client_secret='.$client_secret; 
   $refresh_token = get_option('sendbox_data')['sendbox_refresh_token'];

   $oauth_res = wp_remote_get( $url_oauth,
	array( 'timeout' => 10,
   'headers' => array( 'Content-Type' => 'application/json',
					  'Refresh-Token'=> $refresh_token ) 
	));
	$oauth_obj = json_decode($oauth_res['body']);
   if(isset($oauth_obj->access_token)){
	   $new_auth = $oauth_obj->access_token; 
	   $sendbox_new_auth = get_option('sendbox_data');
	   $sendbox_new_auth['sendbox_auth_token']= $new_auth;
	   update_option('sendbox_data',$sendbox_new_auth);
	   
   }
   if(isset($oauth_obj->refresh_token)){
	   $new_refresh = $oauth_obj->refresh_token;
	   $sendbox_new_refresh = get_option('sendbox_data');
	   $sendbox_new_refresh['sendbox_refresh_token'] = $new_refresh;
	   update_option('sendbox_data',  $sendbox_new_refresh);
   }
   
}

else{
   $api_key = get_option('sendbox_data')['sendbox_auth_token'];
}

return $api_key;

	 
}



	/**
	 * Static function for getting nigeria states.
	 *
	 * @return array
	 */
	public function get_nigeria_states()
	{
		$state = array('Abia', 'Adamawa', 'Akwa Ibom', 'Anambra', 'Bauchi', 'Benue', 'Borno', 'Bayelsa', 'Cross River', 'Delta', 'Ebonyi ', 'Edo', 'Ekiti', 'Enugu ', 'Federal Capital Territory', 'Gombe ', 'Jigawa ', ' Imo ', ' Kaduna', 'Kebbi ', 'Kano', ' Kogi', ' Lagos', 'Katsina', 'Kwara', 'Nasarawa', 'Niger ', 'Ogun', 'Ondo ', 'Rivers', 'Oyo', 'Osun', 'Sokoto', 'Plateau', 'Taraba', 'Yobe', 'Zamfara');
		return $state;
	}  


} 


