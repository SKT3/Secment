var the_tree = [];

//$(document).ready(function(){
    var select = $('*[name="category_id"]');
    var current_category_id = select.val();
    

    
    if($('#main_form').length == 1) { // form
        var li = select.parents('li');
        $('<li><label>Под Под Група</label><select class="cs" id="cat_2"></select></li>').insertAfter(li);
        $('<li><label>Под Група</label><select class="cs" id="cat_1"></select></li>').insertAfter(li);
        $('<li><label>Основна Група</label><select class="cs" id="cat_0"></select></li>').insertAfter(li);
    } else { // filter
        var li = select.parents('tr');
        $('<tr><td><label>Под Под Група</label></td><td><select class="cs" id="cat_2"></select></td></tr>').insertAfter(li);
        $('<tr><td><label>Под Група</label></td><td><select class="cs" id="cat_1"></select></td></tr>').insertAfter(li);
        $('<tr><td><label>Основна Група</label></td><td><select class="cs" id="cat_0"></select></td></tr>').insertAfter(li);
    }
    

    select.parents('form').append('<input type="hidden" name="category_id" id="id_category_id" />');

    li.remove();

    $('#cat_0, #cat_1, #cat_2').on('change',trigger_select_change);
    get_tree(current_category_id);
//});

function trigger_select_change(){
    $('#id_category_id').val('');
    idx = $('.cs').index(this);
    $('.cs:gt('+idx+')').html('');
    if(this.value) {
        lvl = this.id.replace('cat_','');
        
        if(lvl<2) {
            lvl = parseInt(lvl)+1;
            
            fill_select(this.value, lvl);
        } else {
            $('#id_category_id').val(this.value);
        }
    }
}

function fill_select(root,level) {

    if(root.toString() != '') {
        $('#cat_'+level).html('<option value="">-</option>');
        current_tree = the_tree[root]['items'];
        for(d in current_tree) {
            $('#cat_'+level).append('<option value="'+d+'">'+current_tree[d]+'</option>');
        }
        $('#cat_'+level).val(the_tree['selected'][level]).trigger('change');
        the_tree['selected'][level] = 0;

    }
}

function get_tree(current_category_id) {
    $.get(xhr_url+'?method=get_tree&category_id='+current_category_id,function(d){
        the_tree = d['tree'];
        fill_select(0,0);
    },'json');
}