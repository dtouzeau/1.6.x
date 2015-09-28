<?php
if(isset($_GET["VERBOSE"])){$GLOBALS["VERBOSE"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}
if(isset($_GET["VERBOSE"])){$GLOBALS["VERBOSE"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}

	include_once('ressources/class.templates.inc');
	include_once('ressources/class.ldap.inc');
	include_once('ressources/class.users.menus.inc');
	include_once('ressources/class.artica.inc');
	include_once('ressources/class.ini.inc');
	include_once('ressources/class.squid.inc');
	include_once('ressources/class.dansguardian.inc');
	header("Pragma: no-cache");	
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");	
	
	
	$users=new usersMenus();
	if(!$users->CORP_LICENSE){
		$WEBFILTERING_TOP_MENU=WEBFILTERING_TOP_MENU();
		$tpl=new templates();
		echo $tpl->_ENGINE_parse_body("<div style='font-size:30px;margin-bottom:20px'>$WEBFILTERING_TOP_MENU</div>");
		echo FATAL_ERROR_SHOW_128("{this_feature_is_disabled_corp_license}");
		die();
	}
	
	
	if(!IsPersonalCategoriesRights()){
		$tpl=new templates();
		echo FATAL_ERROR_SHOW_128("{ERROR_NO_PRIVS}");
		exit;
	}
	
if(isset($_GET["js"])){js();}
if(isset($_GET["add-perso-cat-js"])){add_category_js();exit;}
if(isset($_GET["tabs"])){tabs();exit;}
if(isset($_GET["categories"])){categories();exit;}
if(isset($_GET["category-search"])){categories_search();exit;}
if(isset($_GET["add-perso-cat-popup"])){add_category_popup();exit;}
if(isset($_GET["add-perso-cat-tabs"])){add_category_tabs();exit;}

if(isset($_GET["category-delete-js"])){del_category_js();exit;}
if(isset($_POST["delete-personal-cat"])){delete_category();exit;}

if(isset($_POST["category_text"])){add_category_save();exit;}

function js(){
	$page=CurrentPageName();
	echo "AnimateDiv('BodyContent');LoadAjax('BodyContent','$page?tabs=yes');";
	
}

function del_category_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$delete_personal_cat_ask=$tpl->javascript_parse_text("{delete_personal_cat_ask}");
	$t=time();
$html="

var X_DeletePersonalCat$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);return;};
	$('#PERSONAL_CATEGORIES_TABLE').flexReload();
	YahooWin5Hide();
}

function DeletePersonalCat$t(){
	if(!confirm('$delete_personal_cat_ask')){return;}
	var XHR = new XHRConnection();
	XHR.appendData('delete-personal-cat','{$_GET["category-delete-js"]}');
	XHR.sendAndLoad('$page', 'POST',X_DeletePersonalCat$t);
}

DeletePersonalCat$t();";
	echo $html;
}


function add_category_js(){
	header("content-type: application/x-javascript");
	$tpl=new templates();
	$page=CurrentPageName();
	$widownsize=995;
	$t=$_GET["t"];
	$title=$tpl->_ENGINE_parse_body("{your_categories}::{new_category}");
	$html="YahooWin5('$widownsize','$page?add-perso-cat-tabs=yes&cat={$_GET["cat"]}&t=$t','$title');";
	echo $html;
}

