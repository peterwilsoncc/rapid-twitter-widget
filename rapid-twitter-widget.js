RapidTwitter= RapidTwitter || {};

RapidTwitter.script = function(RapidTwitter, window, document) {
	var widgets = RapidTwitter.widgets,
		widget_length = widgets.length,
		scripts = {},
		s, i, widget,script_name,script_source, scripts_length,script;
	
	for ( i=0; i < widget_length; i++ ) {
		widget = widgets[i];

		script_name = '';

		script_name += widget.count;
		script_name += '_';
		script_name += widget.screen_name;
		script_name += '_';
		script_name += widget.exclude_replies;
		script_name += '_';
		script_name += widget.include_rts;
		
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
		script_source += 'callback=RapidTwitter.callback';
		
		scripts[script_name] = script_source;
		
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
	    } else if (object.forEach instanceof Function) {
	      // the object implements a custom forEach method so use that
	      object.forEach(block, context);
	      return;
	    } else if (typeof object.length == "number") {
	      // the object is array-like
	      resolve = Array;
	    }
	    resolve.forEach(object, block, context);
	  }
	};
	
	
	forEach (scripts, function(script) {
		var tw = document.createElement('script');
		tw.type = 'text/javascript';
		tw.async = true;
		tw.src = script;
		s = document.getElementsByTagName('script')[0]; 
		s.parentNode.insertBefore(tw, s);
	});
	
	function callback() {
		
	}
	RapidTwitter.callback = callback;
}(RapidTwitter, window, document);