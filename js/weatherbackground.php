<?php define('WP_USE_THEMES', false); require('../../../../wp-blog-header.php'); header("Content-Type: text/javascript");
$options = get_option('fsbwether_options'); 

$autolocation = "";
$city = "";
$zip = "";
$woeid = "";
$unit = "c";

if($options['fsbwAutodetect']) {
	$getip = set_transient( 'getip', wp_remote_get('http://j.maxmind.com/app/geoip.js'), 60*60*1 );
	$getip = get_transient('getip');
	$autolocation = str_replace("'", "", str_replace("; }", "", str_replace("geoip_region_name()  { return ", "", str_replace("function ", "", strstr(strstr($getip['body'], 'geoip_region_name'), 'geoip_latitude', true)))));
}

if($options['fsbwZIP']){ $zip = $options['fsbwZIP']; }
if($options['fsbwWOEID']){  $woeid = $options['fsbwWOEID']; }
if($options['fsbwAutodetect'] && !empty($autolocation)){ 
	$city = $autolocation;
} else {
	$city = $options['fsbwCity']; //echo "location: '".$fsbwCity."', \n";
}
if($options['fsbwUnit']){
	$unit = $options['fsbwUnit'];
} else {
	$unit = "c";
}

if(isset($_GET['id']) && !empty($_GET['id'])){
	$custom = get_post_custom($_GET['id']);
	
	$c_disable	= $custom["fsbwCustom_Disable"][0];
	$c_widget 	= $custom["fsbwCustom_WidgetDisable"][0];
	$c_city 	= $custom["fsbwCustom_LocationCity"][0];
	$c_zip 		= $custom["fsbwCustom_LocationZIP"][0];
	$c_woeid 	= $custom["fsbwCustom_LocationWOEID"][0];
}


//DEFAULT 
if(isset($city) && !empty($city)){
    $get_weather = wp_remote_get('http://weather.yahooapis.com/forecastrss?q='.urlencode(htmlentities($city)).'&u='.$unit);
    $get_weather = set_transient( 'get_weather', $get_weather, 60*60*1 );
}
if(isset($zip) && !empty($zip)){
    $get_weather = wp_remote_get('http://weather.yahooapis.com/forecastrss?p='.urlencode(htmlentities($zip)).'&u='.$unit);
    $get_weather = set_transient( 'get_weather', $get_weather, 60*60*1 );
}
if(isset($woeid) && !empty($woeid)) {
    $get_weather = wp_remote_get('http://weather.yahooapis.com/forecastrss?w='.urlencode(htmlentities($woeid)).'&u='.$unit);
    $get_weather = set_transient( 'get_weather', $get_weather, 60*60*1 );
}

// CUSTOMS
if(isset($c_city) && !empty($c_city)){
    $get_weather = wp_remote_get('http://weather.yahooapis.com/forecastrss?q='.urlencode(htmlentities($c_city)).'&u='.$unit);
    $get_weather = set_transient( 'get_weather', $get_weather, 60*60*1 );
}
if(isset($c_zip) && !empty($c_zip)){
    $get_weather = wp_remote_get('http://weather.yahooapis.com/forecastrss?p='.urlencode(htmlentities($c_zip)).'&u='.$unit);
    $get_weather = set_transient( 'get_weather', $get_weather, 60*60*1 );
}
if(isset($c_woeid) && !empty($c_woeid)) {
    $get_weather = wp_remote_get('http://weather.yahooapis.com/forecastrss?w='.urlencode(htmlentities($c_woeid)).'&u='.$unit);
    $get_weather = set_transient( 'get_weather', $get_weather, 60*60*1 );
}


if(isset($_COOKIE["fsbw"]) && !empty($_COOKIE["fsbw"])){
	$fsbw = $_COOKIE["fsbw"];
	$citiesmenu = $options['fsbwCitiesMenu'];
	$citiesmenu = explode( ',', $citiesmenu );
	if (in_array($fsbw, $citiesmenu)) {
	    $get_weather = wp_remote_get('http://weather.yahooapis.com/forecastrss?q='.urlencode(htmlentities($fsbw)).'&u='.$unit);
	    $get_weather = set_transient( 'get_weather', $get_weather, 60*60*1 );
	} else {
		return false;
	}
}

