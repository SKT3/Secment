$(function () {
    $('#fileupload').fileupload({
        dataType: 'json',
        autoUpload: true,
        sequentialUploads: true,
        acceptFileTypes: /(\.|\/)(gif|jpe?g|png)$/i ,
        send: function(e, data) {
            $("#ajax-load").css("visibility", 'visible');
            $("#ajax-load").css("z-index", 1000);
        },
        done: function (e, data) {
            $("#ajax-load").css("visibility", 'hidden');
            $("#ajax-load").css("z-index", 0);

            if($('#fileupload').hasClass('single'))
            {
                $('.file li img').remove();
                $('.file li').append('<img src="'+data.result.img+'" alt="" />');
            }
            else {
                $('.files').append('<li id="f_'+data.result.id+'" style="display:none;">\
                    <img src="'+data.result.img+'" alt="" /> \
                    <strong>'+data.result.name+'</strong> \
                    <em>('+data.result.size+')</em> \
                    <button class="delete" type="button" data-url="'+data.result.delete_url+'">Delete</button> \
                </li>');
                $('#f_'+data.result.id).slideDown('normal');
            }
        },
    });
});

$('.files button.delete').live('click',function(){
    obj = this;
    $.get($(this).attr('data-url'),{'id':$(this).parents('li').attr('id').replace('f_','')},function(){
        $(obj).parents('li').slideUp('normal',function(){
            $(this).remove();
        });
    })
});

$('.files').sortable({ 
    handle: "img",
    update: function(event,ui) {
        $.get($('.files').attr('data-sort')+'&'+$('.files').sortable('serialize'))
    },

});
$('.files').disableSelection();