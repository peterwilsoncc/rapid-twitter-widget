<?php
/*
Plugin Name: Rapid Twitter Widget
Plugin URI: 
Description: Display the <a href="http://twitter.com/">Twitter</a> latest updates from a Twitter user inside a widget. 
Version: 1.3
Author: Peter Wilson, Floate Design Partners
Author URI: 
License: GPLv2
*/

define('RAPID_TWITTER_WIDGET_VERSION', '1.3');

class Rapid_Twitter_Widget extends WP_Widget {

	function __construct() {
		$widget_ops = array(
			'classname'   => 'widget_twitter widget_twitter--hidden',
			'description' => __( 'Display your tweets from Twitter')
		);
		parent::__construct( 'rapid-twitter', __( 'Rapid Twitter' ), $widget_ops );
		
		if ( is_active_widget(false, false, $this->id_base) ) {
			add_action( 'wp_head', array(&$this, 'rapid_twitter_widget_style') );
		}
		
		add_action( 'wp_enqueue_scripts', array( &$this, 'rapid_twitter_widget_script' ) );
	}
	
	function rapid_twitter_widget_style() {
		if ( ! current_theme_supports( 'widgets' ) 
			|| ! apply_filters( 'show_rapid_twitter_widget_style', true, $this->id_base ) ) {
			return;
		}
		echo "<style>.widget_twitter--hidden{display:none!important;}</style>";
		wp_register_style(
			'rapid-twitter-widget',
			plugins_url( 'rapid-twitter-widget/rapid-twitter-widget' . '.css' ),
			'',
			RAPID_TWITTER_WIDGET_VERSION,
			'all'
		);
	}

	function rapid_twitter_widget_script() {
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '-min';
		wp_register_script(
			'rapid-twitter-widget',
			plugins_url( 'rapid-twitter-widget/rapid-twitter-widget' . $suffix . '.js' ),
			'',
			RAPID_TWITTER_WIDGET_VERSION,
			true
		);

		$js_data = array(
			'ajaxurl' =>  admin_url('admin-ajax.php'),
			'sec' => wp_create_nonce( 'rapid_twitter_nonce_')
		);
		
		wp_localize_script(
			'rapid-twitter-widget',
			'RapidTwitter_config',
			$js_data
		);

	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;

		$instance['account'] = trim( strip_tags( stripslashes( $new_instance['account'] ) ) );
		$instance['account'] = str_replace( 'http://twitter.com/', '', $instance['account'] );
		$instance['account'] = str_replace( 'https://twitter.com/', '', $instance['account'] );
		$instance['account'] = str_replace( '/', '', $instance['account'] );
		$instance['account'] = str_replace( '@', '', $instance['account'] );
		$instance['account'] = str_replace( '#!', '', $instance['account'] ); // account for the Ajax URI
		$instance['title'] = strip_tags( stripslashes( $new_instance['title'] ) );
		$instance['show'] = absint( $new_instance['show'] );
		$instance['hidereplies'] = isset( $new_instance['hidereplies'] );
		$instance['includeretweets'] = isset( $new_instance['includeretweets'] );
		$instance['followbutton'] = isset( $new_instance['followbutton'] );

		return $instance;
	}

