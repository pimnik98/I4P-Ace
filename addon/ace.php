#!/usr/bin/php
<?php
require_once('/usr/local/ispmgr/lib/php/isp4private/core.php');
$isp = new PiminoffISP("i4p_ace",true,3);
# Установка значений по умолчанию
$isp->config->def("Theme","eclipse","Default");
$isp->config->def("Height","auto","Default");

$isp->config->def("php","php","Format");
$isp->config->def("html","html","Format");
$isp->config->def("css","css","Format");
$isp->config->def("ini","ini","Format");
$isp->config->def("cpp","c_cpp","Format");
$isp->config->def("bat","batchfile","Format");
$isp->config->def("sh","sh","Format");
$isp->config->def("js","javascript","Format");
$isp->config->def("xml","xml","Format");
$isp->config->def("txt","text","Format");
$isp->config->save(true);

# генерируем xml-документ
function readyAce($html,$path=""){
	return '<?xml version="1.0" encoding="UTF-8"?><doc user="root" level="7"><metadata name="file.ace" type="form"><form maxwidth="yes" event="ace_save" helpurl="http://isp4private.ru/"><field name="gdata"><textarea zoom="0" name="gdata"/></field><field name="path"><textarea name="path" zoom="0" readonly="1"/></field><field name="v" fullwidth="yes"><htmldata name="v"/></field></form></metadata><params><func>file.ace</func></params><v>'.$html.'</v><elid>'.$path.'</elid><path>'.$path.'</path></doc>';
}

# Получение формата файла
function getFormat($path){
	global $isp;
	$path_parts = pathinfo($path);
	return isset($path_parts['extension']) && $isp->config->get($path_parts['extension'],"Format")?$isp->config->get($path_parts['extension'],"Format"):"text";
}

# Генерируем HTML-документ редактора
function preAce($contents="Test",$ffa="text"){
    global $isp;
    $contents = htmlspecialchars(
    '<div id="tcont" style="padding:0px; margin-top:1px; overflow-y:auto; overflow-x:auto; min-height:'.($isp->config->get("Height","Default") == "auto"?"256px":$isp->config->get("Height","Default")).'; width:100%"><pre id="editor" style="position: absolute;top: 0;bottom: 0;left: 0;right: 0;">'. htmlspecialchars($contents) .'</pre></div>'.
    '<script>
    let script = document.createElement("script");script.src = "/manimg/i4p/aceeditor/piminoff/ace.js";document.head.append(script);
    script.onload = function() {var editor = ace.edit("editor");editor.setTheme("ace/theme/'.$isp->config->get("Theme","Default").'");editor.session.setMode("ace/mode/'.$ffa.'");document.getElementById("buttonsDiv").style.bottom = 14;document.getElementById("buttonsDiv").style.margin = 0;document.getElementById("buttonsDiv").style.position = "absolute";document.getElementById("buttonsDiv").style.right = 14;document.getElementById("buttonsDiv").style.minHeight = 32;};
    function frm_event(){document.getElementsByName("gdata")[0].value = ace.edit("editor").getValue();return true;}
    </script><style>.ace_scrollbar-h {right: inherit!important;left: 0!important;}</style>');
    return $contents;
}

# Загружаем файл
function getContent($path){
	$data = "";
	if (!is_file($path)) return "Файл не существует, или не является таковым.";
	if (!is_readable($path)) return "Ошибка прав чтения файла, проверьте права доступа";
	if (!is_writable($path)) return "Ошибка прав записи файла, проверьте права доступа";
	$data = @file_get_contents($path);
	return $data;
}

# Получение пути пользователя
function getPath($user,$level){
    $userdir = '';
	if ($level < 7){
		$passwd = explode("\n", @file_get_contents('/etc/passwd'));
		foreach($passwd as $pass){
			if (strpos($pass, $user . ':') === 0){
				$user_info = explode(':', $pass);
				$userdir = $user_info[5];
				break;
			}
	    }
	}
    return $userdir."/";
}

$p = $isp->getParams();
$form = new XMLData();
$form->addNode($form->root,"metadata");
switch($isp->func){
	case "i4p.ace":
	case "file.ace":{
	    if (isset($p["sok"]) && $p["sok"] == "ok" && isset($p["path"])){
	        $path = getPath($isp->env->login,$isp->env->access).$p["path"];
	        $isp->logs->WriteLog(3,"WRITING TO ".$path);
	        file_put_contents($path, $p["gdata"]);
	        echo readyAce(preAce(getContent($path),getFormat($path)),$p["path"]);
    		die();
	    } else {
    		$path = getPath($isp->env->login,$isp->env->access).(!$isp->plid?"":$isp->plid."/").((!$isp->elid?"":$isp->elid));
    		echo readyAce(preAce(getContent($path),getFormat($path)),(!$isp->plid?"":$isp->plid."/").((!$isp->elid?"":$isp->elid)));
    		die();
	    }
		break;
	}
	default:{
		$isp->err->InternalError("The function is not detected or has not yet been implemented in this version of the plugin.");
	}
}
$form->PrintXML();
