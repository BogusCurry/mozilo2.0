<?php
session_start();
define("IS_CMS",true);
define("IS_ADMIN",false);
$start_time = get_executTime(false);
/* 
echo "<pre style=\"position:fixed;background-color:#000;color:#0f0;padding:5px;font-family:monospace;border:2px solid #777;\">";
print_r($_REQUEST);
echo "</pre>"; 
*/

// Initial: Fehlerausgabe unterdruecken, um Path-Disclosure-Attacken ins Leere laufen zu lassen
@ini_set("display_errors", 1);

# ab php > 5.2.0 hat preg_* ein default pcre.backtrack_limit von 100000 zeichen
# deshalb der versuch mit ini_set
@ini_set('pcre.backtrack_limit', 1000000);

# fals da bei winsystemen \\ drin sind in \ wandeln
$BASE_DIR = str_replace("\\\\", "\\",__FILE__);
# zum schluss noch den teil denn wir nicht brauchen abschneiden
$BASE_DIR = substr($BASE_DIR,0,-(strlen("index.php")));
define("BASE_DIR",$BASE_DIR);
unset($BASE_DIR);

define("CMS_DIR_NAME","cms");

if(is_file(BASE_DIR.CMS_DIR_NAME."/DefaultConfCMS.php")) {
    require_once(BASE_DIR.CMS_DIR_NAME."/DefaultConfCMS.php");
} else {
    die("Fatal Error ".BASE_DIR.CMS_DIR_NAME."/DefaultConfCMS.php Datei existiert nicht");
}

if(is_file(BASE_DIR_CMS."DefaultFunc.php")) {
    require_once(BASE_DIR_CMS."DefaultFunc.php");
} else {
    die("Fatal Error ".BASE_DIR_CMS."DefaultFunc.php Datei existiert nicht");
}

$_GET = cleanREQUEST($_GET);
$_REQUEST = cleanREQUEST($_REQUEST);
$_POST = cleanREQUEST($_POST);
#------------------------------

require_once(BASE_DIR_CMS."SpecialChars.php");
require_once(BASE_DIR_CMS."Properties.php");

$specialchars   = new SpecialChars();
$CMS_CONF     = new Properties(BASE_DIR_CMS.CONF_DIR_NAME."/main.conf.php");
$GALLERY_CONF  = new Properties(BASE_DIR_CMS.CONF_DIR_NAME."/gallery.conf.php");
$USER_SYNTAX  = new Properties(BASE_DIR_CMS.CONF_DIR_NAME."/syntax.conf.php");
#define("URL_BASE",substr($_SERVER['PHP_SELF'],0,strpos($_SERVER['PHP_SELF'],"index.php")));

require_once(BASE_DIR_CMS.'idna_convert.class.php');
$Punycode = new idna_convert();

require_once(BASE_DIR_CMS."Language.php");
$language       = new Language();

$activ_plugins = array();
$deactiv_plugins = array();
$plugin_first = array();
# Vorhandene Plugins finden und in array $activ_plugins und $deactiv_plugins einsetzen
# wird für Search und Pluginplatzhaltern verwendet
list($activ_plugins,$deactiv_plugins,$plugin_first) = findPlugins();
require_once(BASE_DIR_CMS."Syntax.php");
require_once(BASE_DIR_CMS."Smileys.php");
$syntax         = new Syntax();
$smileys        = new Smileys(BASE_DIR_CMS."smileys");

require_once(BASE_DIR_CMS."Plugin.php");

$LAYOUT_DIR     = LAYOUT_DIR_NAME."/".$CMS_CONF->get("cmslayout");
$TEMPLATE_FILE  = $LAYOUT_DIR."/template.html";

$LAYOUT_DIR_URL = $specialchars->replaceSpecialChars(URL_BASE.$LAYOUT_DIR,true);
$CSS_FILE       = $LAYOUT_DIR_URL."/css/style.css";
$FAVICON_FILE   = $LAYOUT_DIR_URL."/favicon.ico";

$WEBSITE_NAME = $specialchars->rebuildSpecialChars($CMS_CONF->get("websitetitle"),false,true);
if ($WEBSITE_NAME == "")
    $WEBSITE_NAME = "Titel der Website";