	function form( $instance ) {
		//Defaults
		$instance = wp_parse_args( (array) $instance,
			array(
				'account'     => '',
				'title'       => '',
				'show'        => 5,
				'hidereplies'	=> false,
				'followbutton'  => false,
			) );

		$account = esc_attr( $instance['account'] );
		$title = esc_attr( $instance['title'] );
		$show = absint( $instance['show'] );
		if ( $show < 1 || 20 < $show )
			$show = 5;
		$hidereplies = (bool) $instance['hidereplies'];
		$include_retweets = (bool) $instance['includeretweets'];
		$follow_button = (bool) $instance['followbutton'];

		//Title
		echo '<p>';
		echo '<label for="' . $this->get_field_id('title') . '">' . esc_html__('Title:') . '</label>';
		echo '<input class="widefat" id="' . $this->get_field_id('title') . '" name="' . $this->get_field_name('title') . '" type="text" value="' . $title . '" />';
		echo '</p>';

		//Username
		echo '<p>';
		echo '<label for="' . $this->get_field_id('account') . '">' . esc_html__('Twitter username:') . '</label>';
		echo '<input class="widefat" id="' . $this->get_field_id('account') . '" name="' . $this->get_field_name('account') . '" type="text" value="' . $account . '" />';
		echo '</p>';

		//Max Tweets
		echo '<p>';
		echo '<label for="' . $this->get_field_id('show') . '">' . esc_html__('Maximum number of tweets to show:') . '</label>';
		echo '<select id="' . $this->get_field_id('show') . '" name="' . $this->get_field_name('show') . '">';

		for ( $i = 1; $i <= 20; ++$i )
			echo "<option value='$i' " . ( $show == $i ? "selected='selected'" : '' ) . ">$i</option>";

		echo '</select>';
		echo '</p>';

		//Hide Replies
		echo '<p>';
		echo '<label for="' . $this->get_field_id('hidereplies') . '">';
		echo '<input id="' . $this->get_field_id('hidereplies') . '" class="checkbox" type="checkbox" name="' . $this->get_field_name('hidereplies') . '"';
		if ( $hidereplies )
			echo ' checked="checked"';
		echo ' /> ' . esc_html__('Hide replies');
		echo '</label>';
		echo '</p>';

		//Include Retweets
		echo '<p>';
		echo '<label for="' . $this->get_field_id('includeretweets') . '"><input id="' . $this->get_field_id('includeretweets') . '" class="checkbox" type="checkbox" name="' . $this->get_field_name('includeretweets') . '"';
		if ( $include_retweets )
			echo ' checked="checked"';
		echo ' /> ' . esc_html__('Include retweets');
		echo '</label>';
		echo '</p>';

		//Follow button
		echo '<p>';
		echo '<label for="' . $this->get_field_id('followbutton') . '"><input id="' . $this->get_field_id('followbutton') . '" class="checkbox" type="checkbox" name="' . $this->get_field_name('followbutton') . '"';
		if ( $follow_button )
			echo ' checked="checked"';
		echo ' /> ' . esc_html__('Display Follow button');
		echo '</label>';
		echo '</p>';
	}

	function widget( $args, $instance ) {
		global $rapid_twitter_controller;
		extract( $args );
		
		$account = trim( urlencode( $instance['account'] ) );
		if ( empty( $account ) ) return;
		$title = apply_filters( 'widget_title', $instance['title'] );
		if ( empty( $title ) ) $title = __( 'Twitter Updates' );
		$show = absint( $instance['show'] );  // # of Updates to show
		if ( $show > 200 ) {
			// Twitter paginates at 200 max tweets. update() should not have accepted greater than 20
			$show = 200;
		}
		$hidereplies = (bool) $instance['hidereplies'] ? 't' : 'f';
		$include_retweets = (bool) $instance['includeretweets'] ? 't' : 'f';
		$follow_button = (bool) $instance['followbutton'];

		echo $before_widget;

		echo $before_title;
		if ( $follow_button ) {
			echo esc_html($title);
		} else {
			echo "<a href='" . esc_url( "https://twitter.com/{$account}" ) . "'>" . esc_html($title) . "</a>";
		}
		echo $after_title;


		$args = array(
			'screen_name' => $account,
			'count' => $show,
			'exclude_replies' => $hidereplies,
			'include_rts' => $include_retweets,
			'include_entities' => 't',
			'trim_user' => 't'
		);
		ksort($args);
		$query_string = http_build_query( $args );

		$url_ref = $rapid_twitter_controller->url_reference( $args );
		
		$callback_args = $args;
		$callback_args['reference'] = $url_ref;
		
		$rapid_twitter_controller->add_json_callback($callback_args);
		
		$widget_ref = '';
		$widget_ref .= $args['widget_id'];
		$widget_ref .= '__';
		$widget_ref .= $instance['title'];
		$widget_ref .= '__';
		$widget_ref .= $url_ref;
		
		$script_id = hash( 'md5', $widget_ref );
		$script_id = base_convert( $script_id, 16, 36 );

		echo '<script>';
		echo 'if(typeof(RapidTwitter)==\'undefined\'){';
		echo 'RapidTwitter={};RapidTwitter.apis={};';
		echo '}';
		echo 'if(typeof(RapidTwitter.apis.' . $url_ref . ')==\'undefined\'){';
		echo 'RapidTwitter.apis.' . $url_ref . '={';
		echo 'screen_name:\'' . esc_js( $account ) . '\'';
		echo ',count:\'' . esc_js( $show ) . '\'';
		echo ',exclude_replies:\'' . esc_js( $hidereplies ) . '\'';
		echo ',include_rts:\'' . esc_js( $include_retweets ) . '\'';
		echo ',widgets: []';
		echo '};';
		echo '}';
		echo 'RapidTwitter.apis.' . $url_ref . '.widgets.push(\'' . $script_id . '\');';
		echo '</script>';
		echo '<div id="' . $script_id . '"></div>';
		wp_enqueue_script( 'rapid-twitter-widget' );
		if ( $follow_button ) {
			wp_enqueue_style( 'rapid-twitter-widget' );
			echo '<a target="_blank" class="rapid-twitter-btn" title="Follow @' . $account . ' on Twitter" href="https://twitter.com/' . $account . '">' . "\n";
			echo '<i></i><span class="label">Follow @' . $account . '</span></a>' . "\n";
		}
		echo $after_widget;
		
	}

}

