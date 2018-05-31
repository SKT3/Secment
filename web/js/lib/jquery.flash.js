/*
	@Author  : 
	Cvetozar Ninov
*/
var flash_obj = {};
(function($) {
	$.fn.loadFlash = function(settings) {
		
		 settings = jQuery.extend({
			filename:   '',
			width:   100,
			height:	 100,
			params: {}
		},settings);
		 
		 flash_obj = $(this);
		
		
		_loadFlash(settings);
	}
	
		
		function _loadFlash(settings)
		{
			
			var add_params = '';
			if ( settings.params )
			{
				for( param in settings.params ) {
					add_params += '<param name="'+param+'" value="'+settings.params[param]+'" />';
				}
			}
		
			var Flash = ' \
				<object type="application/x-shockwave-flash" data="' + settings.filename + '" width="' + settings.width + '" height="' + settings.height + '"> \
				<param name="quality" value="high" /> \
				<param name="scale" value="noscale" /> \
				<param name="wmode" value="transparent" /> \
				' + add_params + ' \
				<param name="movie" value="' + settings.filename + '" /> \
				</object> \
			';
		  flash_obj.html(Flash);
		}
})(jQuery);