if ($CMS_CONF->get("usecmssyntax") == "false")
    define("USE_CMS_SYNTAX",false);
else
    define("USE_CMS_SYNTAX",true);

# Steuerungs element über die url
# Draft modus geht nur wenn man im admin eingelogt ist
if(getRequestValue('draft') == "true" and $CMS_CONF->get("draft"))
    define("DRAFT",true);
else
    define("DRAFT",false);

# wenn ein Plugin die gallerytemplate.html benutzten möchte
# reicht es wenn in der URL galtemplate=??? enthalten ist ??? können Galerien sein
if(getRequestValue("galtemplate", "get")) {
    $TEMPLATE_FILE  = $LAYOUT_DIR."/gallerytemplate.html";
}

$template = getTemplate($TEMPLATE_FILE);
/*
$plugin_such_ohne = "/\{([^\|\{]+)\}/U";
$plugin_such = "/\{(.+)\|([^\{]*)\}/U";

$find_first_plugins_content = substr($template,0,stripos($template,"<head>"));

preg_match_all($plugin_such_ohne, $find_first_plugins_content, $plugins_first);
if(count($plugins_first[0]) > 0)
    $plugins_first[2] = array_fill(0, count($plugins_first[0]), false);
else
    $plugins_first[2] = array();
preg_match_all($plugin_such, $find_first_plugins_content, $plugins_first1);
$result[0] = array_merge($plugins_first1[0], $plugins_first[0]);
$result[1] = array_merge($plugins_first1[1], $plugins_first[1]);
$result[2] = array_merge($plugins_first1[2], $plugins_first[2]);
foreach($result[0] as $pos => $string) {
    $replace = $syntax->plugin_replace($result[1][$pos],$result[2][$pos]);
    $template = str_replace($string,$replace,$template);
}
*/


# bei false wird CAT_REQUEST, PAGE_REQUEST auf NULL gesetzt
# dardurch gibts kein activen punkt im menu
#if(getRequestValue('mactiv') and getRequestValue('mactiv') == "false")
#    define("MENU_ACTIVE",false);

# was soll als content gezeigt werden suchergebnis, sidemap, page, null(ist für plugins intresant)
#if(in_array(getRequestValue('mpage'),array("sitemap","search","null")))
#    define("ACTION_CONTENT",getRequestValue('mpage'));

# default verhalten setzen
#if(ACTION_REQUEST !== NULL) {
#    if(!defined("MENU_ACTIVE"))
#        define("MENU_ACTIVE",false);
#    if(!defined("ACTION_CONTENT"))
#        define("ACTION_CONTENT",ACTION_REQUEST);
#} else {
#    if(!defined("MENU_ACTIVE"))
#        define("MENU_ACTIVE",true);
#    if(!defined("ACTION_CONTENT"))
#        define("ACTION_CONTENT","page");
#}
// Request-Parameter einlesen und dabei absichern
$SEARCH_REQUEST = stripcslashes(getRequestValue('search'));
$HIGHLIGHT_REQUEST = getRequestValue('highlight');

$HTML                   = "";

require_once(BASE_DIR_CMS."CatPageClass.php");
$CatPage         = new CatPageClass();

# aus mod_rewrite url $_GET['cat'] und $_GET['page'] erstellen
if(!array_key_exists("cat",$_GET)) {
    # ein tmp dafor weil wenn URL_BASE = / ist werden alle / ersetzt durch nichts
    $url_get = str_replace("tmp".URL_BASE,"","tmp".$_SERVER['REQUEST_URI']);
    $url_get = str_replace("&amp;","&",$url_get);
    $QUERY_STRING = str_replace("&amp;","&",$_SERVER['QUERY_STRING']);
    $url_get = str_replace("?".$QUERY_STRING,"",$url_get);
    $url_get = str_replace('%252F','%2F',$url_get);
    # / nach : damit es wie eine cat:page syntax aussieht
    $url_get = str_replace('/',':',$url_get);
    if(substr($url_get,-5) == ".html")
        $url_get = substr($url_get,0,-5);
    $tmp = $CatPage->split_CatPage_fromSyntax(rawurldecode($url_get));
    $_GET['cat'] = cleanValue($tmp[0]);
    $_GET['page'] = cleanValue($tmp[1]);
    unset($tmp,$QUERY_STRING,$url_get);
}


