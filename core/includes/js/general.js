"use strict";

//wnit Object
var wnit = {
	hash: {},
	_timer: 0,
	steps: new Array(),
	fields: {},
	validate: false,
	//----------------------------------------------------------------------------------------------------
	// * initHash
	//----------------------------------------------------------------------------------------------------
	initHash: function()
	{
		var b, a = location.hash.substr( 1, ( location.hash.length - 1 ) ).split( "&" );
		
		for( var i = 0; i < a.length; i++ )
		{
			b = a[ i ].split( "=" );
			this.hash[ b[ 0 ] ] = b[ 1 ];
		}
	},
	//----------------------------------------------------------------------------------------------------
	// * get_hash
	//----------------------------------------------------------------------------------------------------
	get_hash: function()
	{
		return location.hash.substr( 1 );
	},
	//----------------------------------------------------------------------------------------------------
	// * delay
	//----------------------------------------------------------------------------------------------------
	delay: function(cb, num, param)
	{
		if (this._timer)
			window.clearTimeout(this._timer);
		this._timer = window.setTimeout(function() 
		{
		   cb(param);
		}, num);
	},
	//----------------------------------------------------------------------------------------------------
	// * quick_search
	//----------------------------------------------------------------------------------------------------
	quick_search: function( param )
	{
		$( '#searchlist' ).html('<span class="loading">Searching...</span>');
		$( '#searchlist' ).load( param.url + "?naked=1&query=" + escape( param.query ) );
		$( '#searchlist' ).show();
	},
	//----------------------------------------------------------------------------------------------------
	// * subform_fix
	//----------------------------------------------------------------------------------------------------
	subform_fix: function( action )
	{
		var flag = "box=1";
		flag = ( action.indexOf( "?" ) == -1 ) ? ( "?" + flag ) : ( "&" + flag );
		
		return action + flag;
	},
	//----------------------------------------------------------------------------------------------------
	// * step
	//----------------------------------------------------------------------------------------------------
	step: function(cur,next)
	{
		var success = false;
		if( this.fields[cur] )
		{
			success = true;
			var set_focus = false;
			if( this.validate && next )
			{
				for (var index = 0; index < this.fields[cur].length; ++index)
				{
					var fname = this.fields[cur][index].name;
					var ftype = this.fields[cur][index].type != 'undefined' ? this.fields[cur][index].type : 's';
					var ferr = this.fields[cur][index].err != 'undefined' ? this.fields[cur][index].err : false;
					var req = this.fields[cur][index].required;
					var parent = $(fname).parent();
					parent.removeClass('err');
					if (req&&(ferr||!$(fname).val()))
					{
						parent.addClass('err');
						if(!set_focus)
						{
							$(fname).focus();
							set_focus = true;
						}
						success = false;
					}
				}
			}
			
			if (success&&next!='submit')
			{
				if(next)cur = next;
				for (var index = 0; index < this.steps.length; ++index)
				{
					$('#set_'+ this.steps[index]).hide();
					$('#step_'+ this.steps[index]).removeClass('sel');
				}
				$('#set_'+cur).show();
				$('#step_'+cur).addClass('sel');
				success = true;
			}
		}
		return success;
	},
	//----------------------------------------------------------------------------------------------------
	// * fancy_subform
	//----------------------------------------------------------------------------------------------------
	fancy_subform: function(index)
	{
		var href = $( this ).attr( 'href' );
		$( this ).attr( 'href', wnit.subform_fix( href ) );
		$( this ).fancybox(
		{
			'overlayColor':'#333',
			'height':'auto',
			'autoDimensions':true,
			modal:true,
			onComplete:function()
			{
				$( '#fancybox-content form' ).each(function(index)
				{
					var form_id = $( this ).attr( 'id' );
					var action = wnit.subform_fix( $( this ).attr( 'action' ) );
					$( this ).addClass( "module" );
					$( '#' + form_id + ' .cancel' ).attr( 'onclick', "" );
					$( '#' + form_id + ' .cancel' ).unbind( 'click' );
					$( '#' + form_id + ' .cancel' ).bind( 'click', function(){ $.fancybox.close(); } );
					$( '#' + form_id + ' select' ).selectbox();
					$( '#' + form_id + ' .file input' ).filestyle();
					$( '#' + form_id + ' .file input' ).css({ opacity: 0.0 });
					
					wnit.cb_bind_submit( this.id, action );
				});
			}
		});
	},
	//----------------------------------------------------------------------------------------------------
	// * cb_bind_submit
	//----------------------------------------------------------------------------------------------------
	cb_bind_submit: function( obj, action )
	{
		obj = $( '#' + obj )[ 0 ];
		$( obj ).bind( "submit", function() 
		{  
			$.fancybox.showActivity();
			$.ajax(
			{
				type	: "POST",
				cache	: false,
				url		: action,
				enctype	: 'multipart/form-data',
				data	: $( obj ).serializeArray(),
				success	: function( data ) 
				{
					$.fancybox( 
					{
						'overlayColor':'#333','height':'auto','autoDimensions':false,'width':'auto','content':data,
						'onComplete':function(){if( typeof sub_form_cb == 'function' ){sub_form_cb( data, obj.id, action );}}
					});
				}
			});
			return false; 
		});
	},
	//----------------------------------------------------------------------------------------------------
	// * button
	//----------------------------------------------------------------------------------------------------
	button: function( cls, click )
	{
		var html = "";
		
		html += '<a class="' + cls + '" href="#" onclick="' + click +'"><img src="{U_URL}themes/{V_THEME}/images/icons/' + cls + '.png" alt="Delete" /></a>';
		
		return html;
	},
	//----------------------------------------------------------------------------------------------------
	// * break_url
	//----------------------------------------------------------------------------------------------------
	break_url: function ( url ) {
		var r = {
			url: "",
			hash: false,
			getvars: {}
		};
		if ( url.indexOf("#") > -1 ) {
			var d = url.split("#");
			url=d[0];
			r.hash=d[1];
		} 
		if ( url.indexOf("?") > -1 ) {
			var d = url.split("?");
			url=d[0];
			var getvars = d[1].split("&");
			
			var n = getvars.length;
			for ( var i = 0; i < n; i++ ) {
				if ( getvars[i].indexOf("=") > 0 ) {
					var d = getvars[i].split("=");
					
					r.getvars[d[0]] = d[1];
				}
			}
		} 
		r.url = url;
		return r;
	},
	//----------------------------------------------------------------------------------------------------
	// * anchor
	//----------------------------------------------------------------------------------------------------
	anchor: function ( page, getvars ) {
		var vars = [];
		
		for ( var i in getvars ) {
			vars.push( i + "=" + getvars[i] );
		}
		
		return page + (vars ? ("?" + vars.join("&") ) : "");
	},
	//----------------------------------------------------------------------------------------------------
	// * url_append
	//----------------------------------------------------------------------------------------------------
	url_append: function ( page, v ) {
		var v = v.split("=");
		var a = this.break_url(page);
		a.getvars[v[0]] = v[1];
		return this.anchor( a.url, a.getvars );
	}
}

