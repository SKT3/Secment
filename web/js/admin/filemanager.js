var tinymce = false;
var track = false;

jQuery().ready(function() {
    
    // Track for tinymce
    var track_input = $('#track');
    var tinymce_input = $('#tinymce');
    var place_input = $('#place');
    var custom_input = window.parent.$('#custom_input');
    var parent_target = $('#target');
    if (track_input.length && tinymce_input.length)
    {
        $('body').addClass('tinymce');
        
        track = track_input.val();
        tinymce = tinymce_input.val();
        places = place_input.val();
    }
    
    if(custom_input.length){
        var target = window.parent.$('#'+custom_input.val());
        $('#file-manager ul li.image a.el, #file-manager ul li.doc a.el').on('click', function() {
            var file = $(this).attr('href');

            target.val(file);
            window.parent.$('#filemanager').dialog('close');
            window.parent.trigger_FM_change();
            
            return false;
        });
    }

    if(parent_target.length){
        var target = window.parent.$('#'+parent_target.val());
        $('#file-manager ul li.image a.el, #file-manager ul li.doc a.el').on('click', function() {
            var file = $(this).attr('href');

            target.val(file);
            
            window.parent.$('#filemanager').dialog('close');
            window.parent.trigger_FM_change();
            
            return false;
        });
    }

    if (track && tinymce)
    {
        if (places == "" && tinymce == 'images')
        {
            $('#file-manager ul li.image a.el').on('click', function() {
                var file = $(this).attr('href');

                apply_img(file);
                
                return false;
            });
        }
        
        if (tinymce == 'all')
        {
            $('#file-manager ul li:not(.folder) a.el').on('click', function() {
                var file = $(this).attr('href');

                apply_link(file);
                
                return false;
            });
        }

        if (places != "" && (tinymce == 'videos' || tinymce == 'images'))
        {
            $('#file-manager ul li.video a.el, #file-manager ul li.image a.el').on('click', function() {
                var file = $(this).attr('href');

                apply_video(file, places);
                
                return false;
            });
        }
    }
    
    $('#file-manager ul li .delete').on('click', function() {
        var el = $(this);
        var link = el.attr('href');
       
        el.toggleClass('checked');
        
        
        return false;
    });
    
    // Automatic uploadin on change
    $('#file-manager nav form input[type=file]').on('change', function() {
        $("#ajax-load").fadeIn('fast');
        $('#file-manager nav form').submit();
    });
    
    $('#file-manager .list button.delete').on('click', function() {
        var collection = new Array();
        
        if ($('#file-manager ul .delete.checked').length)
        {
            var i=0;
            $('#file-manager ul .delete.checked').each(function() {
                var rel = $(this).attr('rel');
                collection[i] = rel;
                
                i++;
            });
        }
        
        $.get($(this).attr('href'), {'delete_files' : JSON.stringify(collection)}, function() {
            $('#file-manager ul .delete.checked').each(function() {
                var parent = $(this).parents('li');
                parent.fadeOut('fast', function() {
                    $(this).remove();
                });
            });
        });
        
        return false;
    });
    
    // Rename dir or file
    $('#file-manager ul li strong').on('dblclick', function() {
        var strong = $(this);
        var val = strong.attr('title');
        
        var change_name = prompt("", val);
        
        if (change_name != null && change_name != "" && change_name != val)
        {
            var parent = strong.parents('li');
            var to_url = $('#file-manager .list button.delete').attr('href');
            
            if (parent.hasClass('folder'))
            {
                var folder = strong.html();
                
                $.getJSON(to_url, {'rename_folder' : folder, 'new_name' : change_name}, function(data) {
                    if (data.error == false)
                    {
                        location.reload();
                    }
                });
            }
            else
            {
                var file = $('.el', parent);
                file = file.attr('href');
                
                $.getJSON(to_url, {'rename_file' : file, 'new_name' : change_name}, function(data) {
                    if (data.error == false)
                    {
                        location.reload();
                    }
                });
            }
        }
        
        return false;
    }).on('contextmenu', function(e) {
        var strong = $(this);
        var parent = strong.parents('li');
        
        var context_menu = $('.context-menu', parent);
        $('.context-menu').hide();    
        context_menu.show();
    
        return false;
    });
    
    $('#file-manager ul li .context-menu .btn-rename').on('click', function() {
        var parent = $(this).parents('li');
        $('strong', parent).dblclick();
    });
    
    $('#file-manager ul li .context-menu .btn-delete').on('click', function() {
        var link = $(this).attr('href');
        var parent = $(this).parents('li');

        $.get(link, function() {
            parent.fadeOut('fast', function() {
                $(this).remove();

            });
        });
        
        return false;
    });
    
    $(document).click(function() {
        $('#file-manager ul .context-menu').hide();
    });
});


/*
function apply(file){
    var path = $('#cur_dir').val();
    var base_url = $('#base_url').val();
    var track = $('#track').val();
    var target = window.parent.document.getElementById(track + '_ifr');
    var closed = window.parent.document.getElementsByClassName('mce-filemanager');
    var ext = file.split('.').pop();
    var fill = '';
    if ($.inArray(ext, ext_img) > -1) {

        fill = $("<img />", {"src": path + file});
    } else {
        fill = $("<a />").attr("href", path + file).text(file.replace(/\..+$/, ''));
    }
    $(target).contents().find('#tinymce').append(fill);
    $(closed).find('.mce-close').trigger('click');
}
*/

function apply_link(file) {
    var track = $('#track').val();
    
    $('.mce-link_' + track, window.parent.document).val(file);
    var closed = window.parent.document.getElementsByClassName('mce-filemanager');
    
    if ($('.mce-text_' + track, window.parent.document).val() == '')
    {
        $('.mce-text_' + track, window.parent.document).val(file);
    }
    
    $(closed).find('.mce-close').trigger('click');
}

function apply_img(file) {
    var track = $('#track').val();
    
    var target = window.parent.document.getElementsByClassName('mce-img_' + track);
    var closed = window.parent.document.getElementsByClassName('mce-filemanager');
    
    // Put dimensions
    var parent = $(target).parents('.mce-container-body');

    $(target).val(file);
    $(closed).find('.mce-close').trigger('click');
}

function apply_video(file, type_file) {
    var track = $('#track').val();
    
    var target = window.parent.document.getElementsByClassName('mce-' + type_file + '_' + track);
    var closed = window.parent.document.getElementsByClassName('mce-filemanager');
    
    $(target).val(file);
    $(closed).find('.mce-close').trigger('click');
}