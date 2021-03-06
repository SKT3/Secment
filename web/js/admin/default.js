var Sweboo = {};

jQuery.extend(jQuery.easing, {
	easeOutExpo: function (x,t,b,c,d){return (t==d) ? b+c : c * (-Math.pow(2, -10 * t/d) + 1) + b;},
	easeInOutExpo:function(e,f,a,h,g){if(f==0){return a}if(f==g){return a+h}if((f/=g/2)<1){return h/2*Math.pow(2,10*(f-1))+a}return h/2*(-Math.pow(2,-10*--f)+2)+a},
	easeInOutQuint:function(x,t,b,c,d){if((t/=d/2)<1)return c/2*t*t*t*t*t + b;return c/2*((t-=2)*t*t*t*t + 2)+ b;},
	easeOutQuint:function(x,t,b,c,d){return c*((t=t/d-1)*t*t*t*t+1)+b;},
	easeInCubic: function(x, t, b, c, d){return c*(t/=d)*t*t + b;},
	easeOutCubic: function(x, t, b, c, d) {return c*((t=t/d-1)*t*t + 1) + b;},
	easeInOutCubic: function(x, t, b, c, d) {if ((t/=d/2) < 1) return c/2*t*t*t + b; return c/2*((t-=2)*t*t + 2) + b;}
});

$.extend($.gritter.options, {
	position: 'bottom-right',
	time: 2500,
	fade_out_speed: 500
});

function add_flash_message(data) {
	gparams = [];
	gparams['title'] = data;
	gparams['text'] = ' ';
	$.gritter.add(gparams);
}

function add_flash_message_new(field, data) {
	gparams = [];
	gparams['title'] = field;
	gparams['text'] = data;
	$.gritter.add(gparams);
}


function save_form_callback(data) {
	$(document).trigger('ajaxComplete');
	$('form .error span.err').remove();
	$('form .error').removeClass('error');
	first_error = true;

	if(data.ok==1) {
		window.location.href = data.redirect_to;
	} else {
		$('.button.saving').removeClass('saving');
		for(i in data.errors) {
			if($('#id_'+i).length > 0) {
				$('#id_'+i).parents('li').addClass('error');
				$('#id_'+i).parents('li').append('<span class="err">'+data.errors[i]+'</span>');
				if(first_error==true) {
					first_error = false;
					$('html, body').animate({scrollTop: $('li.error:first').offset().top-20}, 300, 'easeInOutExpo');
				}
			} else {
				add_flash_message(data.errors[i]);
			}
		}
	}
}

function save_widget_form_callback(data) {
	$(document).trigger('ajaxComplete');
	$('form .error span.err').remove();
	$('form .error').removeClass('error');

	if(data.ok==1) {
		add_flash_message('Успешно редактиране');
	} else {
		for(i in data.errors) {
			if($('#id_'+i).length > 0) {
				$('#id_'+i).parents('li').addClass('error');
				$('#id_'+i).parents('li').append('<span class="err">'+data.errors[i]+'</span>');

			} else {
				add_flash_message_new(i, data.errors[i]);
			}
		}
	}
}


// Post data into a new window for preview plugin
//(function($){
//	$.sendPostToNewWidnow = function(selector, settings){
//		// settings
//		var config = {};
//		if ( settings ){$.extend(config, settings);}
//
//		// variables
//		var obj = $(selector);
//
//		alert(obj.val());
//
//		return this;
//	};
//})(jQuery);

