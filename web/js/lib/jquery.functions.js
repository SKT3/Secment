// Backgroun position plugin
(function($) {
	$.extend($.fx.step,{
	    backgroundPosition: function(fx) {
            if (fx.state === 0 && typeof fx.end == 'string') {
                var start = $.curCSS(fx.elem,'backgroundPosition');
                start = toArray(start);
                fx.start = [start[0],start[2]];
                var end = toArray(fx.end);
                fx.end = [end[0],end[2]];
                fx.unit = [end[1],end[3]];
			}
            var nowPosX = [];
            nowPosX[0] = ((fx.end[0] - fx.start[0]) * fx.pos) + fx.start[0] + fx.unit[0];
            nowPosX[1] = ((fx.end[1] - fx.start[1]) * fx.pos) + fx.start[1] + fx.unit[1];
            fx.elem.style.backgroundPosition = nowPosX[0]+' '+nowPosX[1];

           function toArray(strg){
               strg = strg.replace(/left|top/g,'0px');
               strg = strg.replace(/right|bottom/g,'100%');
               strg = strg.replace(/([0-9\.]+)(\s|\)|$)/g,"$1px$2");
               var res = strg.match(/(-?[0-9\.]+)(px|\%|em|pt)\s(-?[0-9\.]+)(px|\%|em|pt)/);
               return [parseFloat(res[1],10),res[2],parseFloat(res[3],10),res[4]];
           }
        }
	});
})(jQuery);

function isInteger(s){
    return (s%(parseInt(s)/Number(s)))===0;
}


function addCommas(nStr)
{
    nStr += '';
    
    x = nStr.split('.');
    x1 = x[0];
    x2 = x.length > 1 ? '.' + x[1] : '';

    var rgx = /(\d+)(\d{3})/;
    while (rgx.test(x1)) {
        x1 = x1.replace(rgx, '$1' + ' ' + '$2');
    }
    return x1 + x2;
}

Array.prototype.in_array = function(a) {
    for(c in this) {
        if(a==this[c]) {return true;}
    }
    return false;
}

function randomize()
{
	setTimeout(function(){randomize();},501);
		$('a').css('position','relative').each(function(){
		$(this).animate({left: Math.random()*100-Math.random()*200, top: -Math.random()*100/6 },500);
	});
}

(function($) {
    $.fn.placeHolder = function(){
        // Init
        $(this).each(function(){
            $(this).bind('focus', function() {
                var title = $(this).attr('title');

                if ($(this).val() == title)
                {
                    $(this).val('');
                }
            }).bind('blur', function() {
                if ($(this).val() == '')
                {
                    var title = $(this).attr('title');
                    $(this).val(title);
                }
            });
        });
    };
})(jQuery);