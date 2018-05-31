$(function(){

});

var styles = [
	{title : 'Заглавие на страница(h1)', block : 'h1', classes :'heading'},
	{title : 'Заглавие/под-заглавие h3', block : 'h3'},
	{title : 'Заглавие на секция за начална страница(h3)', block: 'h3', classes : 'heading-normal'},
	{title : 'Заглавие на секция с подчертавка(h4)', block : 'h4', classes : 'heading-normal'},
	{title : 'Заглавие дясна колона с подчертавка(h5)', block : 'h5', classes : 'heading-normal'},
	{title : 'Съдържание', block : 'div', classes : 'text'},
	{title : 'Иконка за файл', selector: 'a', classes : 'download'},
	{title : 'Довършителни работи - Услуги - Черен към бял текст', selector: 'span', classes : 'hoverable'}
];

$(window).load(function(){
	$(document).on('click', '.addBlock', function(e){
		e.preventDefault();
		add_module($(this).attr('rel'));
	});

	bind_sortable();

	$('.widget').addClass('ui-draggable')
		.find('.widget-header')
			.addClass('ui-dialog-titlebar ui-widget-header');

	$(document.body).on('click', '.widget-header .minimize',  function() {
		$(this).toggleClass('ui-icon-minusthick').toggleClass('ui-icon-plusthick');
		$(this).parents('.widget:first').find('.widget-content').toggle();
		if($(this).hasClass('ui-icon-minusthick')) {
			var shown = 1;
		} else {
			var shown = 0;
		}

		$.ajax({
			url: xhr_url+'?method=saveBlockState',
			data: {'block': $(this).parent().parent().attr('id').replace('widget_', ''), 'state': shown },
			type: 'POST',
			dataType: 'JSON',
			success: function(data){

			}
		});
	});

	$(document.body).on('click', '.widget-header .edit-widget', function(e){
		e.preventDefault();
		var src = $(this).parent().parent().attr('data-url');
		var block_id = $(this).parent().parent().attr('id');
		var title = $(this).text();
		iframe.find('#cnt').html('');
		$.ajax({
		  type: 'GET',
		  url: src,
		  success: function(data) {
		  	iframe.find('#cnt').attr('rel', src.replace('edit_','save_')).attr('data-block', block_id).html(data);
		  	dialog.dialog("option", "position", ['center', 'center']);
		  	dialog.find('button.cancel').off('click').click(function() { dialog.dialog('close'); });
		  	window.parent.tinymce.init({
				selector: '.rich-text-full',
				convert_urls: false,
				plugins: [
					 'advlist autolink link image lists charmap anchor',
					 'searchreplace wordcount visualblocks visualchars code nonbreaking',
					 'save table contextmenu directionality template paste media textcolor'
				],
				statusbar: false,
			   image_advtab: true,
			   toolbar: 'styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist | link image media | forecolor backcolor | code',
			   templates: window.top.tinyMceTemplates,
			   style_formats: styles,
			});
		  },
		  dataType: 'html'
		});
		dialog.dialog('option', 'title', title).dialog('open');
	});

	$(document.body).on('click', '.widget-header .delete-widget', function(e){
		e.preventDefault();
		var widget = $(this).parent().parent();
		if(confirm('Сигурни ли сте, че искате да изтриете този блок?')){
			$.ajax({
				url: xhr_url+'?method=delete_block',
				data: {'id': widget.attr('id').replace('widget_', '') },
				type: 'POST',
				success: function(data){
					widget.remove();
					$('.placeholder').sortable('refresh');
				}
			});
		}
	});




	var iframe = $('<div class="widgetEdit"><div id="cnt"></div></div>');
	var dialog = window.parent.jQuery('<div></div>').append(iframe).appendTo('body').dialog({
		autoOpen: false,
		modal: true,
		resizable: false,
		width: '940',
		height: 'auto',
		close: function () {
			$('.ui-widget-overlay').css('position', 'absolute');
		},
		open: function() {
			$('.ui-widget-overlay').css('position', 'fixed');
		}
	});

	var widgets = new Array();
	$('.widget').each(function(index, value){
		widgets.push({
			'id'  : $(this).attr('id'),
			'url' : $(this).attr('data-url')
		});
		if($(this).attr('data-state') == 0){
			$(this).find('.ui-icon-minusthick').trigger('click');
		}
	});
	var i = 0;
	$.ajax({
		url: xhr_url+'?method=getContent',
		data: { widgets : widgets},
		type: 'POST',
		success: function(data){
			if(data){
				$.each(data, function(key, content){
					$('#'+key+' .widget-content').html(content);
					i++;
				});
				init_editor(i);
			}
		},
		dataType: 'JSON'
	});

})
function init_editor(i) {
	if(i == $('.widget').length) {
		tinymce.init({
			selector: 'div.editable',
			inline: true,
			convert_urls: false,
			plugins: [
				 'advlist autolink link image lists charmap anchor',
				 'searchreplace wordcount visualblocks visualchars code nonbreaking',
				 'save table contextmenu directionality template paste media textcolor'
			],
			statusbar: false,
			image_advtab: true,
			toolbar: 'styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist | link image media | forecolor backcolor | code',
			setup: function(editor) {
				editor.on('focus', function(e) {
			  		editorButtons(editor.id);
				});
				editor.on('blur', function(e) {
			  		editorButtons(editor.id, true);
			  		editor.hide();
				});
				 editor.on('hide', function(e) {

				});
			},
		   templates: window.top.tinyMceTemplates,
		   fixed_toolbar_container: '#tinymce_toolbar',
		   theme: 'modern',
		   style_formats: styles,
		});

		tinymce.init({
			selector: "span.editable",
			inline: true,
			toolbar: "undo redo",
			menubar: false,
			setup: function(editor) {
					editor.on('focus', function(e) {
				  		editorButtonsTab(editor.id);
					});
					editor.on('blur', function(e) {
				  		editorButtonsTab(editor.id, true);
				  		editor.hide();
					});
					 editor.on('hide', function(e) {

					});
				},
			fixed_toolbar_container: '#tinymce_toolbar',
		   	theme: 'modern',
		});

	}
}

