RapidTwitter= RapidTwitter || {};

RapidTwitter.script = function(RapidTwitter, window, document) {
	var widgets = RapidTwitter.widgets,
		s, i,script_source;
	
	function callback(widget, data) {
		// data = window.data;
		var ids = widget.widgets,
			ids_el = ids.length,
			the_html = '';


		for (var k=0; k<data.length; k++) {
			var the_text = '', the_date, the_screen_name;
			if (typeof data[k].retweeted_status == 'undefined') {
				the_text += linkify_tweet(data[k].text);
				the_date = relative_time(data[k].created_at);
				the_screen_name = data[k].user.screen_name;
			}
			else {
				//this ensures the text isn't truncated by long user names
				the_text += 'RT ';
				the_text += linkify_tweet('@' + data[k].retweeted_status.user.screen_name);
				the_text += ': ';
				the_text += linkify_tweet(data[k].retweeted_status.text);
				the_date = relative_time(data[k].retweeted_status.created_at);
				the_screen_name = data[k].retweeted_status.user.screen_name
			}


			the_html += '<li>';
			the_html += the_text;
			
			
			the_html += ' ';
			the_html += '<span class="timesince">';
			the_html += '<a href="';
			the_html += 'https://twitter.com/';
			the_html += the_screen_name;
			the_html += '/status/';
			the_html += data[k].id_str;
			the_html += '">';
			the_html += the_date;
			the_html += '</a>';
			the_html += '</span>';
			the_html += '</li>';
		}


			
		for (var i=0; i<ids_el; i++) {
			var list;
			if (document.getElementsByClassName) {
				list = document.getElementsByClassName(ids[i]);
			}
			else if (document.querySelectorAll) {
				list = document.querySelectorAll('.' + ids[i]);
			}
			else {
				var all = document.getElementsByTagName('*'),
					all_length = all.length,
					regexp = new RegExp('(\\s|^)'+ids[i]+'(\\s|$)');
				list = new Array();
				for (var j = 0; j < all_length; j++) {
					var el = all[j];
					if ( regexp.test(el.className) ) {
						list.push(el);
					}
				}
			}
            
			
			var regexp = new RegExp('(\\s|^)'+'rapid-twitter--hidden'+'(\\s|$)'),
				elements_length = list.length;

			for (var j = 0; j < elements_length; j++) {
				var el = list[j];
				el.className = el.className.replace(regexp, ' ').replace(/\s{2,}/g,' ').replace(/^\s+|\s+$/g,'');
				
				var ul = document.createElement('ul');
				ul.className = 'tweets';
				ul.innerHTML = the_html;
				el.appendChild(ul);
			}
		}
	}
	RapidTwitter.callback = callback;


	function relative_time(time_value) {
		var split_date = time_value.split(" ");
		var the_date = new Date(split_date[1] + " " + split_date[2] + ", " + split_date[5] + " " + split_date[3] + " UTC");
		var relative_to = new Date();
		var delta = (relative_to.getTime() - the_date.getTime()) / 1000;
		
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
			var monthNames = [ "Jan", "Feb", "Mar", "Apr", "May", "Jun",
				"Jul", "Aug", "Sep", "Oct", "Nov", "Dec" ];
			return the_date.getDate() + ' ' + monthNames[the_date.getMonth()];
			// return (parseInt(delta / 86400)).toString() + ' days ago';
		}
	}
	
	function linkify_tweet(tweet) {
		var link_exp = /(^|\s)(\b(https?|ftp|file):\/\/[-A-Z0-9+&@#\/%?=~_|!:,.;]*[-A-Z0-9+&@#\/%=~_|])/ig;
		tweet = tweet.replace(link_exp, " <a href='$2'>$2</a> ");
		tweet = tweet.replace(/(^|\s|.)@(\w+)/g, " $1<a href=\"https://twitter.com/$2\">@$2</a> ");
		tweet = tweet.replace(/(^|\s)#(\w+)/g, " $1<a href=\"https://twitter.com/search?q=%23$2&src=hash\">#$2</a> ");
		tweet = tweet.replace(/(^|\s)\$([A-Za-z]+)/g, " $1<a href=\"https://twitter.com/search?q=%24$2&src=ctag\">&#36;$2</a> ");
		return tweet;
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
	
	
	forEach (widgets, function kitten(widget) {
		script_source = 'http://api.twitter.com/1/statuses/user_timeline.json?';

		script_source += 'count=';
		script_source += widget.count;
		script_source += '&';
		script_source += 'screen_name=';
		script_source += widget.screen_name;
		script_source += '&';
		script_source += 'exclude_replies=';
		script_source += widget.exclude_replies;
		script_source += '&';
		script_source += 'include_rts=';
		script_source += widget.include_rts;
		script_source += '&';
		script_source += 'callback=RapidTwitter.callback.' + widget.ref + '';


		RapidTwitter.callback[widget.ref] = function(data) {callback(widget,data);};

		var tw = document.createElement('script');
		tw.type = 'text/javascript';
		tw.async = true;
		tw.src = script_source;
		s = document.getElementsByTagName('script')[0]; 
		s.parentNode.insertBefore(tw, s);

	});
	
	
}(RapidTwitter, window, document);