$pagecontent = false;

foreach($plugin_first as $plugin) {
    if(file_exists(PLUGIN_DIR_REL.$plugin."/index.php")) {
        // Plugin-Code includieren
        require_once(PLUGIN_DIR_REL.$plugin."/index.php");
        if(class_exists($plugin)) {
            $tmp_plugin = new $plugin();
            $tmp_plugin->getPluginContent("plugin_first");
#            $replace = $tmp_plugin->getPluginContent("plugin_first");
        }
#        if($replace !== false)
#            $template = str_replace("{".$plugin."}",$replace,$template);
        unset($tmp_plugin);
    }
}

# was wird als detailmenu angezeigt
# search=suchworte sitemap=Sitmap NULL=page/oder nichts bei MENU_ACTIVE false
# bei sitemap, search wird CAT_REQUEST, PAGE_REQUEST auf NULL gesetzt
if(!defined("ACTION_REQUEST")) {
    if(in_array(getRequestValue('action'),array("sitemap","search")))
        define("ACTION_REQUEST",getRequestValue('action'));
    else
        define("ACTION_REQUEST",false);
}

# default verhalten setzen
if(!defined("ACTION_CONTENT"))
    define("ACTION_CONTENT",ACTION_REQUEST);
/*if(ACTION_REQUEST !== false) {
} else {
    if(!defined("ACTION_CONTENT"))
        define("ACTION_CONTENT",false);
}
*/

/*echo "<pre>";
print_r($CatPage->CatPageArray);
echo "</pre><br>\n";*/

// Zuerst: Uebergebene Parameter ueberpruefen
set_CatPageRequest();

// Dann: HTML-Template einlesen und mit Inhalt fuellen
readTemplate($template,$pagecontent);
# manche Provider sind auf iso eingestelt
header('content-type: text/html; charset='.CHARSET.'');

if(strpos($HTML,"<!--{MEMORYUSAGE}-->") > 1)
    $HTML = str_replace("<!--{MEMORYUSAGE}-->",get_memory(),$HTML);

if(strpos($HTML,"<!--{EXECUTETIME}-->") > 1)
    $HTML = str_replace("<!--{EXECUTETIME}-->",get_executTime($start_time),$HTML);
// Zum Schluß: Ausgabe des fertigen HTML-Dokuments
echo $HTML;

function get_executTime($start_time) {
    if(!function_exists('gettimeofday'))
        return NULL;
    list($usec, $sec) = explode(" ", microtime());
    if($start_time === false) {
        return ((float)$usec + (float)$sec);
    }
    return "Seite in ".sprintf("%.4f", (((float)$usec + (float)$sec) - $start_time))." Sek. erstelt";
}

function get_memory() {
    $size = memory_get_usage();
    if(function_exists('memory_get_peak_usage'))
        $size = memory_get_peak_usage();
    $unit=array('B','KB','MB','GB','TB','PB');
    return @round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i].' Memory Benutzt';
}