function add_category_tabs(){
	$tpl=new templates();
	$page=CurrentPageName();


	$catname=trim($_GET["cat"]);
	$catname_enc=urlencode($catname);

	if($_GET["cat"]==null){
		$catname="{new_category}";
	}

	$array["add-perso-cat-popup"]=$catname;
	if($_GET["cat"]<>null){
		$array["manage"]='{websites}';
		$array["urls"]='{urls}';
		$array["security"]='{permissions}';
		
	}

	$fontsize=18;
	$catzenc=urlencode($_GET["cat"]);
	$t=$_GET["t"];
	while (list ($num, $ligne) = each ($array) ){

		if($num=="manage"){
			$html[]= $tpl->_ENGINE_parse_body("<li style='font-size:{$fontsize}px'><a href=\"squid.categories.php?popup=yes&category=$catname_enc&tablesize=695&t=$t\" style='font-size:18px'><span>$ligne</span></a></li>\n");
			continue;
		}

		if($num=="urls"){
			$html[]= $tpl->_ENGINE_parse_body("<li style='font-size:{$fontsize}px'><a href=\"squid.categories.urls.php?popup=yes&category=$catname_enc&tablesize=695&t=$t\" style='font-size:18px'><span>$ligne</span></a></li>\n");
			continue;
		}

		if($num=="security"){
			$html[]= $tpl->_ENGINE_parse_body("<li style='font-size:{$fontsize}px'><a href=\"squid.categories.security.php?popup=yes&category=$catname_enc&tablesize=695&t=$t\" style='font-size:18px'><span>$ligne</span></a></li>\n");
			continue;
		}

		
		$html[]= $tpl->_ENGINE_parse_body("<li style='font-size:{$fontsize}px'><a href=\"$page?$num=$t&t=$t&cat=$catname_enc\" style='font-size:18px'><span>$ligne</span></a></li>\n");
	}



	echo build_artica_tabs($html, "main_zoom_catz");



}

function add_category_popup(){
	$tpl=new templates();
	$page=CurrentPageName();
	$dans=new dansguardian_rules();
	$time=time();
	$q=new mysql_squid_builder();
	$error_max_dbname=$tpl->javascript_parse_text("{error_max_database_name_no_more_than}");
	$error_category_textexpl=$tpl->javascript_parse_text("{error_category_textexpl}");
	$error_category_nomore5=$tpl->javascript_parse_text("{error_category_nomore5}");
	
	
	if(!$q->FIELD_EXISTS("personal_categories", "PublicMode")){
		$q->QUERY_SQL("ALTER TABLE `personal_categories`
				ADD `PublicMode` smallint( 1 ) NOT NULL ,
				ADD INDEX ( `PublicMode` )");
	}
	
	$t=$_GET["t"];
	if(!is_numeric($t)){$t=time();}
	$catenc=urlencode($_GET["cat"]);
	$actions=button("{compile2}", 
			"Loadjs('ufdbguard.compile.category.php?category=$catenc&t=$t')",26)."&nbsp;|&nbsp;".
			button("{delete}", "Loadjs('$page?category-delete-js=$catenc&t=$t')",26)."&nbsp;|&nbsp;";

	if($_GET["cat"]==null){$actions=null;}

	if($_GET["cat"]<>null){
		
		$sql="SELECT * FROM personal_categories WHERE category='{$_GET["cat"]}'";
		$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	$titleBT="{apply}";
	}else{
	$action=null;
	$titleBT='{add}';
	}
	
	
	
	$proto="http";
	if($_SERVER["HTTPS"]=="on"){$proto="https";}
	$uri="$proto://{$_SERVER["SERVER_ADDR"]}:{$_SERVER["SERVER_PORT"]}/categories";

	$catz_allow_in_public_mode=$tpl->_ENGINE_parse_body("{catz_allow_in_public_mode}");
	$catz_allow_in_public_mode=str_replace("%URI", $uri, $catz_allow_in_public_mode);
	$PublicMode=Paragraphe_switch_img("{allow_in_public_mode}", 
			"$catz_allow_in_public_mode","PublicMode-$time",$ligne["PublicMode"],null,750);
	
	
	$groups=$dans->LoadBlackListesGroups();
	$groups[null]="{select}";
	$field=Field_array_Hash($groups, "CatzByGroupL",null,null,null,0,"font-size:22px");

	$blacklists=$dans->array_blacksites;
	$description="<textarea name='category_text'
	id='category_text-$t' style='height:50px;overflow:auto;width:99%;font-size:22px !important'>"
	.$ligne["category_description"]."</textarea>";

	if(isset($blacklists[$_GET["cat"]])){
		$description="<input type='hidden' id='category_text-$t' value=''>
		<div class=explain style='font-size:13px'>{$blacklists[$_GET["cat"]]}</div>";
	}
	
	$html="
	<div id='perso-cat-form'></div>
	<div style='width:98%' class=form>
			<table style='width:100%'>
			<tbody>
			<tr>
				<td class=legend style='font-size:22px'>{category}:</td>
				<td>". Field_text("category-to-add-$t","{$_GET["cat"]}","font-size:22px;padding:3px;width:99%;font-weight:bold")."</td>
			</tr>
			<tr>
				<td class=legend style='font-size:22px'>{description}:</td>
				<td>$description</td>
			</tr>
			<tr>
				<td class=legend style='font-size:22px'>{group}:</td>
				<td>$field</td>
			</tr>
			<tr>
				<td class=legend style='font-size:22px'>{group} ({add}):</td>
				<td>". Field_text("CatzByGroupA",null,"font-size:22px;padding:3px;width:320px")."</td>
			</tr>
			<tr>
				<td colspan=2>$PublicMode</td>
			</tr>
			<tr>
				<td colspan=2 align='right' style='font-size:22px'><hr>$actions". button($titleBT,"SavePersonalCategory$t()",26)."</td>
			</tr>
			</tbody>
			</table>
			</div>
		

		<script>
var X_SavePersonalCategory= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);return;};
	$('#PERSONAL_CATEGORIES_TABLE').flexReload();
	YahooWin5Hide();

}

