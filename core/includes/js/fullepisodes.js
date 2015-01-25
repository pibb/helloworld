var EpisodePage = function() {
	var ep = this;
	
	this.segment_count = $("#segments .segment").length;
	$("#segments .segment").click( this.$toggle_info );
	 
	 

	if ( wnit.page.hash && wnit.page.hash.indexOf("=") > -1 ) {
		var d = wnit.page.hash.split("=");
		if ( d[0] == "segment" ) {
			$("#segments .segment").each(function(){
				if ( $(this).attr("data-segment-num") == d[1] ) {
					ep.$toggle_info.call( this );
				}	
			});
		}
	}	
	 
	$('.play, .embed').click(function(){
		var segment_id = $(this).parent().parent().parent().parent().attr("data-segment-id");
		
		if ( $(this).hasClass("embed") )
			var url = wnit.anchor( wnit.page.url, {"segment": segment_id, "naked": 1, "embed": 1, "code": 1} );
		else
			var url = wnit.anchor( wnit.page.url, {"segment": segment_id, "naked": 1, "embed": 1} );
		
			
		$.get( url, function(html){
			$.fancybox( html );
		})
		return false;
	});
	
}

EpisodePage.prototype = {
	segment_count: 0,
	$toggle_info: function() {
		if ( $(this).find(".info" ).hasClass("summary") ) {
			$("#segments .segment .info").each( function(){
				$(this).removeClass("full");
				$(this).removeClass("summary");
				$(this).addClass("summary");
				
				$(this).find(".info" ).show();
				$(this).find(".info" ).fadeOut( 500 );
			});	
			
			$(this).find(".info" ).removeClass("summary");
			$(this).find(".info" ).hide();
			
			$(this).find(".info" ).addClass("full");
			$(this).find(".info" ).fadeIn( 200 );
			
			var segnum = 0;
			var seg = this;
			$(this).parent().children(".segment").each( function() {
				if ( seg == this ) segnum = $(this).attr("data-segment-num");
			});
			window.location.hash = "segment=" + segnum;
			
			return false;
		} else {
		
		}
	}
};

$(document).ready(function(){
	$(".noscript").removeClass("noscript");
	$("#season-links").hide();
	$("#season").change(function(){
		
		var url = $(this).attr("data-href") + "&season=" +  $(this).val();
		
		$.get(url,function(html){
			$("#fullepisodes").html( html );
		});
		
		
	});
	
	page = new EpisodePage();
});