// ------------------------------------------------------------------------------
// Parameter auf Korrektheit pruefen
// ------------------------------------------------------------------------------
function set_CatPageRequest() {
    if(defined("CAT_REQUEST") and defined("PAGE_REQUEST"))
        return;

    // Wenn ein Action-Parameter uebergeben wurde: keine aktiven Kat./Inhaltts. anzeigen
    # $CAT_REQUEST und $PAGE_REQUEST bleiben lehr
    if((ACTION_REQUEST == "sitemap") or (ACTION_REQUEST == "search")) {
        define("CAT_REQUEST",NULL);
        define("PAGE_REQUEST",NULL);
        return;
    }

    global $CatPage;

    $CAT_REQUEST_URL = $CatPage->get_UrlCoded(getRequestValue('cat', 'get'));
    $PAGE_REQUEST_URL = $CatPage->get_UrlCoded(getRequestValue('page', 'get'));

#!!!!!!!!!!! file upload
#exists_File( $cat, $file )
    # übergebene cat und page gibts
    if($CatPage->exists_CatPage($CAT_REQUEST_URL,$PAGE_REQUEST_URL)
        ) {
        define("CAT_REQUEST",$CatPage->get_AsKeyName($CAT_REQUEST_URL));
        define("PAGE_REQUEST",$CatPage->get_AsKeyName($PAGE_REQUEST_URL));
        return;
    # übergebene cat gibts aber page nicht cat hat aber pages
    } elseif($CatPage->exists_CatPage($CAT_REQUEST_URL,false)
        and $CatPage->get_FirstPageOfCat($CAT_REQUEST_URL)
        ) {
        define("CAT_REQUEST",$CatPage->get_AsKeyName($CAT_REQUEST_URL));
        # erste page nehmen
        define("PAGE_REQUEST",$CatPage->get_FirstPageOfCat(CAT_REQUEST));
        return;
    }

    # so wir sind bishierher gekommen dann probieren wirs mit defaultcat
    # oder mit erster cat die page hat
    $DEFAULT_CATEGORY = $CAT_REQUEST_URL;
    # $CAT_REQUEST_URL ist lehr
    # oder $CAT_REQUEST_URL gibts nicht als cat
    # oder $CAT_REQUEST_URL hat keine pages
    # dann defaultcat aus conf holen
    if(empty($CAT_REQUEST_URL)
        or !$CatPage->exists_CatPage($CAT_REQUEST_URL,false)
        or !$CatPage->get_FirstPageOfCat($CAT_REQUEST_URL)
        ) {
        global $CMS_CONF;
        $DEFAULT_CATEGORY = $CMS_CONF->get("defaultcat");
    }
    # prüfen ob die $DEFAULT_CATEGORY existiert
    if($CatPage->exists_CatPage($DEFAULT_CATEGORY,false)) {
        # die erste page holen
        # und setze $CAT_REQUEST und $PAGE_REQUEST
        define("CAT_REQUEST",$CatPage->get_AsKeyName($DEFAULT_CATEGORY));
        if($CatPage->get_FirstPageOfCat(CAT_REQUEST))
            define("PAGE_REQUEST",$CatPage->get_FirstPageOfCat(CAT_REQUEST));
        else
            define("PAGE_REQUEST",NULL);
    # defaultcat gibts nicht hol die erste cat die auch pages hat und setze sie
    } else {
        list($CAT_REQUEST,$PAGE_REQUEST) = $CatPage->get_FirstCatPage();
        if($CatPage->exists_CatPage($CAT_REQUEST,false))
            define("CAT_REQUEST",$CAT_REQUEST);
        if($CatPage->exists_CatPage($CAT_REQUEST,$PAGE_REQUEST))
            define("PAGE_REQUEST",$PAGE_REQUEST);
    }
    if(!defined("CAT_REQUEST"))
        define("CAT_REQUEST",NULL);
    if(!defined("PAGE_REQUEST"))
        define("PAGE_REQUEST",NULL);
}

// ------------------------------------------------------------------------------
// HTML-Template einlesen und verarbeiten
// ------------------------------------------------------------------------------
function readTemplate($template,$pagecontent) {
    global $HTML;
    global $TEMPLATE_FILE;
    global $HIGHLIGHT_REQUEST;
    global $language;
    global $syntax;
    global $CMS_CONF;
    global $smileys;

#    $template = getTemplate($TEMPLATE_FILE);

#    $pagecontent = "";

    # ist nur true wenn Inhaltseite eingelesen wird
    $is_Page = false;
    if(ACTION_CONTENT == "sitemap") {
        $pagecontent = getSiteMap();
    } elseif(ACTION_CONTENT == "search") {
        require_once(BASE_DIR_CMS."SearchClass.php");
        $search = new SearchClass();
        $pagecontent = $search->searchInPages();
    } elseif($pagecontent === false) {
        # Inhaltseite wird eingelesen und USE_CMS_SYNTAX wird benutzt
        if(USE_CMS_SYNTAX)
            $is_Page = true;
        $pagecontent = getContent();
    }

    # wenn im Template keine Inhaltseite benutzt wird
    if(!strstr($template,"{CONTENT}"))
        $is_Page = false;

    $HTML = str_replace('{CONTENT}','---content~~~'.$pagecontent.'~~~content---',$template);
    $HTML = $syntax->convertContent($HTML, $is_Page);
    unset($pagecontent);

    // Smileys ersetzen
    if($CMS_CONF->get("replaceemoticons") == "true") {
        $HTML = $smileys->replaceEmoticons($HTML);
    }

    // Gesuchte Phrasen hervorheben
    if($HIGHLIGHT_REQUEST <> "") {
        require_once(BASE_DIR_CMS."SearchClass.php");
        $search = new SearchClass();
        # wir suchen nur im content teil
        list($content_first,$content,$content_last) = $syntax->splitContent($HTML);
        $content = $search->highlightSearch($content);
        $HTML = $content_first.$content.$content_last;
        unset($content_first,$content,$content_last);
    }

#    $HTML = str_replace(array('&#123;','&#125;','&#91;','&#93;'),array('{','}','[',']'),$HTML);
    $HTML = str_replace(array('---content~~~','~~~content---'),"",$HTML);
}


