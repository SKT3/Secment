function load_characteristics(type_id,device_id)
{
    $.get(xhr_url+'?method=load_characteristics_values',{'type_id': type_id, 'device_id': device_id},function(d){
        $('#features').html('');
        if(d.data.length==0) {return;}
        for(i in d.data) {
            $('#features').append('<li id="features_'+i+'"><h3>'+d.data[i].title+'</h3><br /><br /></li>');
            for(j in d.data[i].features) {
                $('#features_'+i).append('<label>'+d.data[i].features[j].title+'</label>'+d.data[i].features[j].field+' '+d.data[i].features[j].numeric_field+'<br />');
            }

        }
    },'json');
}

$('#id_type_id').bind('change',function(){
    load_characteristics($(this).val(),object_id);
});

$('#id_type_id').change();