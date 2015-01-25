(function($) 
{	
	var $backdrop = $("<div />").addClass("slider-backdrop"),
	$background = $("<img />").addClass("slider-background"),
	$overlay = $("<div />").addClass("slider-overlay"),
	$container = $(),
	$list = $(),
	$current = $(),
	slides = [],
	step = -1,
	from = -1,
	timer = null,
	self = null,
	options = {},
	loading = false,
	resolution_str = "base",
	methods = 
	{
        init: function( settings )
		{
			// pass arguments
			self = this;
			loading = true;
			options = 
			{
				parent: 'body',
				gparent: 'html',
				first: 0,
				fade: 1000,
				delay: 5e3,
				preload: true,
				paused: false,
				backdrop: true,
				load: function() {},
				complete: function() {},
				walk: function() {},
			};
            $.extend(options, $.fn.wnitslider.defaults, settings);
			var padding_obj = methods.padding_adjustment($(options.parent));
			options.htmlcss = $.extend({},padding_obj);
			options.overlaycss = $.extend({},padding_obj);
			options.backdropcss = $.extend({},padding_obj);
			options.bgcss = $.extend({},padding_obj);
			delete options.htmlcss.width;
			
			// validate the container and slide elements
			methods.setup_tags.call(this);
			
			// determine resolution file suffix and preload images
			methods.reset_resolution_str.call(this);
			methods.update_img_resolutions.call(this);
			if(options.preload)
				methods.preload.call(this);
				
			// go to the first slide
			methods.jump.call(this,options.first);
            return $.fn.wnitslider;
        },
		show: function()
		{
			//alert('test'+step.toString());
			if ($current.length!=0)
			{
				if (from>=0)
					$(slides[from]).fadeOut(options.fade);
				$current.fadeOut(options.fade,function()
				{
					$current.remove();
					$current = $new;
				});
			}
			
			// create the background img
            var $new = $background.clone();
            $new.css(options.bgcss).attr("src", $(slides[step]).data('href')).bind("load", function() 
			{
				// add resize event                
				if (loading)
				{
					$(window).bind("load resize", function(e) 
					{
						methods.resize.call(this);
					});
					
					// add backdrop
					$(options.parent).css('background','none');
					$(options.gparent).css('background','none');
					if(options.backdrop)
						$backdrop.css(options.backdropcss).height($(options.parent).height()+parseInt(-$backdrop.css('margin-left').replace("px",""))).prependTo($(options.parent)).show();
				}
				
				// add background
				var fade = loading ? 300 : options.fade;
				$(slides[step]).fadeIn(fade);
				$new.hide().prependTo($(options.parent)).fadeIn(fade, function() 
				{
					$(options.parent).trigger("slidercomplete", [this, step]);
					options.complete.apply(this, [step]);
				
					// start slideshow
					if(!options.paused)
					{
						$(options.parent).trigger("sliderstart", [this, step]);
						clearTimeout(timer);		
						timer = setTimeout(function(){methods.next.call(self);}, options.delay);
					}
				});
				// call final callbacks
				if(loading)
				{					
					// add overlay
					if($overlay.css('background-image')!="")
						$overlay.hide().css(options.overlaycss).height($new.height()).prependTo($(options.parent)).show();
                
					$current = $new;
                	$(options.parent).trigger("sliderload", [$current.get(0), step]);
                	options.load.apply($current.get(0), [step]);
					loading = false;
				}
                if (step>=0) 
				{
                    $(options.parent).trigger("sliderwalk", [$current.get(0), step]);
                    options.walk.apply($current.get(0), [step]);
                }
            });
            return $.fn.wnitslider;
		},
		jump: function(s)
		{
			if ( s !== step )
			{
				from = step;
				step = s;
				methods.check_steps.call(this);
				if(from!=step)
					methods.show.call(this);
			}
			return $.fn.wnitslider;
		},
		next: function()
		{
			return methods.jump.call(this,step+1);
		},
		previous: function()
		{
			return methods.jump.call(this,step-1);
		},
		check_steps: function(a)
		{
			var max_step = slides.length - 1;
			if ( step > max_step )
				step = 0;
			else if ( step < 0 )
				step = max_step;
			return $.fn.wnitslider;
		},
		setup_tags: function()
		{
			// use current self as container; check for list tag
			$container = $(self);
			if(Array('UL','OL').indexOf($container.prop('tagName'))!=-1)
				$list = $container;
			else
			{
				$list = $container.children('ul');
				if ($list.length!=0)
					$list = $($list[0]);
				else
				{
					$list = $container.children('ol');
					if ($list.length==0)	
						$.error('slides container must container either an OL or a UL.');
					else
						$list = $($list[0]);
				}
			}
			
			// is there an overlay?
			var href = $container.data('overlay');
			if (typeof href != "undefined")
				$overlay.css('background-image',"url("+href+")");
			
			// look for tags
			slides = $list.children('li');
			slides.hide().css(options.htmlcss);
			if (slides.length==0)
				$.error('slides container must contain at least one slide.');
            return $.fn.wnitslider;
		},
		reset_resolution_str: function()
		{
			if ( options.resolutions )
			{
				var win_w = $(window).width();   
				for ( var str in options.resolutions )
				{
					if ( win_w >= options.resolutions[str] )
					{
						resolution_str = str;
						break;
					}
				}
			}
            return $.fn.wnitslider;
		},
		update_img_resolutions: function()
		{
			var href = null;
			for( var i = 0; i < slides.length; i++ )
			{
				href = $(slides[i]).data('href');
				if(typeof href == "undefined")
					$(slides[i]).data('href','#');
				else if(resolution_str!="base")
					$(slides[i]).data('href',href.replace('base.',resolution_str+'.'));
			}
            return $.fn.wnitslider;
		},
        preload: function() 
		{
            var cache = [];
            for ( var i = 0; i < slides.length; i++ ) 
			{
				var href = $(slides[i]).data('href');
                if ( href && href.toLowerCase() !='none' ) 
				{
                    var cache_image = document.createElement( "img" );
                    cache_image.src = href;
                    cache.push( cache_image );
                }
            }
            return $.fn.wnitslider;
        },		
		padding_adjustment: function( e )
		{
			var settings = {};
			var left = $(e).css('padding-left');
			var top = $(e).css('padding-top');
			settings.width = $(e).outerWidth();
			if(left!="0px")
				settings.marginLeft = '-' + left;
			if(top!="0px")
				settings.marginTop = '-' + top;
			return settings;
		},
		resize: function() 
		{
			var padding = methods.padding_adjustment(options.parent);
			$.extend(options.backdropcss,padding);
			$.extend(options.bgcss,padding);
			$.extend(options.htmlcss,padding);
			$.extend(options.overlaycss,padding);
			delete options.htmlcss.width;
			$current.css(options.bgcss);
			$overlay.css(options.overlaycss).height($current.height());
			$backdrop.css(options.backdropcss).height($(options.parent).height()+parseInt(-$backdrop.css('margin-top').replace("px","")));
			return $.fn.wnitslider;
		}
    };
	
    $.fn.wnitslider = function( method ) 
	{
		
        if ( methods[ method ] )
            return methods[ method ].apply( this, Array.prototype.slice.call( arguments, 1 ) );
        else if ( typeof method === "object" || !method )
            return methods.init.apply( this, arguments );
        else
            $.error( "Method " + method + " does not exist" );
    };
	
	$.fn.wnitslider.defaults = 
	{
		resolutions: 
		{
			'large':1800,
			'base': 760,
			'small': 480,
			'vsmall': 0
		}
	};
})(jQuery);