// ------------------------------------------------------------------------------
// Inhalt einer Content-Datei einlesen, Rueckgabe als String
// ------------------------------------------------------------------------------
function getContent() {
    global $CatPage;
    # kein Draft mode und page ist draft
    if(!DRAFT and $CatPage->get_Type(CAT_REQUEST,PAGE_REQUEST) == EXT_DRAFT)
        return "";
    elseif($CatPage->exists_CatPage(CAT_REQUEST,PAGE_REQUEST))
        return $CatPage->get_PageContent(CAT_REQUEST,PAGE_REQUEST);
    return "";
}

// ------------------------------------------------------------------------------
// Erzeugung einer Sitemap
// ------------------------------------------------------------------------------
function getSiteMap() {
    global $language;
    global $CMS_CONF;
    global $CatPage;

    $include_pages = array(EXT_PAGE);
    if($CMS_CONF->get("showhiddenpagesinsitemap") == "true") {
        $include_pages = array(EXT_PAGE,EXT_HIDDEN);
    }

    $sitemap = "<h1>".$language->getLanguageValue("message_sitemap_0")."</h1>"
    ."<div class=\"sitemap\">";
    // Kategorien-Verzeichnis einlesen
    $categoriesarray = $CatPage->get_CatArray(false, false, $include_pages);
    // Jedes Element des Arrays an die Sitemap anhaengen
    foreach ($categoriesarray as $currentcategory) {
        $sitemap .= "<h2>".$CatPage->get_HrefText($currentcategory,false)."</h2><ul>";
        // Inhaltsseiten-Verzeichnis einlesen
        $contentarray = $CatPage->get_PageArray($currentcategory,$include_pages,true);
        // Alle Inhaltsseiten der aktuellen Kategorie auflisten...
        // Jedes Element des Arrays an die Sitemap anhaengen
        foreach ($contentarray as $currentcontent) {
            $url = $CatPage->get_Href($currentcategory,$currentcontent);
            $urltext = $CatPage->get_HrefText($currentcategory,$currentcontent);
            $titel = $language->getLanguageValue("tooltip_link_page_2", $CatPage->get_HrefText($currentcategory,$currentcontent), $CatPage->get_HrefText($currentcategory,false));

            $sitemap .= "<li>".$CatPage->create_LinkTag($url,$urltext,false,$titel)."</li>";
        }
        $sitemap .= "</ul>";
    }
    $sitemap .= "</div>";
    // Rueckgabe der Sitemap
    return $sitemap;
}

function getTemplate($TEMPLATE_FILE) {
    global $CMS_CONF;
    global $language;
    if(false === ($template = file_get_contents($TEMPLATE_FILE)))
        die($language->getLanguageValue("message_template_error_1", $TEMPLATE_FILE));
    # usesubmenu aus der template.html auslesen und setzten
    $dummy = 1;
    if(strpos($template,"usesubmenu") > 1 and strpos($template,"usesubmenu") < 10) {
        $tmp = substr($template,0,strpos($template,"<!DOCTYPE"));
        $tmp = substr(trim(substr($tmp,strpos($tmp,"=") + 1)),0,1);
        if(ctype_digit($tmp) and $tmp <= 2)
            $dummy = $tmp;
        $template = substr($template,strpos($template,"<!DOCTYPE"));
    }
    $CMS_CONF->set("usesubmenu",$dummy);
    return $template;
}

?>