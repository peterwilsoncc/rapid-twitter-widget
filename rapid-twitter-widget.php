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

	function form( $instance ) {
		//Defaults
		$instance = wp_parse_args( (array) $instance, array('account' => '', 'title' => '', 'show' => 5, 'hidereplies' => false) );

		$account = esc_attr($instance['account']);
		$title = esc_attr($instance['title']);
		$show = absint($instance['show']);
		if ( $show < 1 || 20 < $show )
			$show = 5;
		$hidereplies = (bool) $instance['hidereplies'];
		$include_retweets = (bool) $instance['includeretweets'];
		$before_timesince = esc_attr($instance['beforetimesince']);

		echo '<p><label for="' . $this->get_field_id('title') . '">' . esc_html__('Title:') . '
		<input class="widefat" id="' . $this->get_field_id('title') . '" name="' . $this->get_field_name('title') . '" type="text" value="' . $title . '" />
		</label></p>
		<p><label for="' . $this->get_field_id('account') . '">' . esc_html__('Twitter username:') . ' <a href="http://support.wordpress.com/widgets/twitter-widget/#twitter-username" target="_blank">( ? )</a>
		<input class="widefat" id="' . $this->get_field_id('account') . '" name="' . $this->get_field_name('account') . '" type="text" value="' . $account . '" />
		</label></p>
		<p><label for="' . $this->get_field_id('show') . '">' . esc_html__('Maximum number of tweets to show:') . '
			<select id="' . $this->get_field_id('show') . '" name="' . $this->get_field_name('show') . '">';

		for ( $i = 1; $i <= 20; ++$i )
			echo "<option value='$i' " . ( $show == $i ? "selected='selected'" : '' ) . ">$i</option>";

		echo '		</select>
		</label></p>
		<p><label for="' . $this->get_field_id('hidereplies') . '"><input id="' . $this->get_field_id('hidereplies') . '" class="checkbox" type="checkbox" name="' . $this->get_field_name('hidereplies') . '"';
		if ( $hidereplies )
			echo ' checked="checked"';
		echo ' /> ' . esc_html__('Hide replies') . '</label></p>';

		echo '<p><label for="' . $this->get_field_id('includeretweets') . '"><input id="' . $this->get_field_id('includeretweets') . '" class="checkbox" type="checkbox" name="' . $this->get_field_name('includeretweets') . '"';
		if ( $include_retweets )
			echo ' checked="checked"';
		echo ' /> ' . esc_html__('Include retweets') . '</label></p>';

		echo '<p><label for="' . $this->get_field_id('beforetimesince') . '">' . esc_html__('Text to display between tweet and timestamp:') . '
		<input class="widefat" id="' . $this->get_field_id('beforetimesince') . '" name="' . $this->get_field_name('beforetimesince') . '" type="text" value="' . $before_timesince . '" />
		</label></p>';
	}



}


add_action( 'widgets_init', 'rapid_twitter_widget_init' );
function rapid_twitter_widget_init() {
	register_widget('Rapid_Twitter_Widget');
}
?>