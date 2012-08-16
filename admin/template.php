<?php if(!defined('IS_ADMIN') or !IS_ADMIN) die();

function template() {
    global $CMS_CONF;
    $LAYOUT_DIR     = BASE_DIR.LAYOUT_DIR_NAME."/".$CMS_CONF->get("cmslayout").'/';

    if(getRequestValue('chancefiles') == "true") {
        require_once(BASE_DIR_ADMIN."jquery/File-Upload/upload.class.php");
    }

    if(false !== ($newfile = getRequestValue('newfile','post'))
            and false !== ($orgfile = getRequestValue('orgfile','post'))
            and false !== ($curent_dir = getRequestValue('curent_dir','post'))) {
        $dir = BASE_DIR.LAYOUT_DIR_NAME."/".str_replace('%2F','/',$curent_dir)."/";
        if(true !== ($error = moveFileDir($dir.$orgfile,$dir.$newfile,true))) {
            ajax_return("error",true,$error,true,"js-dialog-reload");
        }
        ajax_return("success",true);
    }

    if(getRequestValue('templateselectbox','post') == "true") {
        require_once(BASE_DIR_ADMIN.'editsite.php');
        # wir schiken die neue selectbox zurück
        echo '<span id="replace-item">'.returnTemplateSelectbox().'</span>';
        ajax_return("success",true);
    }

    if(getRequestValue('configtemplate','post') == "true") {
        if(false !== ($templatefile = getRequestValue('templatefile','post',false))
                and !file_exists($LAYOUT_DIR.$templatefile)) {
            ajax_return("error",true,returnMessage(false,getLanguageValue("error_no_file_dir").$LAYOUT_DIR.$templatefile),true,true);
        }
        if(false !== ($content = getRequestValue('content','post',false))) {
            if(false === (mo_file_put_contents($LAYOUT_DIR.$templatefile,$content))) {
                ajax_return("error",true,returnMessage(false,getLanguageValue("editor_content_error_save")),true,true);
            }
            echo ajax_return("success",false);
        } else {
            if(false === ($syntax = get_contents_ace_edit($LAYOUT_DIR.$templatefile))) {
                ajax_return("error",true,returnMessage(false,getLanguageValue("editor_content_error_open")),true,true);
            }
            echo '<textarea id="page-content">'.$syntax.'</textarea>';
            echo ajax_return("success",false);
        }
        exit();
    }

    $template = array();

    $dircontent = getDirAsArray($LAYOUT_DIR,array(".html"),"natcasesort");
    foreach($dircontent as $file) {
        $template["template_title_html_css"][] = '<table class="js-tools-show-hide mo-tag-height-from-icon" width="100%" cellspacing="0" border="0" cellpadding="0">'
                    .'<tbody>'
                    .'<tr>'
                        .'<td class="mo-nowrap" width="99%">'
                            .'<span class="js-filename mo-padding-left">'.$file.'</span>'
                        .'</td>'
                        .'<td class="mo-nowrap">'
                            .'<img class="js-tools-icon-show-hide js-edit-template js-html mo-tool-icon" src="'.ADMIN_ICONS.'page-edit.png" alt="page-edit" hspace="0" vspace="0" />'
                        .'</td>'
                    .'</tr>'
                    .'</tbody>'
                    .'</table>';

    }

    $dircontent = getDirAsArray($LAYOUT_DIR.'css',array(".css"),"natcasesort");
    foreach($dircontent as $file) {
        $template["template_title_html_css"][] = '<table class="js-tools-show-hide mo-tag-height-from-icon" width="100%" cellspacing="0" border="0" cellpadding="0">'
                    .'<tbody>'
                    .'<tr>'
                        .'<td class="mo-nowrap" width="99%">'
                            .'<span class="js-filename mo-padding-left"><span class="mo-bold mo-padding-right">css/</span>'.$file.'</span>'
                        .'</td>'
                        .'<td class="mo-nowrap">'
                            .'<img class="js-tools-icon-show-hide js-edit-template js-css mo-tool-icon" src="'.ADMIN_ICONS.'page-edit.png" alt="page-edit" hspace="0" vspace="0" />'
                        .'</td>'
                    .'</tr>'
                    .'</tbody>'
                    .'</table>';
    }

    require_once(BASE_DIR_ADMIN."jquery/File-Upload/fileupload.php");
    $pagecontent = getFileUpload($CMS_CONF->get("cmslayout").'/grafiken');

    $tmpl = get_template_truss('<li class="mo-li ui-corner-all">'.$pagecontent.'</li>',"template_title_grafiken",true);


    return contend_template($template).$tmpl.pageedit_dialog();
}

?>