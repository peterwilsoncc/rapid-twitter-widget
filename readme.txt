=== Rapid Twitter Widget ===
Contributors: floatedesign, peterwilsoncc
Tags: twitter, widget, tweets
Stable tag: 0.3.1
Requires at least: 3.4.2
Tested up to: 
License: GPLv2

Display tweets from one or more Twitter accounts a WordPress widget.

== Description ==

Display the latest Tweets from your Twitter accounts inside WordPress widgets. Customize Tweet displays using your site or theme CSS.

Tweets are loaded after the page content to ensure a delayed response from Twitter doesn't slow down your website.

Based upon Wickett Twitter Widget by Automattic (now part of Jetpack)


== Frequently Asked Questions ==

= Why have you re-written the Wickett Twitter Widget plugin? =

The Wickett Twitter Widget has been grandfathered by Automattic and moved into their 
mega-plugin Jetpack. I like the simplicity of the Twitter widget by do not wish to
use other Jetpack features.

I switched to using JavaScript to load tweets as the original widget could slow down
page load if Twitter's API was taking too long to respond. 

= Can multiple instances of the widget be used? =

Yes.

= Can private Twitter accounts be used? =

No. The widget does not support authenticated requests for private data.

= I see less than the requested number of Tweets displayed =

Twitter may return less than the requested number of Tweets if the requested account has a high number of @replies in its user timeline.

== Changelog ==

