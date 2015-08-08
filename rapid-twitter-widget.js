if(typeof(RapidTwitter)=='undefined'){RapidTwitter={};}

RapidTwitter.script = function(RapidTwitter, window, document) {
	var apis = RapidTwitter.apis,
		i;
	
	function callback(api, tweets) {
		if ( typeof tweets.error != 'undefined' ) {
			return;
		}
		
		var widgets = api.widgets,
			widgets_len = widgets.length,
			the_html = '';

		the_html = generate_html(api.screen_name, tweets);
			
		for (var i=0; i<widgets_len; i++) {
			var element = widgets[i],
				ul = document.createElement('ul');
			element = document.getElementById(element);
			
			ul.className = 'tweets';
			ul.innerHTML = the_html;
			element.appendChild(ul);

			removeClass(element.parentNode, 'widget_twitter--hidden');
		}
	}
	RapidTwitter.callback = callback;

	function generate_html(screen_name, tweets){
		var the_html = '';
		if ( typeof RapidTwitter.generate_html == 'function' ) {
			return RapidTwitter.generate_html(screen_name, tweets);
		}
		for (var i=0, l=tweets.length; i<l; i++) {
			var use_tweet = tweets[i], 
				rt_html = '',
				classes = ['tweet'];

			if (typeof use_tweet.user.screen_name == 'undefined') {
				use_tweet.user.screen_name = screen_name;
			}

			if (typeof use_tweet.retweeted_status != 'undefined') {
				use_tweet = use_tweet.retweeted_status;
				classes.push('tweet--retweet');

				if (typeof use_tweet.user.screen_name == 'undefined') {
					var mentions = tweets[i].entities.user_mentions,
						mentions_length = mentions.length,
						mention_position = 256; //any number over 140 works
					for (var j=0; j<mentions_length; j++) {
						if (mentions[j].indices[0] < mention_position) {
							mention_position = mentions[j].indices[0];
							use_tweet.user.screen_name = mentions[j].screen_name;
						}
					}
				}

				
				rt_html += 'RT ';
				rt_html += '<a href="';
				rt_html += 'https://twitter.com/';
				rt_html += use_tweet.user.screen_name;
				rt_html += '" class="tweet__mention tweet__mention--retweet">';
				rt_html += '<span>@</span>';
				rt_html += use_tweet.user.screen_name;
				rt_html += '</a>';
				rt_html += ': ';
			}
			
			if (use_tweet.in_reply_to_screen_name != null) {
				classes.push('tweet--reply');
			}

			the_html += '<li class="';
			the_html += classes.join(' ');
			the_html += '">';
			the_html += rt_html;
			the_html += process_entities(use_tweet);
			
			
			the_html += ' ';
			the_html += '<a class="tweet__datestamp timesince" href="';
			the_html += 'https://twitter.com/';
			the_html += use_tweet.user.screen_name;
			the_html += '/status/';
			the_html += use_tweet.id_str;
			the_html += '">';
			the_html += relative_time(use_tweet.created_at);
			the_html += '</a>';
			the_html += '</li>';
		}
		return the_html;
	}


	function relative_time(time_value) {
		var split_date = time_value.split(" "),
			the_date = new Date(split_date[1] + " " + split_date[2] + ", " + split_date[5] + " " + split_date[3] + " UTC"),
			now = new Date(),
			delta = (now.getTime() - the_date.getTime()) / 1000,
			monthNames = [ "Jan", "Feb", "Mar", "Apr", "May", "Jun",
				"Jul", "Aug", "Sep", "Oct", "Nov", "Dec" ];
		
		if(delta < 60) {
			return 'less than a minute ago';
		}
		else if(delta < 120) {
			return 'about a minute ago';
		}
		else if(delta < (45*60)) {
			return (parseInt(delta / 60)).toString() + ' minutes ago';
		}
		else if(delta < (90*60)) {
			return 'about an hour ago';
		}
		else if(delta < (24*60*60)) {
			return 'about ' + (parseInt(delta / 3600)).toString() + ' hours ago';
		}
		else if(delta < (48*60*60)) {
			return '1 day ago';
		}
		else {
			return the_date.getDate() + ' ' + monthNames[the_date.getMonth()];
			// return (parseInt(delta / 86400)).toString() + ' days ago';
		}
	}
	RapidTwitter.relative_time = relative_time;
	
	// source: https://gist.github.com/1292496
	// Takeru Suzuki
	function process_entities (tweet) {
		var result = [],
			entities = [],
			lastIndex = 0,
			key,
			i,
			len,
			elem;

		for (key in tweet.entities) {
			for (i = 0, len = tweet.entities[key].length; i < len; i++) {
				elem = tweet.entities[key][i];
				entities[elem.indices[0]] = {
					end: elem.indices[1],
					text: function () {
						switch (key) {
							case 'media':
								return '<a href="' + elem.url + '" class="tweet__media" title="' + elem.expanded_url + '">' + elem.display_url + '</a>';
								break;
							case 'urls':
								var display_url;
								display_url = (elem.display_url) ? elem.display_url : elem.url;
								return (elem.display_url)? '<a href="' + elem.url + '" class="tweet__link" title="' + elem.expanded_url + '">' + display_url + '</a>': elem.url;
								break;
							case 'user_mentions':
								var reply_class = (elem.indices[0] == 0) ? ' tweet__mention--reply' : '';
								return '<a href="https://twitter.com/' + elem.screen_name + '" class="tweet__mention'+reply_class+'"><span>@</span>' + elem.screen_name + '</a>';
								break;
							case 'hashtags':
								return '<a href="https://twitter.com/search?q=%23' + elem.text + '" class="tweet__hashtag"><span>#</span>' + elem.text + '</a>';
								break;
							case 'symbols':
								return '<a href="https://twitter.com/search?q=%24' + elem.text + '" class="tweet__symbols"><span>$</span>' + elem.text + '</a>';
								break;
							default:
								return elem.text;
						}
					}()
				};
			}
		}
		
		for (i = 0, len = entities.length; i < len; i++) {
			if (entities[i]) {
				elem = entities[i];
				result.push(tweet.text.substring(lastIndex, i));
				result.push(elem.text);
				lastIndex = elem.end;
				i = elem.end - 1;
			}
		}
		
		result.push(tweet.text.substring(lastIndex));
		return result.join('');
	}	
	RapidTwitter.process_entities = process_entities;

	function removeClass(element, class_name) {
		var regexp = new RegExp('(\\s|^)'+class_name+'(\\s|$)');
		element.className = element.className.replace(regexp, ' ');
	}

	for (var outer_key in apis) {
			
		(function(){
			var key = outer_key,
				api = apis[key],
				config = RapidTwitter_config,
				tw = document.createElement('script'),
				s, script_source;

			script_source = config.ajaxurl;
			script_source += '?';

			script_source += 'count=';
			script_source += api.count;
			script_source += '&';
			script_source += 'screen_name=';
			script_source += api.screen_name;
			script_source += '&';
			script_source += 'exclude_replies=';
			script_source += api.exclude_replies;
			script_source += '&';
			script_source += 'include_rts=';
			script_source += api.include_rts;
			script_source += '&';
			script_source += 'include_entities=';
			script_source += 't';
			script_source += '&';
			script_source += 'trim_user=';
			script_source += 't';
			script_source += '&';
			script_source += 'suppress_response_codes=';
			script_source += 't';
			script_source += '&';
			script_source += 'callback=' + key;
			script_source += '&';
			script_source += 'action=rapid_twitter';
			script_source += '&';
			script_source += 's=' + config.sec;


			RapidTwitter.callback[key] = function(tweets) {callback(api,tweets);};

			tw.type = 'text/javascript';
			tw.async = true;
			tw.src = script_source;
			s = document.getElementsByTagName('script')[0]; 
			s.parentNode.insertBefore(tw, s);

		})();
	}
	
}(RapidTwitter, window, document);
