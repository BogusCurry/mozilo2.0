
var edit_handler = function(event) {
    event.preventDefault();
    if($(this).hasClass("ui-state-disabled"))
        return false;

    $('#js-ace-color-img').css('display','none');
    $("#js-editor-toolbar").css("display","block");
    if($(this).hasClass("js-css")) {
        $('#colordiv-editor').append($('.colordiv'));
        $('#js-ace-color-img').css('display','inline');
        if($('#select-mode').val() != "text") {
            $('#select-mode option:selected').attr('selected',false)
            $('#select-mode option[value="css"]').attr('selected',true)
        }
        $("#js-editor-toolbar").css("display","none");
    }
    if($(this).hasClass("js-html")) {
        $('#colordiv-mozilo').append($('.colordiv'));
        if($('#select-mode').val() != "text") {
            $('#select-mode option:selected').attr('selected',false)
            $('#select-mode option[value="html"]').attr('selected',true)
        }
    }

    editor_session.setMode("ace/mode/"+$('#select-mode').val());

    // wenn sich im FileUpload was geändert hat deshalb hollen wir immer dei selectbox template
    send_data("templateselectbox=true",$('select[name="template_css"]'));

    $(dialog_editor).dialog("option", "width", $(".mo-td-content-width").eq(0).width());
    $(dialog_editor).dialog("option", "height", (parseInt($(window).height()) - dialogMaxheightOffset));

var file = $(this).closest(".js-tools-show-hide").find(".js-filename").text();

    $(dialog_editor).dialog("option", "title", "Berabeiten "+file);
    $(dialog_editor).data("send_object",false);

//    $(dialog_editor).dialog("open");
// $("#out").html($("#out").html()+"<br>para = "+user_para+"&templatefile="+$(this).attr("name"));
    editor_file = "configtemplate=true&templatefile="+file;

    send_editor_data(editor_file,false);
}
$(function() {
    $('.js-edit-template').bind("click", edit_handler);
});