add_action( 'widgets_init', 'rapid_twitter_widget_init' );

function rapid_twitter_widget_init() {
	register_widget( 'Rapid_Twitter_Widget' );
}

class Rapid_Twitter_Controller {
	
	private $options;
	private $callbacks;
	private $json_feeds;
	
	function __construct() {
		add_action( 'admin_menu', array( &$this, 'init_settings_page' ) );
		add_action( 'admin_init', array( &$this, 'init_options' ) );
		
		add_action( 'wp_ajax_rapid_twitter', array( &$this, 'ajax_rapid_twitter' ) ); //logged in
		add_action( 'wp_ajax_nopriv_rapid_twitter', array( &$this, 'ajax_rapid_twitter' ) ); //logged out
		
	}

	function init_settings_page() {
		add_options_page(
			'Rapid Twitter Widget Settings',
			'Rapid Twitter Widget',
			'manage_options',
			'rapid-twitter-widget-settings',
			array( &$this, 'output_settings_page' )
		);
	}
	
	function init_options() {
		add_settings_section(
			'rapid_twitter_widget_api',
			'Twitter API Details',
			array( &$this, 'output_options_intro' ),
			'rapid-twitter-widget-settings'
		);
		
		add_settings_field(
			'rapid_twitter_widget_key', 
			'Twitter consumer key', 
			array( &$this, 'output_key_field'), 
			'rapid-twitter-widget-settings',
			'rapid_twitter_widget_api'
		);

		add_settings_field(
			'rapid_twitter_widget_secret', 
			'Twitter consumer secret', 
			array( &$this, 'output_secret_field'), 
			'rapid-twitter-widget-settings',
			'rapid_twitter_widget_api'
		);
	}