//Init
$(document).ready(function()
{
	wnit.initHash();
	wnit.page = wnit.break_url( window.location.href.toString() );
	$(".fancybox").fancybox({autoSize:false,autoHeight:true,width:320});
	
	// fix header bar

	$(window).scroll(function (event) 
	{
		var $obj = $('header');
		var top = $obj.outerHeight();// - parseFloat($obj.css('marginTop').replace(/auto/, 0));
		
		if($(window).width()>760)
		{
			var y = $(this).scrollTop() + $(window).innerHeight();
			if (y >= top)
			  $obj.addClass('fixed');
			else
			  $obj.removeClass('fixed');
		}
	});
});
$(document).ready(function()
{	
	wnit.initHash();
	$('.box').fancybox({'overlayColor':'#333','height':'auto','autoDimensions':false});
	$('.subform').each();
});
$(window).resize(function() 
{
	$('#menu .compact').slideUp();
});
$(document).mouseup(function (e)
{
	var container = $('#menu');
	var menu = $('#menu .compact');
	if (!container.is(e.target) && container.has(e.target).length === 0) // if the target of the click isn't the container nor a descendant of the container
	{
		menu.slideUp(300);
	}
	
	var container = $('#connect-mobile');
	var mform = $('#connect-mobile-form');
	if (!container.is(e.target) && container.has(e.target).length === 0) { mform.slideUp(300); }
	
	var container = $('#connect-newsletter');
	var mform = $('#connect-newsletter-form');
	if (!container.is(e.target) && container.has(e.target).length === 0) { mform.slideUp(300); }
	
	var container = $('#menu-f-watch');
	var mform = $('#menu-f-watch .submenu');
	if (!container.is(e.target) && container.has(e.target).length === 0) { mform.hide(0); }
	
	var container = $('#menu-f-support');
	var mform = $('#menu-f-support .submenu');
	if (!container.is(e.target) && container.has(e.target).length === 0) { mform.hide(0); }
	
	var container = $('#menu-f-engage');
	var mform = $('#menu-f-engage .submenu');
	if (!container.is(e.target) && container.has(e.target).length === 0) { mform.hide(0); }
	
	var container = $('#menu-f-about');
	var mform = $('#menu-f-about .submenu');
	if (!container.is(e.target) && container.has(e.target).length === 0) { mform.hide(0); }
	
	var container = $('#search');
	var mform = $('#searchlist');
	if (!container.is(e.target) && container.has(e.target).length === 0) { mform.hide(0); }
});
