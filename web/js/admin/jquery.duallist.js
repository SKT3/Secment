$.fn.SwebooDualList = function(settings) 
{
	settings = $.extend({
		buttons: true,
		removeText: 'remove',
		removeImage: '',
		addText: 'add',
		addImage: '',
		beforeFrom: '',
		beforeTo: '',
    ieBg: '',
    ieColor: 'graytext',
    testMode: false
	}, settings);
	return this.each(function() {
		if (this.multiple == false) { return; }
		var name = this.name;
		if (!this.id) {
			this.id = this.name.match(/[a-zA-Z0-9]+/);
		}
		var id = this.id;
		var select = $('#' + id);

		$(this.form)
			.submit(function(e) {
				if (settings.testMode) e.preventDefault();
				for(var item = 0; item < this.SwebooDualLists.length; item++)
				{
					selectAll(this.SwebooDualLists[item]);
				}
			})
			.each(function() {
				if (this.SwebooDualLists == undefined) this.SwebooDualLists = new Array();
				this.SwebooDualLists.push(id);
			});

		var container = $(this).parent().addClass('SwebooDualListContainer');
		
		if (settings.beforeFrom) { select.before($('<div class="SwebooDualListFrom">').text(settings.beforeFrom));}
		
    select.before($('<select id="from_' + id + '" multiple="multiple" size="'+$("#"+id).attr("size")+'">'));
		if (settings.buttons)
		{
			select.before($('<div class="SwebooDualListButtons">')
				.append($('<button id="b_to_' + id + '">')
					.html(button(settings.addText, settings.addImage)))
				.append($('<button id="b_from_' + id + '">')
					.html(button(settings.removeText, settings.removeImage)))
			);
		}
		moveAllOptions(id, 'from_' + id);		
		addTo('from_'+id, id);
		if (settings.beforeTo) select.before($('<div class="SwebooDualListTo>').html(settings.beforeTo));
		
		$('#from_' + id).dblclick(function() { addTo('from_' + id, id); });
		$('#' + id).dblclick(function() { moveFrom(id, 'from_' + id); });

		if (settings.buttons)
		{
			$("#b_to_"+id).click(function(e) { e.preventDefault(); addTo('from_' + id, id); });
			$("#b_from_"+id).click(function(e) { e.preventDefault(); moveFrom(id, 'from_' + id); });
		}
		if ($.fn.emulateDisabled) 
      $('#from_' + id).emulateDisabled();
    if ($.fn.obviouslyDisabled)
      $('#from_' + id).obviouslyDisabled({textColor: settings.ieColor, bgColor: settings.ieBg});
		
	});
	function button(text, image)
	{
		image = (image != '') ? '<img src="' + image + '">' : '';
		return (image + ' ' + text)
	}
	function selectAll(me) {
		$('#' + me + ' option').attr('selected', true);
		$('#from_' + me + ' option').attr('selected', false);
	}
	function addTo(from, to)
	{
		var dest = $("#"+to)[0];

		$("#"+from+" option:selected").clone().each(function() {
			if (this.disabled == true) return
			$(this)
			.appendTo(dest)
			.attr("selected", false);
		});
		$("#"+from+" option:selected")
			.attr("selected", false)
			.attr("disabled", true)
		
		if ($.fn.obviouslyDisabled)
      $("#"+from).obviouslyDisabled({textColor: settings.ieColor, bgColor: settings.ieBg});
	}
	function moveFrom(from, to)
	{
		var dest = $("#"+to)[0];
		$("#"+from+" option:selected").each(function() 
		{
			select = $(this)
			val = select
				.attr("selected", false)
				.val();
			select.remove();
			$('option:disabled', $("#"+to)).each(function() 
			{
				if (this.value == val)
				{
					$(this).attr("disabled", false);
				}
			});
		});
		
		if ($.fn.obviouslyDisabled)
      $("#"+to).obviouslyDisabled({textColor: settings.ieColor, bgColor: settings.ieBg});

	}
	function moveAllOptions(from, to) {
		$("#"+to).html($("#"+from).html())
			.find('option:selected')
			.attr("selected", true);
		$("#"+from).html('');
		
	}

};

$.fn.emulateDisabled = function() 
{
	return this.each(function()
  {
		if ($.browser.msie) eDAdd(this);
	});
	function eDRestore(selectBox) 
	{
		$('option', selectBox).each(function()
		{
			if(this.selected && this.disabled)
			{
				this.selected=false;
				if (selectBox.multiple == false) selectBox.selectedIndex = selectBox.selectCurrent;
			}

		});
	}
	function eDAdd(selectBox)
	{
		selectBox.selectCurrent = '';
		$(selectBox).focus(function(){ this.selectCurrent = this.selectedIndex; });
		$(selectBox).change(function(){ eDRestore(this); });
	}
};

$.fn.obviouslyDisabled = function(settings) 
{
	settings = $.extend({
		textColor: 'graytext',
		bgColor: ''
	}, settings);
	return this.each(function() 
	{
		$('option', this).each(function() 
		{
      if ($.browser.msie) {
  			if (this.disabled) 
  			{
  				this.style.color = settings.textColor;
  				this.style.backgroundColor = settings.bgColor;
  			}
  			else
  			{
  				this.style.backgroundColor = "";
  				this.style.color = "";
  			}
      }
		});
		if ($.browser.msie)
		{
      var bg = (this.style.backgroundColor) ? this.style.backgroundColor : '';
      var diff = (bg == '#FFFFFE') ? '#FFFFFF' : '#FFFFFE';
      this.style.backgroundColor = diff;
      this.style.backgroundColor = bg;
    }
	});
};