	function output_settings_page() {
		?>
		<div class="wrap">
			<?php screen_icon(); ?>
			<h2>Rapid Twitter Widget Settings</h2>
			<form method="post" action="<?php echo admin_url( 'options-general.php?page=rapid-twitter-widget-settings' ) ?>">
				<?php
					if ( ( 'update' == $_REQUEST['action'] ) && ( 'rapid-twitter-widget-settings' == $_REQUEST['page'] ) ) {
						$this->set_options();
					}
					else {
						// read the settings
						$this->get_options();
					}
					// This prints out all hidden setting fields
					settings_fields('rapid_twitter_widget_option_group');
					do_settings_sections('rapid-twitter-widget-settings');
				?>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
	
	function output_options_intro() {
		?>
		<p>
			To use the Rapid Twitter Widget, you need to 
			<a href="https://dev.twitter.com/apps/new" target="_blank">create an app</a>
			on the Twitter website.
		</p>
		
		<p>
			Be sure to fill out all the fields, just enter your website as the
			callback URL.
		</p>
		<?php
	}

	function output_key_field() {
		if ( ! defined('RAPID_TWITTER_KEY') ) {
			$this->output_text_field( 'key' );
		} else {
			echo RAPID_TWITTER_KEY . '<br /><em>Globally defined in wp-config.php.</em>';
		}
	}
	
	function output_secret_field(){
		if ( ! defined('RAPID_TWITTER_SECRET') ) {
			$this->output_text_field( 'secret' );
		} else {
			echo RAPID_TWITTER_SECRET . '<br /><em>Globally defined in wp-config.php.</em>';
		}
	}
	
	function output_text_field( $id ) {
		$val = esc_attr( $this->options[$id] );
		
		echo '<input class="text" type="text" ';
		echo 'id="rapid_twitter_widget_api_' . $id . '" ';
		echo 'name="rapid_twitter_widget_api[' . $id . ']" ';
		echo 'size="30" value="' . $val . '"/>';
	}
	
	function get_options() {
		$options = &$this->options;
		
		if ( ! defined( 'RAPID_TWITTER_KEY' ) && ! defined ( 'RAPID_TWITTER_SECRET' ) ) {
			$options = get_option( 'rapid_twitter_widget_api' );
		} else {
			$options['key'] = RAPID_TWITTER_KEY;
			$options['secret'] = RAPID_TWITTER_SECRET;
		}
		
		if ( $options['key'] AND $options['secret'] ) {
			// get the token
			$access_token = $this->get_token();
		}
		
		if ( $access_token ) {
			$options['access_token'] = $access_token;
		}
	}
	
	function get_token() {
		$options = &$this->options;
		
		if ( !$options['key'] OR !$options['secret'] ) {
			return false;
		}
		
		if ( isset($options['access_token']) ) {
			return $options['access_token'];
		}
		
		$key_encode = str_replace('%7E', '~', rawurlencode ( $options['key'] ) );
		$secret_encode = str_replace('%7E', '~', rawurlencode ( $options['secret'] ) );
		
		$signature = base64_encode( $key_encode . ':' . $secret_encode );
		
		$token = get_site_transient( 'rapid_twitter_widget_token' );
		
		if ( $token AND ( $token['signature'] != $signature ) ) {
			unset( $token );
			delete_transient( 'rapid_twitter_widget_token' );
		}
		
		if ( !$token ) {
			$http_header['Authorization'] = 'Basic ' . $signature;
			$http_header['Content-Type'] = 'application/x-www-form-urlencoded;charset=UTF-8';
			
			$http_body = 'grant_type=client_credentials';
			
			$http_args['headers'] = $http_header;
			$http_args['body'] = $http_body;
			
			$http_url = 'https://api.twitter.com/oauth2/token';
			
			$response = wp_remote_post( $http_url, $http_args );
			
			$response_code = wp_remote_retrieve_response_code( $response );
			
			if ( '200' == $response_code ) {
				$response_body = wp_remote_retrieve_body( $response );
				$token = json_decode( $response_body, true );
				$token['signature'] = $signature;
				
				set_site_transient('rapid_twitter_widget_token', $token, 60*60*12 );
				
			}
		}
		
		return $token['access_token'];
		
	}
	
	function set_options() {
		$options = &$this->options;
		if ( !wp_verify_nonce( $_POST['_wpnonce'], 'rapid_twitter_widget_option_group-options' ) ) {
			echo '<div class="error"><p>Unable to verify form submission. Settings will not be saved.</p></div>';
			return;
		}

		$options = $_REQUEST['rapid_twitter_widget_api'];

		
		//unset the access token and recheck before saving.
		unset( $options['access_token'] );
		
		$access_token = $this->get_token();
		
		if ( $access_token ) {
			//the key & secret are valid
			update_option( 'rapid_twitter_widget_api', $options );
			echo '<div class="updated"><p>Twitter application updated.</p></div>';
			
			//return the access token to the options array
			$options['access_token'] = $access_token;
			
			return true;
		}
		else {
			echo '<div class="error"><p>API settings invalid. Please try again.</p></div>';
			
			return false;
		}
	}

	function url_reference( $args ) {
		$defaults = array(
			'count' => 5,
			'exclude_replies' => 'f',
			'include_rts' => 't',
			'include_entities' => 't',
			'trim_user' => 't'
		);
		
		$args = wp_parse_args( $args, $defaults );
		// make sure the order is always the same
		ksort( $args );

		$query_string = http_build_query( $args );
		$numbers = array( '1', '2', '3', '4', '5', '6', '7', '8', '9', '0' );
		$letters = array( 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z' );

		$url_reference = md5( $query_string );
		$url_reference = base_convert( $url_reference, 16, 26 );
		$url_reference = str_replace( $numbers, $letters, $url_reference );

		return $url_reference;
	}

	function get_twitter_feed( $args ) {
		$options = &$this->options;
		
		if ( !$options['key'] OR !$options['secret'] ) {
			$this->get_options();
		}
		
		if ( !$options['access_token'] OR !$options['key'] OR !$options['secret'] ) {
			//something has gone wrong
			return false;
		}
		
		$defaults = array(
			'count' => 5,
			'exclude_replies' => 'f',
			'include_rts' => 't',
			'include_entities' => 't',
			'trim_user' => 't'
		);
		
		$args = wp_parse_args( $args, $defaults );
		// make sure the order is always the same
		ksort( $args );
		
		if ( !$args['screen_name'] ) {
			// nothing to get
			return false;
		}
		
		$http_header['Authorization'] = 'Bearer ' . $options['access_token'];
		
		$http_body = '';
		
		$http_args['headers'] = $http_header;
		$http_args['body'] = $http_body;

		$query_string = http_build_query( $args );
		
		$http_url = 'https://api.twitter.com/1.1/statuses/user_timeline.json?';
		$http_url .= $query_string;
		$http_url = esc_url_raw( $http_url );
		

		$url_reference = $this->url_reference( $args );
		$transient_name = 'rapid_twitter_' . $url_reference;
		$transient_name = substr( $transient_name, 0, 45 );

		$transient_backup = 'rapid_twi_bup_' . $url_reference;
		$transient_backup = substr( $transient_backup, 0, 45 );
		
		
		$tweets = get_site_transient( $transient_name );
		
		if ( !$tweets ) {
			$response = wp_remote_get( $http_url, $http_args );
			$response_code = wp_remote_retrieve_response_code( $response );
			$response_body = wp_remote_retrieve_body( $response );
			
			$tweets = false;
			
			switch ( $response_code ) {
				case 200 : // process tweets and display
					$tweets = json_decode( $response_body, true );

					if ( ! is_array( $tweets ) || isset( $tweets['error'] ) ) {
						$tweet_cache_expire = 300;
						break;
					} else {
						set_site_transient( $transient_backup, $tweets, 86400 ); // A one day backup in case there is trouble talking to Twitter
					}

					$cache_for =  900; 
					break;
				case 401 : // display private stream notice
					$tweets = array();
					$tweets['error'] = 'Private account';
					$tweets['code'] = $response_code;
					$tweets['request'] = $url_reference;
					$cache_for = 300;
					break;
				case 401 :
				case 404 :
				case 410 :
				case 420 :
				case 429 :
					// Wait out this error, it's permanent of some nature
					$tweets = array();
					$tweets['error'] = 'Permanent error';
					$tweets['code'] = $response_code;
					$tweets['request'] = $url_reference;
					$cache_for = 300;
					break;
				
				default :  // display an error message
					$tweets = get_site_transient( $transient_backup );
					$cache_for = 300;
					break;
			}
			
			if ( $cache_for != 0 ) {
				set_site_transient( $transient_name, $tweets, $cache_for ); /* cache for 5 min */
			}
			
		}
		return $tweets;
	}
	
	function add_json_callback( $args ) {
		$reference = $args['reference'];
		unset( $args['reference'] );
		$this->callbacks[$reference] = $args;
	}

	function ajax_rapid_twitter() {
		
		check_ajax_referer( 'rapid_twitter_nonce_', 's' );
		
		header('Content-Type: application/javascript;charset=utf-8');
		
		$args = array(
			'screen_name' => urlencode ( $_REQUEST['screen_name'] ),
			'count' => intval( $_REQUEST['count'] ),
			'exclude_replies' => $_REQUEST['exclude_replies'],
			'include_rts' => $_REQUEST['include_rts'],
			'include_entities' => 't',
			'trim_user' => 't'
		);
		
		if ( $args['exclude_replies'] == 'true' ) {
			$args['exclude_replies'] = 't';
		}
		if ( $args['exclude_replies'] == 'false' ) {
			$args['exclude_replies'] = 'f';
		}
		
		if ( ( $args['exclude_replies'] != 't' ) AND ( $args['exclude_replies'] != 'f' ) ) {
			$args['exclude_replies'] = '';
		}

		if ( $args['include_rts'] == 'true' ) {
			$args['include_rts'] = 't';
		}
		if ( $args['include_rts'] == 'false' ) {
			$args['include_rts'] = 'f';
		}

		if ( ( $args['include_rts'] != 't' ) AND ( $args['include_rts'] != 'f' ) ) {
			$args['include_rts'] = '';
		}
		
		

		
		switch(1) {
			case ( trim( $args['screen_name'] ) == '' ):
			case ( is_numeric( $args['count'] ) == false ):
			case ( trim( $args['exclude_replies'] ) == '' ):
			case ( trim( $args['include_rts'] ) == '' ):
				header("HTTP/1.0 400 Bad Request");
				die();
				break;
		}
		
		$reference = sanitize_key( $_REQUEST['callback'] );
		
		
		$tweets = $this->get_twitter_feed( $args );
		
		$cache_for = 5 * 60; //cache for 5 minutes
		$expires = gmdate("D, d M Y H:i:s", time() + $cache_for) . " GMT";
		
		if ( $tweets != false ) {
			header("Cache-Control: max-age=" . $cache_for . "");
			header("Expires: " . $expires);
			header("Pragma: public");
			$jsondata = json_encode( $tweets );
		}
		else {
			$error_msg['request'] = $reference;
			$error_msg['error'] = 'Not Found';
			$jsondata = json_encode( $error_msg );
		}
		
		echo 'RapidTwitter.callback.' . $reference . '(';
		echo $jsondata;
		echo ');';

		
		die();
	}
}

$rapid_twitter_controller = new Rapid_Twitter_Controller();