function SavePersonalCategory$t(){
	var XHR = new XHRConnection();
	var db=document.getElementById('category-to-add-$t').value;
	var expl=document.getElementById('category_text-$t').value;
	if(db.length<5){alert('$error_category_nomore5');return;}
	if(expl.length<5){alert('$error_category_textexpl');return;}
	if(db.length>15){alert('$error_max_dbname: 15');return;}
	XHR.appendData('personal_database',db);
	var pp=encodeURIComponent(document.getElementById('category_text-$t').value);
	XHR.appendData('category_text',pp);
	XHR.appendData('CatzByGroupA',document.getElementById('CatzByGroupA').value);
	XHR.appendData('CatzByGroupL',document.getElementById('CatzByGroupL').value);
	XHR.appendData('PublicMode',document.getElementById('PublicMode-$time').value);
	
	
	
	XHR.sendAndLoad('$page', 'POST',X_SavePersonalCategory);
}

var X_DeletePersonalCat$t= function (obj) {
	var results=obj.responseText;
	if(results.length>3){alert(results);return;};
	$('#PERSONAL_CATEGORIES_TABLE').flexReload();
	YahooWin5Hide();
}

var X_CompilePersonalCat$t= function (obj) {
	var results=obj.responseText;
	document.getElementById('perso-cat-form').innerHTML='';
	if(results.length>3){alert(results);return;};
	$('#PERSONAL_CATEGORIES_TABLE').flexReload();
}


function DeletePersonalCat$t(){
	if(confirm('$delete_personal_cat_ask')){
		var XHR = new XHRConnection();
		XHR.appendData('delete-personal-cat','{$_GET["cat"]}');
		AnimateDiv('perso-cat-form');
		XHR.sendAndLoad('$page', 'POST',X_DeletePersonalCat$t);
	}

}

function checkform$t(){
	var cat='{$_GET["cat"]}';
	if(cat.length>0){document.getElementById('category-to-add-$t').disabled=true;}
}
checkform$t();
</script>

";
echo $tpl->_ENGINE_parse_body($html);


}

function tabs(){
	if(GET_CACHED(__FILE__, __FUNCTION__)){return;}
	$squid=new squidbee();
	$tpl=new templates();
	$users=new usersMenus();
	$page=CurrentPageName();
	$sock=new sockets();

	$array["table"]="{your_categories}";

	$fontsize=18;
	
	$t=time();
	while (list ($num, $ligne) = each ($array) ){

		
		if($num=="table"){
			$html[]= $tpl->_ENGINE_parse_body("<li style='font-size:{$fontsize}px'>
			<a href=\"$page?categories=yes\" style='font-size:$fontsize'><span>$ligne</span></a></li>\n");
			continue;
				
		}


		$html[]= $tpl->_ENGINE_parse_body("<li><a href=\"$page?$num=$t\" style='font-size:$fontsize'><span>$ligne</span></a></li>\n");
	}



	$html= build_artica_tabs($html,'main_perso_categories',1024);
	SET_CACHED(__FILE__, __FUNCTION__, null, $html);
	echo $html;

}