echo "/*";
echo $fsbw;
echo "*/";

$result = get_transient('get_weather');
$xml = simplexml_load_string($result['body']);
 
//echo htmlspecialchars($result, ENT_QUOTES, 'UTF-8');
 
$xml->registerXPathNamespace('yweather', 'http://xml.weather.yahoo.com/ns/rss/1.0');

$location = $xml->channel->xpath('yweather:location');
$astronomy = $xml->channel->xpath('yweather:astronomy');
 
if(!empty($location)){
    foreach($xml->channel->item AS $item){
        $current = $item->xpath('yweather:condition');
        $forecast = $item->xpath('yweather:forecast');
        $current = $current[0];
        // Variables 
        $weather_city       = $location[0]['city'];                                                                     // City
        $weather_country    = $location[0]['country'];                                                                  // Country
        $weather_region     = $location[0]['region'];                                                                   // Region
        $weather_temp       = $current['temp'];                                                                         // Current temp
        $weather_condcode   = $current['code'];                                                                         // Current weather code
        $weather_condtext   = $current['text'];                                                                         // Current weather text
        $weather_sunrise    = date("H:i", strtotime($astronomy[0]['sunrise']));                                         // Current sunrise time 24h
        $weather_sunset     = date("H:i", strtotime($astronomy[0]['sunset']));                                          // Current sunset time 24h
        $weather_time       = date("H:i", strtotime(substr(strstr(substr($current['date'], 0, -5), date('Y')), 5)));    // Current time for the weather region
        ($weather_time>$weather_sunrise && $weather_time<$weather_sunset) ? $daynight = 'd' : $daynight = 'n' ;         // Current stage of day
        //Print output
        $output = <<<END
var we_city 	= "{$weather_city}";
var we_country 	= "{$weather_country}";
var we_region 	= "{$weather_region}";
var we_temp 	= "{$weather_temp}";
var we_code 	= "{$weather_condcode}";
var we_text 	= "{$weather_condtext}";
var we_sunrise 	= "{$weather_sunrise}";
var we_sunset 	= "{$weather_sunset}";
var we_time 	= "{$weather_time}";
var we_daytime 	= "{$daynight}";
END;
    }
} else {
    $output = '<h1>No results found, please try a different zip code.</h1>';
}
?>
/* Shards */
(function(t){var s=function(t,s,r,i,e,o,h,c,n){this.el=t,this.sharp=!0,this.fs=n,this.filter="",this.colours={c1:s,c2:r,c3:r,shade:i,alpha:c,steps:e,wheel:o,light:~~h},this.init()};s.prototype.init=function(){if(this.cssPrefix=!1,t.browser.webkit?this.cssPrefix="-webkit":t.browser.mozilla?this.cssPrefix="-moz":t.browser.opera?this.cssPrefix="-o":t.browser.msie&&(this.cssPrefix="-ms"),this.cssPrefix){for(var s=this.colours.steps;s>0;)this.percents=this.percentage(),this.stringBuilder(),this.colourFilter(),s-=1,s>0&&(this.filter+=", ");this.el.css("background-image",this.filter),this.fs&&this.fit()}else console.log("sorry bro, your browser isnt supported.")},s.prototype.stringBuilder=function(){var t=this.colours,s=this.catcol(t.c1),r=this.catcol(t.c2),i=this.catcol(t.c3),e=this.catcol(t.shade),o=~~(Math.random()*360);this.filter+=this.cssPrefix+"-linear-gradient("+o+"deg,"+s+" "+this.percents.a+" ,"+e+" "+this.percents.b+", "+r+" "+this.percents.c+", "+i+" "+this.percents.d+")"},s.prototype.catcol=function(t){var s="rgba(",r=")",i=t.toString();return s.concat(i).concat(r)},s.prototype.fit=function(){this.el.css({width:"100%",height:window.innerHeight})},s.prototype.percentage=function(){var t=~~(Math.random()*85),s=t+~~(Math.random()*15),r=s,i=100-s+~~(Math.random()*s),e={a:t+"%",b:s+"%",c:r+"%",d:i+"%"};return e},s.prototype.colourFilter=function(){var t=this.colours;t.c1=this.colstep(t.c1),t.c1.push(t.alpha),t.c2=this.colstep(t.c2),t.c2.push(t.alpha),t.c3=this.colstep(t.c2),t.c3.push(t.alpha),t.shade=this.colstep(t.shade),t.shade.push(t.alpha)},s.prototype.colstep=function(t){var s=this.hsl(t),r=this.colours.wheel,i=360*r;return this.colours.light>3&&(this.colours.light=3),s[0]=s[0]-~~(Math.random()*i/2)+~~(Math.random()*i/2),s[1]=100*r,s[2]=30*this.colours.light,this.rgb(s)},s.prototype.hsl=function(t){var s=t[0]/255,r=t[1]/255,i=t[2]/255,e=Math.max(s,r,i),o=Math.min(s,r,i),h=(e+o)/2,c=0,n=0;e!=o&&(c=.5>h?(e-o)/(e+o):(e-o)/(2-e-o),n=s==e?(r-i)/(e-o):r==e?2+(i-s)/(e-o):4+(s-r)/(e-o)),h=100*h,c=100*c,n=60*n,0>n&&(n+=360);var a=[n,c,h];return a},s.prototype.rgb=function(t){var s,r,i,e,o,h,c=t[0],n=t[1],a=t[2];return n/=100,a/=100,0==n?e=o=h=255*a:(r=.5>=a?a*(n+1):a+n-a*n,s=2*a-r,i=c/360,e=this.hue2rgb(s,r,i+1/3),o=this.hue2rgb(s,r,i),h=this.hue2rgb(s,r,i-1/3)),[Math.round(e),Math.round(o),Math.round(h)]},s.prototype.hue2rgb=function(t,s,r){var i;return 0>r?r+=1:r>1&&(r-=1),i=1>6*r?t+6*(s-t)*r:1>2*r?s:2>3*r?t+6*(s-t)*(2/3-r):t,255*i},t.fn.shards=function(r,i,e,o,h,c,n,a){var l=t(this),u=new s(l,r,i,e,o,h,c,n,a);return a&&t(window).resize(function(){u.fit()}),this.el}})(jQuery);



