<?php
/*
Plugin Name: Rapid Twitter Widget
Plugin URI: 
Description: Display the <a href="http://twitter.com/">Twitter</a> latest updates from a Twitter user inside a widget. 
Version: 1.0
Author: Floate Design Partners, Peter Wilson
Author URI: 
License: GPLv2
*/

class Rapid_Twitter_Widget extends WP_Widget {

	function Rapid_Twitter_Widget() {
		$widget_ops = array('classname' => 'rapid-twitter', 'description' => __( 'Display your tweets from Twitter') );
		parent::WP_Widget('twitter', __('Twitter'), $widget_ops);
	}


}


add_action( 'widgets_init', 'rapid_twitter_widget_init' );
function rapid_twitter_widget_init() {
	register_widget('Rapid_Twitter_Widget');
}
?>