function categories(){
	$page=CurrentPageName();
	$tpl=new templates();
	$users=new usersMenus();
	$q=new mysql_squid_builder();
	if(!$q->TABLE_EXISTS("webfilters_categories_caches")){$q->CheckTables();}else{
		$q->QUERY_SQL("TRUNCATE TABLE webfilters_categories_caches");
	}

	$q->QUERY_SQL("DELETE FROM personal_categories WHERE category='';");
	$OnlyPersonal=null;
	$dans=new dansguardian_rules();
	$dans->LoadBlackListes();

	
	

	$purge_catagories_database_explain=$tpl->javascript_parse_text("{purge_catagories_database_explain}");
	$purge_catagories_table_explain=$tpl->javascript_parse_text("{purge_catagories_table_explain}");
	$items=$tpl->_ENGINE_parse_body("{items}");
	$size=$tpl->_ENGINE_parse_body("{size}");
	$SaveToDisk=$tpl->_ENGINE_parse_body("{SaveToDisk}");
	$addCat=$tpl->_ENGINE_parse_body("{new_category}");
	$purge=$tpl->_ENGINE_parse_body("{purgeAll}");
	$category=$tpl->_ENGINE_parse_body("{category}");
	$tablewith=691;
	$compilesize=35;
	$size_elemnts=50;
	$size_size=58;
	$title=$tpl->javascript_parse_text("{your_categories}");
	$deletetext=$tpl->javascript_parse_text("{purge}");
	$test_categories=$tpl->javascript_parse_text("{test_categories}");
	$delete="{display: '<strong style=font-size:18px>$deletetext</strong>', name : 'icon3', width : 90, sortable : false, align: 'center'},";
	$add=$tpl->javascript_parse_text("{add}");
	$WEBFILTERING_TOP_MENU=WEBFILTERING_TOP_MENU();
	if(!$users->CORP_LICENSE){
		$error="
				
				<p class=text-error style='font-size:18px'>" .$tpl->_ENGINE_parse_body("{this_feature_is_disabled_corp_license}")."</p>";
		
	}
	
	
	$t=time();
	$html="<div style='font-size:30px;margin-bottom:20px'>$WEBFILTERING_TOP_MENU</div>
			$error
<table class='PERSONAL_CATEGORIES_TABLE' style='display: none' id='PERSONAL_CATEGORIES_TABLE' style='width:99%'></table>
<script>
$(document).ready(function(){
	$('#PERSONAL_CATEGORIES_TABLE').flexigrid({
	url: '$page?category-search=yes',
	dataType: 'json',
	colModel : [
	{display: '<strong style=font-size:18px>$category</strong>', name : 'category', width : 825, sortable : false, align: 'left'},
	{display: '<strong style=font-size:18px>$add</strong>', name : 'add', width : 121, sortable : false, align: 'center'},
	{display: '<strong style=font-size:18px>$size</strong>', name : 'category', width : 121, sortable : false, align: 'right'},
	{display: '<strong style=font-size:18px>$items</strong>', name : 'TABLE_ROWS', width : 121, sortable : true, align: 'right'},
	{display: '<strong style=font-size:18px>compile</strong>', name : 'icon2', width : 121, sortable : false, align: 'center'},
	$delete

	],
	buttons : [
		{name: '<strong style=font-size:18px>$addCat</strong>', bclass: 'add', onpress : AddNewCategory},
		{name: '<strong style=font-size:18px>$size</strong>', bclass: 'Search', onpress : LoadCategoriesSize},
		{name: '<strong style=font-size:18px>$test_categories</strong>', bclass: 'Search', onpress : LoadTestCategories},
		
		
		
	],
	searchitems : [
	{display: '$category', name : 'category'},
	],
	sortname: 'table_name',
	sortorder: 'asc',
	usepager: true,
	title: '<span style=font-size:30px>$title</span>',
	useRp: true,
	rpOptions: [10, 20, 30, 50,100,200],
	rp:50,
	showTableToggleBtn: false,
	width: '99%',
	height: 550,
	singleSelect: true

});
});


function AddNewCategory(){
	Loadjs('$page?add-perso-cat-js=yes&t=$t');
}

function SwitchToArtica(){
$('#dansguardian2-category-$t').flexOptions({url: '$page?category-search=yes&minisize={$_GET["minisize"]}&t=$t&artica=1'}).flexReload();
}

function SaveAllToDisk(){
Loadjs('$page?compile-all-dbs-js=yes')

}

function LoadTestCategories(){
	Loadjs('squid.category.tests.php');
}

function LoadCategoriesSize(){
	Loadjs('dansguardian2.compilesize.php')
}

function CategoryDansSearchCheck(e){
if(checkEnter(e)){CategoryDansSearch();}
}

function CategoryDansSearch(){
var se=escape(document.getElementById('category-dnas-search').value);
LoadAjax('dansguardian2-category-list','$page?category-search='+se,false);

}

function DansGuardianCompileDB(category){
Loadjs('ufdbguard.compile.category.php?category='+category);
}

function CheckStatsApplianceC(){
LoadAjax('CheckStatsAppliance','$page?CheckStatsAppliance=yes',false);
}

var X_PurgeCategoriesDatabase= function (obj) {
var results=obj.responseText;
if(results.length>2){alert(results);}
RefreshAllTabs();
}

function PurgeCategoriesDatabase(){
if(confirm('$purge_catagories_database_explain')){
var XHR = new XHRConnection();
XHR.appendData('PurgeCategoriesDatabase','yes');
AnimateDiv('dansguardian2-category-list');
XHR.sendAndLoad('$page', 'POST',X_PurgeCategoriesDatabase);
}

}

var X_TableCategoryPurge= function (obj) {
var results=obj.responseText;
if(results.length>2){alert(results);}
$('#dansguardian2-category-$t').flexReload();
}

function TableCategoryPurge(tablename){
if(confirm('$purge_catagories_table_explain')){
var XHR = new XHRConnection();
XHR.appendData('PurgeCategoryTable',tablename);
XHR.sendAndLoad('dansguardian2.databases.compiled.php', 'POST',X_TableCategoryPurge);
}
}


CheckStatsApplianceC();
</script>

";

echo $tpl->_ENGINE_parse_body($html);


}