tmout = 0;
jQuery().ready(function() {
	$.ajaxSetup({
		cache: false
	});

	if($('#id_slug').length){
		$('#id_title, #id_name').on('blur, keyup', function() {
			clearTimeout(tmout);
			tmout = setTimeout(function() {
				$.ajax({
					url: xhr_url+'?method=generate_slug',
					data: { 'slug' : $('#id_title, #id_name').val()},
					type: 'POST',
					success: function(data) {
						$('#id_slug').val(data);
					}
				});
			},300);
		});
	}

	// Chosen select
	if($(".chosen").length) {
		$(".chosen").chosen({no_results_text: "Няма резултати", placeholder_text_multiple : 'Изберете', search_contains: true, 'width': '75%'});
	}

	$('.list img.no').parents('tr').addClass('not_active');

	// $('#main_actions strong').bind('click',function(){
	// 	$('#main_actions a').toggleClass('show');
	// });

	$(document).ajaxSend(function() {
		$("#ajax-load").css("visibility", 'visible');
		$("#ajax-load").css("z-index", 10000);
	}).ajaxComplete(function() {
		setTimeout(function() {
			$("#ajax-load").css("visibility", 'hidden');
			$("#ajax-load").css("z-index", 0);
		},400);
	});

	$('#navigation .parent a:not(.sub a)').each(function() {
		var id = $(this).attr('id');
		var number = id.replace('parent_', '');

		if ($('#navigation #sub_'+number).length) {
			var link = $(this);
			link.parent().on('hover', function() {
				// Button selection
				if (link.hasClass('current')) {
					link.removeClass('current');
				} else {
					$('#navigation .parent ul > li > a:first-child').removeClass('current');
					link.addClass('current');
				}

				// Open/Hide subnavigation
				if (!$('#navigation ul #sub_'+number).hasClass('active')) {
					if ($('#navigation ul ul').hasClass('active')) {
						$('#navigation ul ul[id!=sub_'+number+']').stop(true, true).removeClass('active').slideUp(200);
						$('#navigation ul #sub_'+number).addClass('active').stop(true, true).slideDown(200);
					} else {
						// $('#navigation .sub #sub_'+number).css('opacity', 0);
						$('#navigation ul #sub_'+number).css('width', 'auto');
						if($('#navigation ul #sub_'+number).width() > ($('a#parent_'+number).outerWidth() + 20)) {
							var w = $('#navigation ul #sub_'+number).width();
						} else {
							var w = ($('a#parent_'+number).outerWidth() + 20);
						}

						$('#navigation ul #sub_'+number)
							.css('left', $('a#parent_'+number).offset().left)
							.addClass('active')
							.css('width', w)
							.slideDown(400);
					}
				} else {
					$('#navigation ul ul').stop(true, true).slideUp(200).removeClass('active');
				}
			});
		}
	});

	$('#main_form[target="save_form"] .buttons .button.ok').on('click',function(){
		if($(this).hasClass('saving')) {
			return false;
		} else {
			$(this).addClass('saving');
			return true;
		}
	});

	$('.list tbody tr:odd').css('background', '#F8F8F8');

	$('td:not(:last)','.list tbody tr:odd').css('border-right', '1px solid white');

	var fixHelper = function(e, ui) {
		ui.children().each(function() {
			$(this).width($(this).width());
		});
		return ui;
	};

	$('#list tbody').sortable({
		handle: ".orderable",
		helper: fixHelper,
		stop: function(event, ui) {
			$('.list tbody tr:even').css('background', '#FFFFFF');
			$('.list tbody tr:odd').css('background', '#F8F8F8');
			$('td:not(:last)','.list tbody tr:odd').css('border-right', '1px solid white');
			$('td:not(:last)','.list tbody tr:even').css('border-right', '1px solid #F5F5F5');
		}
	});

	$('#restore_order').click(function() {
		window.location.reload();
	});

	$('#flash_messages, .errors').each(function() {
		$(this).prependTo('body');
		$(this).css({
			'display': 'none',
			'opacity': 1,
			'position': 'fixed',
			'top' : 0,
			'left': 0,
			'width': '100%',
			'height': $(this).height() + 40,
			'margin': 0,
			'padding': 20
		});

		$(this).fadeIn('slow');
		$('.close', $(this)).click(function(){ $($(this).parents('div')[0]).fadeOut(function(){	$(this).remove(); }); });
	});

	setTimeout(function() {
		$('#flash_messages, .errors').fadeOut(function(){$(this).remove();});
	}, 7000);

	$('#permission_list dt input[type=checkbox]').click(function() {
		checked = $(this).attr('checked');
		$('#permission_list dd#chk_actions_' + $(this).get(0).id.replace('chk_', '') + ' input[type=checkbox]:not(:disabled)').each(function(){
			$(this).attr('checked', checked);
		});
	});

	$('#permission_list dd input[type=checkbox]').click(function() {
		if ($('input[type=checkbox]:checked', $(this).parents('dd')).length) {
			$('input[type=checkbox]:not(:disabled)', $(this).parents('dd').prev('dt')).attr('checked', true);
		} else {
			$('input[type=checkbox]:not(:disabled)', $(this).parents('dd').prev('dt')).attr('checked', false);
		}
	});

	// Calendar setup
	$('#main_form fieldset input.datetime-input, #main_form fieldset input.date-input').each(function() {
		$(this).after('<button type="button" class="btn_date">...</button>');
	});

	$('input.date-input, input.datetime-input').each(function() {
		$(this).next().click(function() {
			$(this).prev().focus();
		});
	});

	$('input.date-input').datepicker({
		dateFormat: 'yy-mm-dd',
		yearRange: '-100:+5',
		monthNamesShort: ['01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12'],
		showOtherMonths: true,
		changeMonth: true,
		changeYear: true,
		constrainInput: true,
		firstDay: 1,
		showAnim: 'fadeIn'
	});

	$('input.datetime-input').datepicker({
		dateFormat: 'yy-mm-dd',
		yearRange: '-100:+5',
		monthNamesShort: ['01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12'],
		showOtherMonths: true,
		changeMonth: true,
		changeYear: true,
		constrainInput: true,
		firstDay: 1,
		duration: '',
		showTime: true,
		stepMinutes: 1,
		stepHours: 1,
		altTimeField: '',
		time24h: true,
		showAnim: 'fadeIn'
	});

	$('#ga_trigger').click(function() {
		checked = $(this).attr('checked');
		$('.ga_checkbox').attr('checked', checked);
		$('#ga_select').attr('disabled', false);
	});

	$('#ga_check_all').click(function() {
		$('#ga_trigger, .ga_checkbox').attr('checked', true);
		$('#ga_select').attr('disabled', false);
	});

	$('#ga_uncheck_all').click(function() {
		$('#ga_trigger, .ga_checkbox').attr('checked', false);
		$('#ga_select').attr('disabled', true);
	});

	$('#ga_select').change(function() {
		ids = $('.ga_checkbox[type=checkbox]:checked').pluck('value');
		if (!ids.length) {
			$(this).val(0);
			return false;
		}

		if (confirm("'" + $('option:selected', $(this)).text().toLowerCase() + "' ")) {
			//location.href = $(this).val().toString() + '?ids=' + ids;
		}

		$(this).val(0);
	});

	$('form .submit').bind('click',function() {
		submitobj = $(this);
		setTimeout(function() {
			$(document).trigger('ajaxSend');
			submitobj.parents('form').trigger('submit');
		}, 0);
	});

	var object_id = $('#id_id').val();
	$('.belongs_to_autocomplete').each(function(){
		column = $(this).attr('data-column');
		object = $(this).attr('data-object');
		conditions = $(this).attr('autocomplete_conditions');

		$(this).autocomplete(xhr_url+'?method=belongs_to_autocomplete&column='+column+'&object='+object+'&conditions='+conditions).setOptions({
			formatItem: function(row) {r = row[0].split("_::_");return '<span id="'+r[0]+'">'+r[1]+'</span>';},
			formatResult : function(item) {return item[0].replace(/[0-9]+_::_/g,'')},
			minChars : 2
		}).result(function(event, item) {
			$(this).next().val(item[0].replace(/_::_(.*)/,''));
		});
	});

	$('.habtm_autocomplete').each(function(){
		column = $(this).attr('data-column');
		association_foreign_key = $(this).attr('data-association_foreign_key');
		foreign_key = $(this).attr('data-foreign_key');
		class_name = $(this).attr('data-class_name');
		conditions = typeof($(this).attr('data-conditions')) != 'undefined' ? $(this).attr('data-conditions') : '' ;

		$(this).autocomplete(xhr_url+'?method=habtm_autocomplete&column='+column+'&association_foreign_key='+association_foreign_key+'&foreign_key='+foreign_key+'&class_name='+class_name+'&conditions='+conditions).setOptions({
			formatItem: function(row) {r = row[0].split("_::_");return '<span id="'+r[0]+'">'+r[1]+'</span>';},
			formatResult : function(item) {return item[0].replace(/[0-9]+_::_/g,'')},
			minChars : 2
		}).result(function(event, item) {
			item =item[0].split('_::_');
			if(object_id) {
				li = '<li id="'+$(this).attr('data-relation')+'__'+object_id+'_'+item[0]+'"><a href="javascript:;">Close</a>'+item[1]+'</li>';
				$.get(xhr_url+'?method=habtm_save',{'foreign_key': $(this).attr('data-foreign_key'), 'association_foreign_key': $(this).attr('data-association_foreign_key'),'foreign_value': object_id, 'association_foreign_value': item[0]},function(){});
			} else {
				li = '<li id="'+$(this).attr('data-relation')+'__0_'+item[0]+'"><a href="javascript:;">Close</a>'+item[1]+'<input type="hidden" name="'+$(this).attr('data-relation')+'[]" value="'+item[0]+'" /></li>';
			}

			$(this).val('');
			$(this).next().append(li);
		});
	});

	$(document).on('click','.habtm_result li a',function() {
		li = $(this).parent('li');
		if(object_id) {
			li_id = li.attr('id').split('__')[1].split('_');
			related_input = li.parent('ul').prev();
			$.get(xhr_url+'?method=habtm_delete', {'foreign_key': related_input.attr('data-foreign_key'),'association_foreign_key': related_input.attr('data-association_foreign_key'),'foreign_value': li_id[0], 'association_foreign_value': li_id[1]},function() {
				li.remove();
			});
		} else {
			li.remove();
		}
	});

	$('.error input , .error select').on('change blur',function() {
		$(this).parents('.error').removeClass('error');
	});
});