/* Video */
(function ($, document, window) {
	"use strict";
	function resize(that) {
		var documentHeight = $(document).height(),
			windowHeight = $(window).height();
		if (that.settings.resizeTo === 'window') {
			$(that).css('height', windowHeight);
		} else {
			if (windowHeight >= documentHeight) {
				$(that).css('height', windowHeight);
			} else {
				$(that).css('height', documentHeight);
			}
		}
	}
	function preload(that) {
		$(that.controlbox).append(that.settings.preloadHtml);
		if (that.settings.preloadCallback) {
			(that.settings.preloadCallback).call(that);
		}
	}
	function play(that) {
		var video = that.find('video').get(0),
			controller;
		if (that.settings.controlPosition) {
			controller = $(that.settings.controlPosition).find('.ui-video-background-play a');
		} else {
			controller = that.find('.ui-video-background-play a');
		}
		if (video.paused) {
			video.play();
			controller.toggleClass('ui-icon-pause ui-icon-play').text(that.settings.controlText[1]);
		} else {
			if (video.ended) {
				video.play();
				controller.toggleClass('ui-icon-pause ui-icon-play').text(that.settings.controlText[1]);
			} else {
				video.pause();
				controller.toggleClass('ui-icon-pause ui-icon-play').text(that.settings.controlText[0]);
			}
		}
	}
	function mute(that) {
		var video = that.find('video').get(0),
			controller;
		if (that.settings.controlPosition) {
			controller = $(that.settings.controlPosition).find('.ui-video-background-mute a');
		} else {
			controller = that.find('.ui-video-background-mute a');
		}
		if (video.volume === 0) {
			video.volume = 1;
			controller.toggleClass('ui-icon-volume-on ui-icon-volume-off').text(that.settings.controlText[2]);
		} else {
			video.volume = 0;
			controller.toggleClass('ui-icon-volume-on ui-icon-volume-off').text(that.settings.controlText[3]);
		}
	}
	function loadedEvents(that) {
		if (that.settings.resize) {
			$(window).on('resize', function () {
				resize(that);
			});
		}
		that.controls.find('.ui-video-background-play a').on('click', function (event) {
			event.preventDefault();
			play(that);
		});
		that.controls.find('.ui-video-background-mute a').on('click', function (event) {
			event.preventDefault();
			mute(that);
		});
		if (that.settings.loop) {
			that.find('video').on('ended', function () {
				$(this).get(0).play();
				$(this).toggleClass('paused').text(that.settings.controlText[1]);
			});
		}
	}
	function loaded(that) {
		$(that.controlbox).html(that.controls);
		loadedEvents(that);
		if (that.settings.loadedCallback) {
			(that.settings.loadedCallback).call(that);
		}
	}
	var methods = {
		init: function (options) {
			return this.each(function () {
				var that = $(this),
					compiledSource = '',
					attributes = '',
					data = that.data('video-options'),
					image,
					isArray;
				if (document.createElement('video').canPlayType) {
					that.settings = $.extend(true, {}, $.fn.videobackground.defaults, data, options);
					if (!that.settings.initialised) {
						that.settings.initialised = true;
						if (that.settings.resize) {
							resize(that);
						}
						$.each(that.settings.videoSource, function () {
							isArray = Object.prototype.toString.call(this) === '[object Array]';
							if (isArray && this[1] !== undefined) {
								compiledSource = compiledSource + '<source src="' + this[0] + '" type="' + this[1] + '">';
							} else {
								if (isArray) {
									compiledSource = compiledSource + '<source src="' + this[0] + '">';
								} else {
									compiledSource = compiledSource + '<source src="' + this + '">';
								}
							}
						});
						attributes = attributes + 'preload="' + that.settings.preload + '"';
						if (that.settings.poster) {
							attributes = attributes + ' poster="' + that.settings.poster + '"';
						}
						if (that.settings.autoplay) {
							attributes = attributes + ' autoplay="autoplay"';
						}
						if (that.settings.loop) {
							attributes = attributes + ' loop="loop"';
						}
						$(that).html('<video ' + attributes + '>' + compiledSource + '</video>');
						that.controlbox = $('<div class="ui-video-background ui-widget ui-widget-content ui-corner-all"></div>');
						if (that.settings.controlPosition) {
							$(that.settings.controlPosition).append(that.controlbox);
						} else {
							$(that).append(that.controlbox);
						}
						that.controls = $('<ul class="ui-video-background-controls"><li class="ui-video-background-play">'
							+ '<a class="ui-icon ui-icon-pause" href="#">' + that.settings.controlText[1] + '</a>'
							+ '</li><li class="ui-video-background-mute">'
							+ '<a class="ui-icon ui-icon-volume-on" href="#">' + that.settings.controlText[2] + '</a>'
							+ '</li></ul>');
						if (that.settings.preloadHtml || that.settings.preloadCallback) {
							preload(that);
							that.find('video').on('canplaythrough', function () {
								if (that.settings.autoplay) {
									that.find('video').get(0).play();
								}
								loaded(that);
							});
						} else {
							that.find('video').on('canplaythrough', function () {
								if (that.settings.autoplay) {
									that.find('video').get(0).play();
								}
								loaded(that);
							});
						}
						that.data('video-options', that.settings);
					}
				} else {
					that.settings = $.extend(true, {}, $.fn.videobackground.defaults, data, options);
					if (!that.settings.initialised) {
						that.settings.initialised = true;
						if (that.settings.poster) {
							image = $('<img class="ui-video-background-poster" src="' + that.settings.poster + '">');
							that.append(image);
						}
						that.data('video-options', that.settings);
					}
				}
			});
		},
		play: function (options) {
			return this.each(function () {
				var that = $(this),
					data = that.data('video-options');
				that.settings = $.extend(true, {}, data, options);
				if (that.settings.initialised) {
					play(that);
					that.data('video-options', that.settings);
				}
			});
		},
		mute: function (options) {
			return this.each(function () {
				var that = $(this),
					data = that.data('video-options');
				that.settings = $.extend(true, {}, data, options);
				if (that.settings.initialised) {
					mute(that);
					that.data('video-options', that.settings);
				}
			});
		},
		resize: function (options) {
			return this.each(function () {
				var that = $(this),
					data = that.data('video-options');
				that.settings = $.extend(true, {}, data, options);
				if (that.settings.initialised) {
					resize(that);
					that.data('video-options', that.settings);
				}
			});
		},
		destroy: function (options) {
			return this.each(function () {
				var that = $(this),
					data = that.data('video-options');
				that.settings = $.extend(true, {}, data, options);
				if (that.settings.initialised) {
					that.settings.initialised = false;
					if (document.createElement('video').canPlayType) {
						that.find('video').off('ended');
						if (that.settings.controlPosition) {
							$(that.settings.controlPosition).find('.ui-video-background-mute a').off('click');
							$(that.settings.controlPosition).find('.ui-video-background-play a').off('click');
						} else {
							that.find('.ui-video-background-mute a').off('click');
							that.find('.ui-video-background-play a').off('click');
						}
						$(window).off('resize');
						that.find('video').off('canplaythrough');
						if (that.settings.controlPosition) {
							$(that.settings.controlPosition).find('.ui-video-background').remove();
						} else {
							that.find('.ui-video-background').remove();
						}
						$('video', that).remove();
					} else {
						if (that.settings.poster) {
							that.find('.ui-video-background-poster').remove();
						}
					}
					that.removeData('video-options');
				}
			});
		}
	};
	$.fn.videobackground = function (method) {
		if (!this.length) {
			return this;
		}
	    if (methods[method]) {
			return methods[method].apply(this, Array.prototype.slice.call(arguments, 1));
	    }
		if (typeof method === 'object' || !method) {
			return methods.init.apply(this, arguments);
	    }
		$.error('Method ' +  method + ' does not exist on jQuery.videobackground');
	};
	$.fn.videobackground.defaults = {
		videoSource: [],
		poster: null,
		autoplay: true,
		preload: 'none',
		loop: false,
		controlPosition: null,
		controlText: ['Play', 'Pause', 'Mute', 'Unmute'],
		resize: true,
		preloadHtml: '',
		preloadCallback: null,
		loadedCallback: null,
		resizeTo: 'document'
	};
}(jQuery, document, window));



 



 
/* Yahoo weather */
(function($) {
	"use strict";
	$.extend({
		wBackground: function(options){
			options = $.extend({
				zipcode: '',
				woeid: '',
				location: 'Sofia',
				success: function(weather){},
				error: function(error) {
					$("#weather").html('<p>'+error+'</p>');
				}
			}, options);

			/*
			** OLD CALLBACK FOR VER 1.0
			*
			var now = new Date();
			var weatherUrl = 'http://query.yahooapis.com/v1/public/yql?format=json&rnd='+now.getFullYear()+now.getMonth()+now.getDay()+now.getHours()+'&diagnostics=true&callback=?&diagnostics=true&env=store%3A%2F%2Fdatatables.org%2Falltableswithkeys&q=';
			if(options.location !== '') {
				weatherUrl += 'select * from weather.forecast where location in (select id from weather.search where query="'+options.location+'") and u="'+options.unit+'"';
			} else if(options.zipcode !== '') {
				weatherUrl += 'select * from weather.forecast where location in ("'+options.zipcode+'") and u="'+options.unit+'"';
			} else if(options.woeid !== '') {
				weatherUrl += 'select * from weather.forecast where woeid='+options.woeid+' and u="'+options.unit+'"';
			} else {
				options.error("Could not retrieve weather due to an invalid WOEID or location.");
				return false;
			}
			*/
			
			/*$.getJSON(
				weatherUrl, function(data) {


					// CHECK FOR WOEID 
					if(data !== null && data.query.results !== null && data.query.results.channel.description !== 'Yahoo! Weather Error') {
			
						$.each(data.query.results, function(i, result) {
							if (result.constructor.toString().indexOf("Array") !== -1) {
								result = result[0];
							}
							var currentDate = new Date();

							var sunRise = new Date(currentDate.toDateString() +' '+ result.astronomy.sunrise);
							var sunSet = new Date(currentDate.toDateString() +' '+ result.astronomy.sunset);

							if(currentDate>sunRise && currentDate<sunSet) {
								var timeOfDay = 'd';
							} else {
								var timeOfDay = 'n';
							}
							var compass = ['N', 'NNE', 'NE', 'ENE', 'E', 'ESE', 'SE', 'SSE', 'S', 'SSW', 'SW', 'WSW', 'W', 'WNW', 'NW', 'NNW', 'N'];
							var windDirection = compass[Math.round(result.wind.direction / 22.5)];
							if(result.item.condition.temp < 80 && result.atmosphere.humidity < 40) {
								var heatIndex = -42.379+2.04901523*result.item.condition.temp+10.14333127*result.atmosphere.humidity-0.22475541*result.item.condition.temp*result.atmosphere.humidity-6.83783*(Math.pow(10, -3))*(Math.pow(result.item.condition.temp, 2))-5.481717*(Math.pow(10, -2))*(Math.pow(result.atmosphere.humidity, 2))+1.22874*(Math.pow(10, -3))*(Math.pow(result.item.condition.temp, 2))*result.atmosphere.humidity+8.5282*(Math.pow(10, -4))*result.item.condition.temp*(Math.pow(result.atmosphere.humidity, 2))-1.99*(Math.pow(10, -6))*(Math.pow(result.item.condition.temp, 2))*(Math.pow(result.atmosphere.humidity,2));
							} else {
								var heatIndex = result.item.condition.temp;
							}
							if(options.unit === "f") {
								var unitAlt = "c";
								var tempAlt = Math.round((5.0/9.0)*(result.item.condition.temp-32.0));
								var highAlt = Math.round((5.0/9.0)*(result.item.forecast[0].high-32.0));
								var lowAlt = Math.round((5.0/9.0)*(result.item.forecast[0].low-32.0));
								var tomorrowHighAlt = Math.round((5.0/9.0)*(result.item.forecast[1].high-32.0));
								var tomorrowLowAlt = Math.round((5.0/9.0)*(result.item.forecast[1].low-32.0));
							} else {
								var unitAlt = "f";
								var tempAlt = Math.round((9.0/5.0)*result.item.condition.temp+32.0);
								var highAlt = Math.round((9.0/5.0)*result.item.forecast[0].high+32.0);
								var lowAlt = Math.round((9.0/5.0)*result.item.forecast[0].low+32.0);
								var tomorrowHighAlt = Math.round((5.0/9.0)*(result.item.forecast[1].high+32.0));
								var tomorrowLowAlt = Math.round((5.0/9.0)*(result.item.forecast[1].low+32.0));
							}
			*/
							<?php echo $output; ?>

							var weather = {
			/*
								title: result.item.title,
								tempAlt: tempAlt,
								todayCode: result.item.forecast[0].code,
								timeOfDay: timeOfDay,
								units:{
									temp: result.units.temperature,
									distance: result.units.distance,
									pressure: result.units.pressure,
									speed: result.units.speed,
									tempAlt: unitAlt
								},
								currently: result.item.condition.text,
								high: result.item.forecast[0].high,
								highAlt: highAlt,
								low: result.item.forecast[0].low,
								lowAlt: lowAlt,
								forecast: result.item.forecast[0].text,
								wind:{
									chill: result.wind.chill,
									direction: windDirection,
									speed: result.wind.speed
								},
								humidity: result.atmosphere.humidity,
								heatindex: heatIndex,
								pressure: result.atmosphere.pressure,
								rising: result.atmosphere.rising,
								visibility: result.atmosphere.visibility,
								sunrise: result.astronomy.sunrise,
								sunset: result.astronomy.sunset,
								description: result.item.description,
								thumbnail: "http://l.yimg.com/a/i/us/nws/weather/gr/"+result.item.condition.code+timeOfDay+"s.png",
								
								tomorrow:{
									high: result.item.forecast[1].high,
									highAlt: tomorrowHighAlt,
									low: result.item.forecast[1].low,
									lowAlt: tomorrowLowAlt,
									forecast: result.item.forecast[1].text,
									code: result.item.forecast[1].code,
									date: result.item.forecast[1].date,
									day: result.item.forecast[1].day,
									image: "http://l.yimg.com/a/i/us/nws/weather/gr/"+result.item.forecast[1].code+"d.png"
								},
								link: result.item.link,
								updated: result.item.pubDate,
			*/

								code: we_code,
								city: we_city,
								image: "http://l.yimg.com/a/i/us/nws/weather/gr/"+we_code+we_daytime+".png",
								country: we_country,
								region: we_region,
								temp: we_temp,
								sumid: we_text.toLowerCase().replace(/ /g,"_")
							};

							/* CALL VIDEO */
							var thsumid = we_text.toLowerCase().replace(/ /g,"_");
							var currentdir = '<?php echo dirname(dirname($_SERVER['REQUEST_URI'])); ?>';
							$.ajax({url:currentdir+'/assets/weather_v/ogv/'+thsumid+'_libtheora.ogv',type:'HEAD',error: function() {console.log('OGV format is missing');}});
							$.ajax({url:currentdir+'/assets/weather_v/mp4/'+thsumid+'_x264.mp4',type:'HEAD',error: function() {console.log('MP4 format is missing');}});
							$.ajax({url:currentdir+'/assets/weather_v/'+thsumid+'.webm',type:'HEAD',error: function() {console.log('WEBM format is missing');}});
							$('h2').bind('click', function(event) {
								event.preventDefault();
								$(this).next('dl').slideToggle(500, function() {
									$('.video-background').videobackground('resize');
								});
							});
							$('body').prepend('<div class=\"video-background\"></div>');
							<?php if(!$options['fsbwNoVideo'] && !$c_disable) { ?>
								$('.video-background').videobackground({
									videoSource: [	
													[	currentdir+'/assets/weather_v/mp4/'+thsumid+'_x264.mp4', 		'video/mp4'		],
													[	currentdir+'/assets/weather_v/'+thsumid+'.webm', 				'video/webm'	], 
													[	currentdir+'/assets/weather_v/ogv/'+thsumid+'_libtheora.ogv', 	'video/ogg'		]	
												], 
									controlPosition: '#ffwb-controls',
									loop: true,
									resizeTo: 'window',
									poster: currentdir+'/assets/weather_i/'+thsumid+'.jpg',
									loadedCallback: function() {$(this).videobackground('mute');}
								});
							<?php } ?>
							options.success(weather);

			/*
						}); // function(i, result)

					} else {
						if (data.query.results === null) {
							options.error("An invalid WOEID or location was provided.");
						} else {
							options.error("There was an error retrieving the latest weather information. Please try again.");
						}
					} // THE CHECK FOR WOEID
				});
			*/
				
			return this;
		}
	});
})(jQuery);