function refresh_editor(widget) {
	if($('#'+widget).find('.editable').length) {
		var id = $('#'+widget).find('.editable').attr('id');
		if($('#'+widget).find('.tabs').length) {
			$('#'+widget).find('.tabs .tab-content .editable').each(function(i, e) {
				var sel = '#' + $(this).attr('id');
				tinymce.init({
					selector: sel,
					inline: true,
					convert_urls: false,
					plugins: [
						 'advlist autolink link image lists charmap anchor',
						 'searchreplace wordcount visualblocks visualchars code nonbreaking',
						 'save table contextmenu directionality template paste media textcolor'
					],
					statusbar: false,
					image_advtab: true,
					toolbar: 'styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist | link image media | forecolor backcolor | code',
					setup: function(editor) {
						editor.on('focus', function(e) {
					  		editorButtons(editor.id);
						});
						editor.on('blur', function(e) {
					  		editorButtons(editor.id, true);
					  		editor.hide();
						});
						 editor.on('hide', function(e) {

						});
					},
				   templates: window.top.tinyMceTemplates,
				   fixed_toolbar_container: '#tinymce_toolbar',
				   theme: 'modern',
				   style_formats: styles,
				});
			});

			$('#'+widget).find('.tabs .nav span.editable').each(function(i, e) {
				var sel = '#' + $(this).attr('id');
				 tinymce.init({
					selector: sel,
					inline: true,
					toolbar: "undo redo",
					menubar: false,
					setup: function(editor) {
						editor.on('focus', function(e) {
					  		editorButtonsTab(editor.id);
						});
						editor.on('blur', function(e) {
					  		editorButtonsTab(editor.id, true);
					  		editor.hide();
						});
						 editor.on('hide', function(e) {

						});
					},
					fixed_toolbar_container: '#tinymce_toolbar',
		   			theme: 'modern'
				});
			});

			// Content tabs
			$('#cnt .tabs').each(function() {
				var master = $(this);

				$('.tab-links a', master).on('click', function() {
					var el = $(this);
					var index = $('.tab-links a', master).index(el);

					var tab = $('.tab-content:eq(' + index + ')', master);
					if (tab.length && !tab.hasClass('active')) {
						$('.tab-content', master).removeClass('active');
						$('.tab-links a', master).removeClass('active');

						el.addClass('active');
						tab.addClass('active');
					}
				});
			});
		} else {
			var sel = '#'+id;
			tinymce.init({
				selector: sel,
				inline: true,
				convert_urls: false,
				plugins: [
					 'advlist autolink link image lists charmap anchor',
					 'searchreplace wordcount visualblocks visualchars code nonbreaking',
					 'save table contextmenu directionality template paste media textcolor'
				],
				statusbar: false,
				image_advtab: true,
				toolbar: 'styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist | link image media | forecolor backcolor | code',
				setup: function(editor) {
					editor.on('focus', function(e) {
				  		editorButtons(editor.id);
					});
					editor.on('blur', function(e) {
				  		editorButtons(editor.id, true);
				  		editor.hide();
					});
					 editor.on('hide', function(e) {

					});
				},
				templates: window.top.tinyMceTemplates,
				fixed_toolbar_container: '#tinymce_toolbar',
				theme: 'modern',
				style_formats: styles,
			});
		}
	}
}


function editorButtons(id, hide) {
	if(!hide) {
		$('#'+id).parent().find('.buttons').show();
	} else {
		setTimeout(function(){ $('#'+id).parent().find('.buttons').hide(); }, 300);
	}
}

function editorButtonsTab(id, hide){
	if(!hide){
		$('#'+id).parent().parent().find('.buttons').toggle();
	}else{
		setTimeout(function(){ $('#'+id).parent().parent().find('.buttons').toggle(); }, 300);
	}
}

function bind_sortable(){
	$('.placeholder ').sortable({
		connectWith: '.placeholder',
		items: '.widget',
		handle: '.ui-widget-header',
		tolerance: 'intersect',
		update: function(event, ui) {
			if (this === ui.item.parent()[0]) {
				var placeholder = ui.item.parents('.placeholder').find('a.addBlock').attr('rel');
				var items = ui.item.parents('.placeholder').sortable('toArray');
				$.ajax({
					url: xhr_url+'?method=sortPlaceholders',
					data: {'placeholder': placeholder, 'items':items },
					type: 'POST',
					dataType: 'JSON',
					success: function(data){
						add_flash_message('Успешно запазихте подредбата');
					}
				});
			}

		}
	});
}