function saveOrder() {
	order = new Array();
	j = 0;
	if ($('#listing').length) {
		$('#listing tr').each(function(){
			matches = this.id.match(/\d+/);
			order[j++] = matches[0];
		});
	}

	url = window.location.toString();
	query_string = '';
	if (url.lastIndexOf('?') != -1) {
		query_string = url.substr(url.lastIndexOf('?') + 1, url.length);
		url = url.substr(0, url.lastIndexOf('?'));
	}

	matches = url.match(/\/index|\/index\//);
	if (matches == null) {
		save_url = (url.charAt(url.length - 1) == '/') ? url + 'xhr' : url + '/xhr';
	} else {
		save_url = url.replace(/\/index|\/index\//, '/xhr');
	}

	save_url = xhr_url;
 	save_order_method = 'save_order';
	if($('#save_order').attr('rel')!='index') {
		save_order_method+='_'+$('#save_order').attr('rel');
	}

	$.ajax({
		type: "POST",
		url: save_url+'?method='+save_order_method,
		data: '&ids=' + order.join(',') + '&' + query_string,
		success: function(data) {
			add_flash_message(data);
		}
	});
}

// fadeToggle function plugin
jQuery.fn.fadeToggle = function(speed, easing, callback) {
	return this.animate({opacity: 'toggle'}, speed, easing, callback);
};

// Pluck
jQuery.fn.pluck = function(key) {
	var plucked = [];
	this.each(function() {
		plucked.push(this[key])
	});
	return plucked;
}

// Min & Max
Array.prototype.max = function() {
	max = this[0];
	len = this.length;
	for (var i = 1; i < len; i++) if (this[i] > max) max = this[i];
	return max;
}

Array.prototype.min = function() {
	min = this[0];
	len = this.length;
	for (var i = 1; i < len; i++) if (this[i] < min) min = this[i];
	return min;
}

function sortNumber(a,b) {
	return a - b;
}

// Backgroun position plugin
(function($) {
	$.extend($.fx.step, {
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

			function toArray(strg) {
				strg = strg.replace(/left|top/g,'0px');
				strg = strg.replace(/right|bottom/g,'100%');
				strg = strg.replace(/([0-9\.]+)(\s|\)|$)/g,"$1px$2");
				var res = strg.match(/(-?[0-9\.]+)(px|\%|em|pt)\s(-?[0-9\.]+)(px|\%|em|pt)/);
				return [parseFloat(res[1],10),res[2],parseFloat(res[3],10),res[4]];
			}
		}
	});
})(jQuery);

Sweboo.Users = {

	Init: function(options) {
		this.options = {};
		$.extend(this.options, options || {});
		$('#admin_group_id').change(function() {
			Sweboo.Users.changeGroup($(this).val())
		});
	},

	changeGroup: function(value) {
		$.ajax({
			url: Sweboo.Users.options.url + 'xhr',
			data: 'method=get_permissions&id=' + (value * 1),
			success: function(permissions){
				$('#permission_list input[type=checkbox]:disabled').attr('disabled', false);
				$('#permission_list input[type=checkbox]').each(function() {
					if (permissions.lastIndexOf('|' + $(this).val() + '|') != -1) {
						$(this).attr('disabled', true).attr('checked', false);
					} else {
						$(this).attr('disabled', false);
					}
				});
			}
		});
	}
}