function categories_search(){
	$MyPage=CurrentPageName();
	$page=CurrentPageName();
	$tpl=new templates();
	$sock=new sockets();
	$q=new mysql_squid_builder();
	$dans=new dansguardian_rules();
	$EnableWebProxyStatsAppliance=$sock->GET_INFO("EnableWebProxyStatsAppliance");
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
	$t=$_GET["t"];
	$OnlyPersonal=0;
	$error_license=null;
	$users=new usersMenus();
	if(!$users->CORP_LICENSE){
		$error_license="<br><i style='font-size:16px;font-weight:bold'>".$tpl->_ENGINE_parse_body("{perso_category_no_license}")."</i>";
	}
	
	if(isset($_GET["OnlyPersonal"])){$OnlyPersonal=1;}
	$rp=200;
	if(isset($_GET["artica"])){$artica=true;}
	if($_POST["sortname"]=="table_name"){$_POST["sortname"]="categorykey";}
	if(!$q->BD_CONNECT()){json_error_show("Testing connection to MySQL server failed...",1);}
	
	$sql="SELECT * FROM personal_categories";
	$table="personal_categories";
	if($_POST["sortname"]=="categorykey"){$_POST["sortname"]="category";}
	$prefix="INSERT IGNORE INTO webfilters_categories_caches (`categorykey`,`description`,`picture`,`master_category`,`categoryname`) VALUES ";
	$searchstring=string_to_flexquery();
	$page=1;
	
	
	if(isset($_POST["sortname"])){
		if($_POST["sortname"]<>null){
			$ORDER="ORDER BY {$_POST["sortname"]} {$_POST["sortorder"]}";
		}
	}
	
	
	if (isset($_POST['page'])) {$page = $_POST['page'];}
	
	
		if($searchstring<>null){
			$sql="SELECT COUNT( * ) AS tcount FROM $table WHERE 1 $searchstring";
			writelogs($sql,__FUNCTION__,__FILE__,__LINE__);
			$ligne=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
			if(!$q->ok){json_error_show("Mysql Error [".__LINE__."]: $q->mysql_error.<br>$sql",1);}
			$total = $ligne["tcount"];
	
		}else{
			$total = $q->COUNT_ROWS($table);
		}
	
		if (isset($_POST['rp'])) {$rp = $_POST['rp'];}
	
	
	
		$pageStart = ($page-1)*$rp;
		$limitSql = "LIMIT $pageStart, $rp";
	
		
	
		$sql="SELECT * FROM $table WHERE 1 $searchstring $ORDER $limitSql ";
	
		writelogs("$q->mysql_admin:$q->mysql_password:$sql",__FUNCTION__,__FILE__,__LINE__);
		$results = $q->QUERY_SQL($sql);
	
		if(!$q->ok){if($q->mysql_error<>null){json_error_show(date("H:i:s")."<br>SORT:{$_POST["sortname"]}:<br>Mysql Error [L.".__LINE__."]: $q->mysql_error<br>$sql",1);}}
	
	
		if(mysql_num_rows($results)==0){json_error_show("Not found...",1);}
	
	
		$data = array();
		$data['page'] = $page;
		$data['total'] = $total;
		$data['rows'] = array();
	
	
		$enc=new mysql_catz();
	
		$field="category";
		$field_description="category_description";

	
		$CATZ_ARRAY=unserialize(@file_get_contents("/home/artica/categories_databases/CATZ_ARRAY"));
	
		$TransArray=$enc->TransArray();
		while (list ($tablename, $items) = each ($CATZ_ARRAY) ){
			if(!isset($TransArray[$tablename])){continue;}
			$CATZ_ARRAY2[$TransArray[$tablename]]=$items;
		}
	
		while ($ligne = mysql_fetch_assoc($results)) {
			$color="black";
			$categorykey=$ligne["category"];
			$Meta=intval($ligne["Meta"]);
			if($categorykey==null){$categorykey="UnkNown";}
			//Array ( [category] => [category_description] => Ma catégorie [master_category] => [sended] => 1 )
			if($GLOBALS["VERBOSE"]){echo "Found  $field:{$categorykey}<br>\n";}
			$categoryname=$categorykey;
			$text_category=null;
	
			$table=$q->cat_totablename($categorykey);
			if($GLOBALS["VERBOSE"]){echo "Scanning table $table<br>\n";}
			
	
			$itemsEncTxt=null;
			$items=$q->COUNT_ROWS($table);
			
	
			if(!preg_match("#^category_(.+)#", $table,$re)){continue;}
			$compile=imgsimple("compile-distri-32.png","{saveToDisk}","DansGuardianCompileDB('$categoryname')");
	
			if($dans->array_pics[$categoryname]<>null){
				$pic="<img src='img/{$dans->array_pics[$categoryname]}'>";}else{$pic="&nbsp;";}
	
			$sizedb_org=$q->TABLE_SIZE($table);
			$sizedb=FormatBytes($sizedb_org/1024);
	
	
			$linkcat="<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('squid.categories.php?category={$categoryname}&t=$t',true)\"
			style='font-size:20px;font-weight:bold;color:$color;text-decoration:underline'>";
	
			$text_category=$tpl->_ENGINE_parse_body(utf8_decode($ligne[$field_description]));
			$text_category=trim($text_category);
	
			$linkcat="<a href=\"javascript:blur();\" OnClick=\"javascript:Loadjs('$MyPage?add-perso-cat-js=yes&cat=$categoryname&t=$t',true)\"
			style='font-size:20px;font-weight:bold;color:$color;text-decoration:underline'>";

	
			
			$viewDB=imgsimple("mysql-browse-database-32.png",null,"javascript:Loadjs('squid.categories.php?category={$categoryname}',true)");
	
	
			$text_category=utf8_encode($text_category);
			$categoryname_text=utf8_encode($categoryname);
			$categoryText=$tpl->_ENGINE_parse_body("<span style='font-size:20px';font-weight:bold'>$categoryname_text</span>
					</a><br><span style='font-size:18px;width:100%;font-weight:normal'><i>{$text_category}</i></span>$error_license");
			
			$itemsEncTxt="<span style='font-size:18px;font-weight:bold'>".numberFormat($items,0,""," ");"</span>";
		
	
			$compile=imgsimple("compile-distri-48.png",null,"DansGuardianCompileDB('$categoryname')");
			$delete=imgsimple("dustbin-48.png",null,"TableCategoryPurge('$table')");
			$add=imgsimple("add-48.png",null,"Loadjs('squid.visited.php?add-www=yes&category=$categorykey&t=$t');");
			
	
			if($categoryname=="UnkNown"){
				$linkcat=null;
				$delete=imgsimple("delete-48.png",null,"TableCategoryPurge('')");
			}
			
			
			if($Meta==1){
				$linkcat="<strong>";
				$compile=null;
				$sizedb="-";
				$delete="&nbsp;";
				$sql="SELECT COUNT( * ) AS tcount FROM webfiltering_meta_items WHERE category='$categorykey'";
				$ligne2=mysql_fetch_array($q->QUERY_SQL($sql,"artica_backup"));
				$items=$ligne2["tcount"];
				$itemsEncTxt="<span style='font-size:18px;font-weight:bold'>".numberFormat($items,0,""," ");"</span>";
			}
	
		$cell=array();
		$cell[]=$linkcat.$categoryText;
		$cell[]="<center>$add</center>";
		$cell[]="<span style='font-size:18px;padding-top:15px;font-weight:bold'>$sizedb</div>";
		$cell[]=$itemsEncTxt;
		$cell[]="<center>$compile</center>";
		$cell[]="<center>$delete</center>";
	
		$data['rows'][] = array(
				'id' => $ligne['ID'],
				'cell' => $cell
		);
		}
	
	
		echo json_encode($data);
	
}

function add_category_save(){
	$_POST["personal_database"]=url_decode_special_tool($_POST["personal_database"]);
	$_POST["category_text"]=url_decode_special_tool($_POST["category_text"]);
	$org=$_POST["personal_database"];


	include_once(dirname(__FILE__)."/ressources/class.html.tools.inc");
	$html=new htmltools_inc();
	$dans=new dansguardian_rules();

	$_POST["personal_database"]=strtolower($html->StripSpecialsChars($_POST["personal_database"]));

	if($_POST["personal_database"]==null){
		echo "No category set or wrong category name \"$org\"\n";
		return;
	}

	if($_POST["personal_database"]=="security"){$_POST["personal_database"]="security2";}
	if($_POST["CatzByGroupA"]<>null){$_POST["CatzByGroupL"]=$_POST["CatzByGroupA"];}

	$_POST["CatzByGroupL"]=mysql_escape_string2($_POST["CatzByGroupL"]);

	$_POST["category_text"]=url_decode_special_tool($_POST["category_text"]);
	$_POST["category_text"]=mysql_escape_string2($_POST["category_text"]);
	$q=new mysql_squid_builder();
	$sql="SELECT category FROM personal_categories WHERE category='{$_POST["personal_database"]}'";
	$ligne=mysql_fetch_array($q->QUERY_SQL($sql));
	if($ligne["category"]<>null){
		$sql="UPDATE personal_categories
		SET category_description='{$_POST["category_text"]}',
		`PublicMode`='{$_POST["PublicMode"]}',
		master_category='{$_POST["CatzByGroupL"]}'
		WHERE category='{$_POST["personal_database"]}'
		";
	}else{

		if(isset($dans->array_blacksites[$_POST["personal_database"]])){
				$tpl=new templates();
				echo $tpl->javascript_parse_text("{$_POST["personal_database"]}:{category_already_exists}");
				return;
		}

		$sql="INSERT IGNORE INTO personal_categories (category,category_description,master_category,PublicMode)
		VALUES ('{$_POST["personal_database"]}','{$_POST["category_text"]}','{$_POST["CatzByGroupL"]}','{$_POST["PublicMode"]}');";
		}




	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}
	$q->CreateCategoryTable($_POST["personal_database"]);
	$sql="TRUNCATE TABLE webfilters_categories_caches";
	$dans->CategoriesTableCache();
	$dans->CleanCategoryCaches();
	
}
function delete_category(){

	$category=trim($_POST["delete-personal-cat"]);
	if(strlen($category)==0){return;}
	$q=new mysql_squid_builder();
	if(!$q->DELETE_CATEGORY($category)){return;}
	
	
}