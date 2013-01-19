RapidTwitter= RapidTwitter || {};

RapidTwitter.script = function(RapidTwitter, window, document) {
	var apis = RapidTwitter.apis,
		s, i,script_source;
	
	function callback(api, tweets) {
		var ids = api.widgets,
			ids_el = ids.length,
			the_html = '';


		for (var i=0; i<tweets.length; i++) {
			var the_text = '', the_date, the_screen_name, tweet_id;
			if (typeof tweets[i].retweeted_status == 'undefined') {
				the_text += tweets[i].text;
				
				the_date = tweets[i].created_at;
				the_screen_name = tweets[i].user.screen_name;
				tweet_id = tweets[i].id_str;
			}
			else {
				//this ensures the text isn't truncated by long user names
				the_text += 'RT ';
				the_text += '@' + tweets[i].retweeted_status.user.screen_name;
				the_text += ': ';
				the_text += tweets[i].retweeted_status.text;
				the_date = tweets[i].retweeted_status.created_at;
				the_screen_name = tweets[i].retweeted_status.user.screen_name
				tweet_id = tweets[i].retweeted_status.id_str;
			}


			the_html += '<li>';
			the_html += linkify_tweet(the_text);
			
			
			the_html += ' ';
			the_html += '<span class="timesince">';
			the_html += '<a href="';
			the_html += 'https://twitter.com/';
			the_html += the_screen_name;
			the_html += '/status/';
			the_html += tweet_id;
			the_html += '">';
			the_html += relative_time(the_date);
			the_html += '</a>';
			the_html += '</span>';
			the_html += '</li>';
		}


			
		for (var i=0; i<ids_el; i++) {
            elements = getElementsByClass(ids[i]);
			
			var elements_length = elements.length;

			for (var j = 0; j < elements_length; j++) {
				var element = elements[j],
					ul = document.createElement('ul');
				removeClass(element, 'widget_twitter--hidden');
				
				ul.className = 'tweets';
				ul.innerHTML = the_html;
				element.appendChild(ul);
			}
		}
	}
	RapidTwitter.callback = callback;


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
	
	function linkify_tweet(text) {
		var link_exp = /(^|\s)(\b(https?|ftp|file):\/\/[-A-Z0-9+&@#\/%?=~_|!:,.;]*[-A-Z0-9+&@#\/%=~_|])/ig;
		text = text.replace(link_exp, " <a href='$2'>$2</a> ");
		text = text.replace(/(^|\s|.)@(\w+)/g, " $1<a href=\"https://twitter.com/$2\">@$2</a> ");
		text = text.replace(/(^|\s)#(\w+)/g, " $1<a href=\"https://twitter.com/search?q=%23$2&src=hash\">#$2</a> ");
		text = text.replace(/(^|\s)\$([A-Za-z]+)/g, " $1<a href=\"https://twitter.com/search?q=%24$2&src=ctag\">&#36;$2</a> ");
		return text;
 	}
    
	function getElementsByClass(get_class_name) {
		var elements;
		if (document.getElementsByClassName) {
			elements = document.getElementsByClassName(get_class_name);
		}
		else if (document.querySelectorAll) {
			elements = document.querySelectorAll('.' + get_class_name);
		}
		else {
			var all = document.getElementsByTagName('*'),
				all_length = all.length,
				regexp = new RegExp('(\\s|^)'+get_class_name+'(\\s|$)');
			elements = new Array();
			for (var j = 0; j < all_length; j++) {
				var el = all[j];
				if ( regexp.test(el.className) ) {
					elements.push(el);
				}
			}
		}
		
		return elements;
	}

	function removeClass(element, class_name) {
		var regexp = new RegExp('(\\s|^)'+class_name+'(\\s|$)');
		element.className = element.className.replace(regexp, ' ');
	}

	//source: http://dean.edwards.name/weblog/2006/07/enum/
	// generic enumeration
	Function.prototype.forEach = function(object, block, context) {
		for (var key in object) {
			if (typeof this.prototype[key] == "undefined") {
				block.call(context, object[key], key, object);
			}
		}
	};

	// globally resolve forEach enumeration
	var forEach = function(object, block, context) {
		if (object) {
			var resolve = Object; // default
			if (object instanceof Function) {
				
				// functions have a "length" property
				resolve = Function;
			} 
			else if (object.forEach instanceof Function) {
				// the object implements a custom forEach method so use that
				object.forEach(block, context);
				return;
			}
			else if (typeof object.length == "number") {
				// the object is array-like
				resolve = Array;
			}
			resolve.forEach(object, block, context);
		}
	};
	
	
	forEach (apis, function (api) {
		script_source = 'http://api.twitter.com/1/statuses/user_timeline.json?';

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
		script_source += 'true';
		script_source += '&';
		script_source += 'callback=RapidTwitter.callback.' + api.ref + '';


		RapidTwitter.callback[api.ref] = function(tweets) {callback(api,tweets);};

		var tw = document.createElement('script');
		tw.type = 'text/javascript';
		tw.async = true;
		tw.src = script_source;
		s = document.getElementsByTagName('script')[0]; 
		s.parentNode.insertBefore(tw, s);

	});
	
	
}(RapidTwitter, window, document);