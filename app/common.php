<?php
use think\Response;
use think\facade\Session;
use think\facade\Cookie;
use think\facade\Config;
use app\common\model\Db;
use app\common\model\Enewsmember as User;
use app\common\model\Enews;
use think\exception\HttpResponseException;

define('NOW_TIME',time());
require 'resfun.php';
require 'ecmsfun.php';


function elang($key=''){
	include_once(ECMS_PATH.'e/data/language/gb/pub/q_message.php');
	return $qmessage_r[$key];
}


function db_str2arr($data,$split='|'){
	$data=explode($split,$data);
	$data2=array();
	for($i=0;$i<count($data);$i++){
		$datai=intval($data[$i]);
		if($datai)$data2[]=$datai;
	}
	return $data2;
}

function db_arr2str($data,$split='|'){
	$str=$split.implode($split,$data).$split;
	return $str;
}

function db_add1arr($data,$one,$split='|'){
	if(is_string($data)){
		$data=db_str2arr($data,$split);
	}
	if(!in_array($one,$data))$data[]=$one;
	return $data;
}

function db_add1str($data,$one,$split='|'){
	return db_arr2str(db_add1arr($data,$one,$split),$split);
}

function db_del1arr($data,$one,$split='|'){
	if(is_string($data)){
		$data=db_str2arr($data,$split);
	}
	for($i=0;$i<count($data);$i++){
		if($data[$i]==$one){unset($data[$i]);}
	}
	return $data;
}

function db_del1str($data,$one,$split='|'){
	return db_arr2str(db_del1arr($data,$one,$split),$split);
}


if(!function_exists('tp_renameuser')){
function tp_renameuser($uid,$usernamenew){
	global $empire,$dbtbpre;

	$uid=(int)$uid;
	$usernameold=DB::getValue("select username as total from {$dbtbpre}enewsmember where userid=$uid");

	if($uid and $usernameold and $usernamenew){
		//会员表
		$sql=DB::execute("update {$dbtbpre}enewsmember set username='$usernamenew' where userid='$uid'");
		//短信息
		$sql=DB::execute("update {$dbtbpre}enewsqmsg set to_username='$usernamenew' where to_username='$usernameold'");
		$sql=DB::execute("update {$dbtbpre}enewsqmsg set from_username='$usernamenew' where from_username='$usernameold'");
		//收藏
		$sql=DB::execute("update {$dbtbpre}enewsfava set username='$usernamenew' where userid='$uid'");
		//购买记录
		$sql=DB::execute("update {$dbtbpre}enewsbuybak set username='$usernamenew' where userid='$uid'");
		//下载记录
		$sql=DB::execute("update {$dbtbpre}enewsdownrecord set username='$usernamenew' where userid='$uid'");
		//信息表
		$tbnames=DB::getCol("select tbname from {$dbtbpre}enewstable");
		foreach($tbnames as $tbname){
			$usql=DB::execute("update {$dbtbpre}ecms_".$tbname." set username='$usernamenew' where userid='$uid' and ismember=1");
			$usql=DB::execute("update {$dbtbpre}ecms_".$tbname."_check set username='$usernamenew' where userid='$uid' and ismember=1");
			$usql=DB::execute("update {$dbtbpre}ecms_".$tbname."_doc set username='$usernamenew' where userid='$uid' and ismember=1");
		}
	}
}
}
if(!function_exists('isemail')){
	function isemail($v){
		return preg_match("/^([a-zA-Z0-9_-])+@([a-zA-Z0-9_-])+(\.[a-zA-Z0-9]{2,10}){1,2}$/",$v);
	}
}

if(!function_exists('ismobile')){
	function ismobile($v){
		return preg_match("/^1[3|4|5|6|7|8|9]{1}[\d]{9}$/",$v);
	}
}

function tp_getwh($a,$b){
	$a=explode(",",$a);
	$b=explode(",",$b);
	$wc=$b[0]/$a[0];
	$hc=$b[1]/$a[1];
	if($wc>$hc){
		$c=$wc;
	}else{
		$c=$hc;
	}
	if($c<1){
		$c=1;
	}
	return array('w'=>round($b[0]/$c),'h'=>round($b[1]/$c));
}

function tp_getjpegsize($img_loc) { 
    $handle = fopen($img_loc, "rb") or die("Invalid file stream."); 
    $new_block = NULL; 
    if(!feof($handle)) { 
        $new_block = fread($handle, 32); 
        $i = 0; 
        if($new_block[$i]=="xFF" && $new_block[$i+1]=="xD8" && $new_block[$i+2]=="xFF" && $new_block[$i+3]=="xE0") { 
            $i += 4; 
            if($new_block[$i+2]=="x4A" && $new_block[$i+3]=="x46" && $new_block[$i+4]=="x49" && $new_block[$i+5]=="x46" && $new_block[$i+6]=="x00") { 
                 
// Read block size and skip ahead to begin cycling through blocks in search of SOF marker 
 
                $block_size = unpack("H*", $new_block[$i] . $new_block[$i+1]); 
                $block_size = hexdec($block_size[1]); 
                while(!feof($handle)) { 
                    $i += $block_size; 
                    $new_block .= fread($handle, $block_size); 
                    if($new_block[$i]=="xFF") { 
                         
// New block detected, check for SOF marker 
 
                        $sof_marker = array("xC0", "xC1", "xC2", "xC3", "xC5", "xC6", "xC7", "xC8", "xC9", "xCA", "xCB", "xCD", "xCE", "xCF"); 
                        if(in_array($new_block[$i+1], $sof_marker)) { 
                             
// SOF marker detected. Width and height information is contained in bytes 4-7 after this byte. 
 
                            $size_data = $new_block[$i+2] . $new_block[$i+3] . $new_block[$i+4] . $new_block[$i+5] . $new_block[$i+6] . $new_block[$i+7] . $new_block[$i+8]; 
                            $unpacked = unpack("H*", $size_data); 
                            $unpacked = $unpacked[1]; 
                            $height = hexdec($unpacked[6] . $unpacked[7] . $unpacked[8] . $unpacked[9]); 
                            $width = hexdec($unpacked[10] . $unpacked[11] . $unpacked[12] . $unpacked[13]); 
                            return array($width, $height); 
                        } else { 
                             
// Skip block marker and read block size 
 
                            $i += 2; 
                            $block_size = unpack("H*", $new_block[$i] . $new_block[$i+1]); 
                            $block_size = hexdec($block_size[1]); 
                        } 
                    } else { 
                        return FALSE; 
                    } 
                } 
            } 
        } 
    } 
    return FALSE; 
} 
function userdiqustr($user){
	global $dbtbpre;
	if(is_numeric($user))$user=User::userinfo($user);
	$str="";
	if($user['sheng'])$str.=DB::getValue("select name from {$dbtbpre}hd_diqu where id=$user[sheng]");
	if($user['shi'])$str.=" ".DB::getValue("select name from {$dbtbpre}hd_diqu where id=$user[shi]");
	if($user['quxian'])$str.=" ".DB::getValue("select name from {$dbtbpre}hd_diqu where id=$user[quxian]");
	return $str;
}

function shaixuan_url2($name,$value="",$get=""){
	global $requestnames;
	if(empty($get)){
		$get=$_GET;
	}else{
		parse_str(substr($get,1),$get);
	}
	if($name=="brandid")unset($get[modelid]);
	if($name=="modelid")unset($get[brandid]);
	unset($get[page]);
//	foreach($get as $k=>$v){
//		if(!in_array($k,$requestnames) or empty($v))unset($get[$k]);
//	}
	if(empty($value)){
		unset($get[$name]);
	}else{
		$get[$name]=$value;
	}
	if($get[page]==1)unset($get[page]);
	uksort($get,"key_order2");
	return count($get)>0?"?".http_build_query($get):parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH);
}

function key_order2($a,$b){
	global $requestnames;
	if(array_keys($requestnames,$a)==array_keys($requestnames,$b)){
		return 0;
	}
	return (array_keys($requestnames,$a)>array_keys($requestnames,$b))?1:-1;
}

function restore(){
	if(request()->app()=='android'){
		return '?s=/android/admin/restore';
	}elseif(request()->app()=='admin'){
		return '/e/paiadmin/index.php?s=/base/restore&tag=admin';
	}else{
		return '/base/restore';
	}
}
function zixun_class_where(){
	global $class_r;
	$where='';
	foreach($class_r as $k=>$v){
		if($v['tbname']=='news' && $v['bclassid']!=18 && $v['islast']){
			$where.=($where?' or ':'')." classid={$k} ";
		}
	}
	return ' ('.$where.') ';
}

function gaizhuang_class_where(){
	global $class_r;
	$where='';
	foreach($class_r as $k=>$v){
		if($v['tbname']=='news' && $v['bclassid']==18 && $v['islast']){
			$where.=($where?' or ':'')." classid={$k} ";
		}
	}
	return ' ('.$where.') ';
}

function tp_str2arr($str, $glue = ','){
	return explode($glue, $str);
}
function tp_arr2str($arr, $glue = ','){
	return implode($glue, $arr);
}

function src_http($content) {
	$content=str_ireplace(
		['src="/d',"src='/d",'src=/d'],
		['src="http://www.domain.com/d',"src='http://www.domain.com/d",'src=http://www.domain.com/d'],
		$content);
	return $content;
}
function tp_http( $url, $post = null, $flag = 0, $gzip=false) {
	$ch = curl_init();
	if ( !$flag )curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, FALSE );
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, TRUE );
	if ( !empty( $post ) ) {
		curl_setopt( $ch, CURLOPT_POST, TRUE );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $post );
	}
	
	curl_setopt( $ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; Baiduspider/2.0;+http://www.baidu.com/search/spider.html)'/*$_SERVER['HTTP_USER_AGENT']*/);
	
	if($gzip){
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt( $ch, CURLOPT_ENCODING, 'gzip');
		curl_setopt( $ch, CURLOPT_HTTPHEADER, array('Accept-Encoding: gzip, deflate'));
	}
	
	curl_setopt( $ch, CURLOPT_URL, $url );
	$ret = curl_exec( $ch );
	curl_close( $ch );
	return $ret;
}

function tp_plbiaoqing($text){
	return preg_replace('/\[e(\d+)\]/is',"<img src='http://www.domain.com/emoticons/\$1.gif' />",$text);
}

function check_verify($code, $id = 1){
    $verify = new \Verify();
    return $verify->check($code, $id);
}
function tp_formattime($time){
	$time=(int)$time;
	$t=time()-$time;
	if($t>0){
		$f=array(
			'31536000'=>'年',
			'2592000'=>'个月',
			'604800'=>'星期',
			'86400'=>'天',
			'3600'=>'小时',
			'60'=>'分钟',
			'1'=>'秒'
		);
		if(/*0&&*/floor($t/86400)>1){
			return date("Y-m-d H:i:s",$time);
		}else{
			foreach($f as $k=>$v){
				if(0!=$c=floor($t/(int)$k)){
					return $c.$v.'前';
				}
			}
		}
	}else{
		return "刚刚";
	}
}

function tp_short_formattime($time){
	$time=(int)$time;
	$t=time()-$time;
	if($t>0){
		$f=array(
			'31536000'=>'年',
			'2592000'=>'个月',
			'604800'=>'星期',
			'86400'=>'天',
			'3600'=>'小时',
			'60'=>'分钟',
			'1'=>'秒'
		);
		if(floor($t/86400)>1){
			return date("Y-m-d",$time);
		}else{
			foreach($f as $k=>$v){
				if(0!=$c=floor($t/(int)$k)){
					return $c.$v.'前';
				}
			}
		}
	}else{
		return "刚刚";
	}
}


function tp_cura2($cur=""){
	$a2=input('get.a2');
	if(strpos($cur,"||")){
		$curarr=explode("||",$cur);
		if(in_array($a2,$curarr)){
			return ' class="cur"';
		}
	}elseif($cur==$a2){
		return ' class="cur"';
	}
	return "";
}
function tp_cura($cur=""){
	$a=request()->action();
	if(strpos($cur,"||")){
		$curarr=explode("||",$cur);
		if(in_array($a,$curarr)){
			return ' class="cur"';
		}
	}elseif($cur==$a){
		return ' class="cur"';
	}
	return "";
}

function tp_cn_num($num){
	$chiNum = array( '零', '一', '二', '三', '四', '五', '六', '七', '八', '九' );
	$chiUni = array( '', '十', '百', '千', '万', '亿', '十', '百', '千' );

	$chiStr = '';

	$num_str = ( string )$num;

	$count = strlen( $num_str );
	$last_flag = true; //上一个 是否为0
	$zero_flag = true; //是否第一个
	$temp_num = null; //临时数字

	$chiStr = ''; //拼接结果
	if ( $count == 2 ) { //两位数
		$temp_num = $num_str[ 0 ];
		$chiStr = $temp_num == 1 ? $chiUni[ 1 ] : $chiNum[ $temp_num ] . $chiUni[ 1 ];
		$temp_num = $num_str[ 1 ];
		$chiStr .= $temp_num == 0 ? '' : $chiNum[ $temp_num ];
	} else if ( $count > 2 ) {
		$index = 0;
		for ( $i = $count - 1; $i >= 0; $i-- ) {
			$temp_num = $num_str[ $i ];
			if ( $temp_num == 0 ) {
				if ( !$zero_flag && !$last_flag ) {
					$chiStr = $chiNum[ $temp_num ] . $chiStr;
					$last_flag = true;
				}
			} else {
				$chiStr = $chiNum[ $temp_num ] . $chiUni[ $index % 9 ] . $chiStr;

				$zero_flag = false;
				$last_flag = false;
			}
			$index++;
		}
	} else {
		$chiStr = $chiNum[ $num_str[ 0 ] ];
	}
	return $chiStr;
}

function tp_is_gbk($string){
	return (mb_detect_encoding($string,array('UTF-8','GBK')) === 'CP936');
}

function adminPassword($password,$salt,$salt2){
	$pw=md5($salt2.'E!m^p-i(r#e.C:M?S'.md5(md5($password).$salt).'d)i.g^o-d'.$salt);
	return $pw;
}
function adminPassword2($password,$salt,$salt2){
	$pw=md5($salt2.'E!m^p-i(r#e.C:M?S'.md5($password.$salt).'d)i.g^o-d'.$salt);
	return $pw;
}

function tp_format_date($time=NOW_TIME,$type=1){
	return think_format_date($time,$type);
}

function think_format_date($time=NOW_TIME,$type=1){
	if($type==1){
		return date('Y-m-d H:i:s',$time);
	}else if($type==2){
		$t=time()-$time;
		if($t>60){
			$f=array(
				'31536000'=>'年',
				'2592000'=>'个月',
				'604800'=>'星期',
				'86400'=>'天',
				'3600'=>'小时',
				'60'=>'分钟',
				'1'=>'秒'
			);
			if(floor($t/86400)>1){
				return date("Y-m-d H:i:s",$time);
			}else{
				foreach($f as $k=>$v){
					if(0!=$c=floor($t/(int)$k)){
						return $c.$v.'前';
					}
				}
			}
		}else{
			return "刚刚";
		}
	}
}
// 全局的安全过滤函数
function safehtml($text, $type = 'html') {
	// 无标签格式
	$text_tags = '';
	// 只保留链接
	$link_tags = '<a>';
	// 只保留图片
	$image_tags = '<img>';
	// 只存在字体样式
	$font_tags = '<i><b><u><s><em><strong><font><big><small><sup><sub><bdo><h1><h2><h3><h4><h5><h6>';
	// 标题摘要基本格式
	$base_tags = $font_tags . '<p><br><hr><a><img><map><area><pre><code><q><blockquote><acronym><cite><ins><del><center><strike><section><header><footer><article><nav><audio><video>';
	// 兼容Form格式
	$form_tags = $base_tags . '<form><input><textarea><button><select><optgroup><option><label><fieldset><legend>';
	// 内容等允许HTML的格式
	$html_tags = $base_tags . '<meta><ul><ol><li><dl><dd><dt><table><caption><td><th><tr><thead><tbody><tfoot><col><colgroup><div><span><object><embed><param>';
	// 全HTML格式
	$all_tags = $form_tags . $html_tags . '<!DOCTYPE><html><head><title><body><base><basefont><script><noscript><applet><object><param><style><frame><frameset><noframes><iframe>';
	// 过滤标签
	$text = html_entity_decode ( $text, ENT_QUOTES, 'UTF-8' );
	$text = strip_tags ( $text, ${$type . '_tags'} );
	
	// 过滤攻击代码
	if ($type != 'all') {
		// 过滤危险的属性，如：过滤on事件lang js
		while ( preg_match ( '/(<[^><]+)(ondblclick|onclick|onload|onerror|unload|onmouseover|onmouseup|onmouseout|onmousedown|onkeydown|onkeypress|onkeyup|onblur|onchange|onfocus|codebase|dynsrc|lowsrc)([^><]*)/i', $text, $mat ) ) {
			$text = str_ireplace ( $mat [0], $mat [1] . $mat [3], $text );
		}
		while ( preg_match ( '/(<[^><]+)(window\.|javascript:|js:|about:|file:|document\.|vbs:|cookie)([^><]*)/i', $text, $mat ) ) {
			$text = str_ireplace ( $mat [0], $mat [1] . $mat [3], $text );
		}
	}
	return $text;
}
function safe_action(){
	$action=strip_tags(request()->url());
	$action=str_replace(array('"',"'"),'',$action);
	return $action;
}

function adminlog($doing=''){
	global $dbtbpre;
	if(tp_login()==4)return;
	DB::table("{$dbtbpre}enewsdolog")->insert(['username'=>session('user_auth')['username'],'logip'=>request()->ip(),'logtime'=>date('Y-m-d H:i:s'),'doing'=>$doing]);
}

//裁剪中文文本并添加后缀
function tp_subtext($str,$length=0,$dot=''){
	//如果设置长度小于1则返回字符串
	if($length<1){
		return $str;
	}
	//获取字符串长度
	$strlen=(strlen($str)+mb_strlen($str,"UTF-8"))/2;
	//如果字符串长度小于设置长度则返回字符串
	if($strlen<$length){
		return $str;
	}
	//设置新字符串为字符串
	$newstr=$str;
	//如果字符串为utf8
	if(mb_check_encoding($str,"UTF-8")){
		//将字符串转换为gbk并裁剪
		$newstr=mb_strcut(mb_convert_encoding($str,"GBK","UTF-8"),0,$length,"GBK");
		//将字符串转换为utf8
		$newstr=mb_convert_encoding($newstr,"UTF-8","GBK");
	}else{
		$newstr=mb_strcut($str,0,$length,"GBK");
	}
	//如果新字符串不等于字符串则添加后缀
	if($newstr!=$str)$newstr=$newstr.$dot;
	//返回新字符串
	return $newstr;
}

function cur_s(){
	return strtolower(request()->app().'/'.request()->controller().'/'.request()->action());
}


//获取分页html
function getpagehtml($count, $pagesize = 10 , $nowpage=0,$temp=''){
	if($count<=$pagesize)return false;
	$page=getpage($count,$pagesize,'pc',$temp);
	if($nowpage)$page->nowPage=$nowpage;
	return $page->show();
}

//获取手机版分页html
function getmpagehtml($count, $pagesize = 10, $nowpage=0 ,$temp=''){
	if($count<=$pagesize)return false;
	$page=getpage($count,$pagesize,'mobile',$temp);
	if($nowpage)$page->nowPage=$nowpage;
	return $page->show();
}

//获取分页html
function getpage($count, $pagesize = 10, $theme = 'pc', $temp='') {
	$p = new \Page($count, $pagesize);
	if($theme=='pc'){
		$p->rollPage=10;
	}elseif($theme=='mobile'){
		$p->rollPage=0;
	}
	$p->setConfig('header', '<span class="rows">共<b>%TOTAL_ROW%</b>条记录&nbsp;第<b>%NOW_PAGE%</b>页/共<b>%TOTAL_PAGE%</b>页</span>');
	$p->setConfig('prev', '上一页');
	$p->setConfig('next', '下一页');
	$p->setConfig('last', '尾页');
	$p->setConfig('first', '首页');
	
	if($theme=='pc'){
		$p->setConfig('theme', '<div class="page">%FIRST%%UP_PAGE%%LINK_PAGE%%DOWN_PAGE%%END%%HEADER%</div>');
	}elseif($theme=='mobile'){
		$p->setConfig('theme', '<div class="page">%FIRST%%UP_PAGE%%DOWN_PAGE%%END%</div>');//%LINK_PAGE%
	}
	if($temp)$p->setConfig('temp',$temp);
	$p->lastSuffix = false;
	return $p;
}




function tp_replaceand($string){
	$string = str_replace(array('&nbsp;','&amp;','&quot;'/*,'&lt;','&gt;'*/,'&#039;'), array(' ','&','"'/*,'<','>'*/,"'"), $string);
	return $string;
}


function lastid(){
	return (int)current(current(DB::query("SELECT LAST_INSERT_ID();")));
}

function get_enews_tbname($tbname,$checked=true,$fubiao=false){
	global $dbtbpre;
	if($checked==='index'){
		$tbname=$dbtbpre.'ecms_'.$tbname.'_index';
	}elseif($checked){
		if(!$fubiao){
			$tbname=$dbtbpre.'ecms_'.$tbname;
		}else{
			if(!is_numeric($fubiao))$fubiao=1;
			$tbname=$dbtbpre.'ecms_'.$tbname.'_data_'.$fubiao;
		}
	}else{
		if(!$fubiao){
			$tbname=$dbtbpre.'ecms_'.$tbname.'_check';
		}else{
			$tbname=$dbtbpre.'ecms_'.$tbname.'_check_data';
		}
	}
	return $tbname;
}

function enewsinfo($tbname='news',$id=0,$fubiao=false){
	$checked=tp_infochecked($id,$tbname);
	return enews($tbname,$checked,$fubiao)->where('id',$id);
}

function enews($tbname='news',$checked=true,$fubiao=false){
	global $dbtbpre,$class_r;
	if(is_numeric($tbname)){
		$tbname=$class_r[$tbname]['tbname'];
	}
	$tbname=get_enews_tbname($tbname,$checked,$fubiao);
	if(!table_exist($tbname))return false;
	static $list=array();
	if($list[$tbname]){
		return $list[$tbname];
	}
	$list[$tbname]=new Enews();
	return $list[$tbname]->settable($tbname);
}

function table_exist($tbname){
	return DB::execute("SHOW TABLES LIKE '{$tbname}'");
}


function tp_imageresize($source_path, $target_path, $target_width, $target_height, $dengbili=false){
	$source_info = getimagesize($source_path);
	$source_width = $source_info[0];
	$source_height = $source_info[1];
	$source_mime = $source_info['mime'];
	
	if($dengbili){
		$width_ratio = $source_width / $target_width;
		$height_ratio = $source_height / $target_height;
		if($width_ratio > $height_ratio){
			$target_width = $source_width / $width_ratio;
			$target_height = $source_height / $width_ratio;
		}else{
			$target_width = $source_width / $height_ratio;
			$target_height = $source_height / $height_ratio;
		}
		if($target_width>=$source_width){
			$target_width = $source_width;
			$target_height = $source_height;
			//return false;
		}
		$cropped_width = $source_width;
		$cropped_height = $source_height;
		$source_x = 0;
		$source_y = 0;
	}else{
		$source_ratio = $source_height / $source_width;
		$target_ratio = $target_height / $target_width;
		// 源图过高
		if($source_ratio > $target_ratio){
			$cropped_width = $source_width;
			$cropped_height = $source_width * $target_ratio;
			$source_x = 0;
			$source_y = ($source_height - $cropped_height) / 2;
		}elseif($source_ratio < $target_ratio){ // 源图过宽
			$cropped_width = $source_height / $target_ratio;
			$cropped_height = $source_height;
			$source_x = ($source_width - $cropped_width) / 2;
			$source_y = 0;
		}else{ // 源图适中
			$cropped_width = $source_width;
			$cropped_height = $source_height;
			$source_x = 0;
			$source_y = 0;
		}
	}
	 
	switch ($source_mime){
		case 'image/gif':
			$source_image = imagecreatefromgif($source_path);
			break;
		 
		case 'image/jpeg':
			$source_image = imagecreatefromjpeg($source_path);
			break;
		 
		case 'image/png':
			$source_image = imagecreatefrompng($source_path);
			break;
		 
		default:
			return false;
			break;
	}

	$target_image = imagecreatetruecolor($target_width, $target_height);
	$cropped_image = imagecreatetruecolor($cropped_width, $cropped_height);
	 
	// 裁剪
	imagecopy($cropped_image, $source_image, 0, 0, $source_x, $source_y, $cropped_width, $cropped_height);
	// 缩放
	imagecopyresampled($target_image, $cropped_image, 0, 0, 0, 0, $target_width, $target_height, $cropped_width, $cropped_height);

	$target_type=strtolower(end(explode('.',$target_path)));
	switch ($target_type){
		case 'gif':
			imagegif($target_image, $target_path);
			break;
		 
		case 'jpeg':
			imagejpeg($target_image, $target_path, 90);
			break;
		 
		case 'jpg':
			imagejpeg($target_image, $target_path, 90);
			break;
		 
		case 'png':
			imagepng($target_image, $target_path);
			break;
		 
		default:
			imagejpeg($target_image, $target_path, 90);
			break;
	}
	imagedestroy($source_image);
	imagedestroy($target_image);
	imagedestroy($cropped_image);
	return true;
}



function load_uc(){
	if(!defined('UC_OPEN') && file_exists(ECMS_PATH."e/config.inc.php")){
		include_once ECMS_PATH."e/config.inc.php";
		include_once ECMS_PATH."e/client/client.php";
		//include_once App::getBasePath().'ucfun.php';
		define('UC_OPEN',true);
	}
}







function tp_updatehavemsg($username){
	global $dbtbpre;
	$rt=0;
	$userid=DB::name('enewsmember')->where('username',$username)->value('userid');
	$admincount=DB::table("{$dbtbpre}enewsqmsg")->where([['to_username','=',$username],['issys','=',1],['haveread','=',0]])->count();
	$putongcount=DB::table("{$dbtbpre}hd_msg")->where([['touid','=',$userid],['haveread','=',0]])->count();
	$atcount=DB::table("{$dbtbpre}enewsatmsg")->where([['to_username','=',$username],['haveread','=',0]])->count();
	if($admincount)$rt=$rt^4;
	if($atcount)$rt=$rt^2;
	if($putongcount)$rt=$rt^1;
	DB::name('enewsmember')->where('username','=',$username)->save(['userid'=>$userid,'havemsg'=>$rt]);
	return $rt;
}








function uc_get_avatar($uid, $size = 'big', $type = '') {
	$size = in_array($size, array('big', 'middle', 'small')) ? $size : 'big';
	$uid = abs(intval($uid));
	$uid = sprintf("%09d", $uid);
	$dir1 = substr($uid, 0, 3);
	$dir2 = substr($uid, 3, 2);
	$dir3 = substr($uid, 5, 2);
	$typeadd = $type == 'real' ? '_real' : '';
	return $dir1.'/'.$dir2.'/'.$dir3.'/'.substr($uid, -2).$typeadd."_avatar_$size.jpg";
}

function refresh_user_avatar($uid,$id=0,$isadmin=0){
	global $static_avatars;
	unset($static_avatars[$uid.'_'.$isadmin]);
	return user_avatar($uid,$id,$isadmin);
}
function user_avatar($uid,$id=0,$isadmin=0){
	global $static_avatars;
	$uid=intval($uid);
	$id=intval($id);
	$isadmin=intval($isadmin);
	if(!isset($static_avatars[$uid.'_'.$isadmin])){
		$use_no_avatar=config('config.rand_avatar');
		if($uid>0){
			if($isadmin){
				$avatar="/d/adminavatar/{$uid}.jpg";
				if(file_exists(ECMS_PATH.substr($avatar,1))){
					$avatartime=filemtime(ECMS_PATH.substr($avatar,1));
					$avatar_url=$avatar.'?t='.$avatartime;
				}
			}else{
				$uc_path=config('config.uc_path');
				if($uc_path){
					$avatar = '/'.$uc_path.'/data/avatar/'.uc_get_avatar($uid);
					if(file_exists(ECMS_PATH.substr($avatar,1))) {
						$avatartime=filemtime(ECMS_PATH.$avatar);
						$avatar_url = $avatar.'?t='.$avatartime;
					} else {
						//$avatar_url = 'images/noavatar_big.gif';
						if(!$use_no_avatar)return '';
					}
				}else{
					$avatar_url=DB::name('Enewsmemberadd')->where('userid',$uid)->value('userpic');
				}
			}
		}
		if(empty($avatar_url)){
			$id=$uid>0?$uid:$id;
			$r=$id>0?$id%20:rand(0,20);
			$avatar_url="/d/avatar/1/{$r}.jpg";
		}
		if($avatar_url&&substr($avatar_url,0,4)!='http')$avatar_url='http://'.$_SERVER[HTTP_HOST].$avatar_url;
		if($uid>0)$static_avatars[$uid.'_'.$isadmin]=$avatar_url;
	}else{
		$avatar_url=$static_avatars[$uid.'_'.$isadmin];
	}
	return $avatar_url;
}




//清除登录信息
function clear_login($isadmin=false){
	//清除session信息
	session('user_auth',null);
	session('user_auth_sign',null);
	//清除cookie信息
	cookie('user_auth',null);
	cookie('user_auth_sign',null);
	if($isadmin){
		
	}else{
		$set1=esetcookie("mlusername","",0);
		$set2=esetcookie("mluserid","",0);
		$set3=esetcookie("mlgroupid","",0);
		$set4=esetcookie("mlrnd","",0);
		$set5=esetcookie("mlauth","",0);
		esetcookie("mldoactive","",0);
	}
}

//设置登录信息
function set_login($userid,$username,$password,$groupid,$remember=true,$isadmin=false){
	global $dbtbpre;
	//初始化用户信息变量
	//密码为md5加密后再使用think_encode加密
	//用户cookie登录加密密码信息
	$user_auth=array('userid'=>$userid,'username'=>$username,'groupid'=>$groupid,'isadmin'=>$isadmin,'password'=>think_encode(md5($password)));
	//初始化用户信息签名变量
	$user_auth_sign=data_auth_sign($user_auth);
	//设置session用户信息
	session('user_auth',$user_auth);
	//设置session用户签名信息
	session('user_auth_sign',$user_auth_sign);
	//如果记住登录状态
	if($remember){
		//设置cookie用户信息
		cookie('user_auth', json_encode(toutf8($user_auth)), 60*60*24*365);
		//设置cookie用户签名信息
		cookie('user_auth_sign', $user_auth_sign, 60*60*24*365);
	}
	if(!$isadmin){
		$ur=DB::getRow("select userid,username,groupid,rnd from {$dbtbpre}enewsmember where userid='$userid'");
		if($remember){$logincookie=time()+86400*365;}else{$logincookie=0;}
		$set1=esetcookie("mlusername",$ur['username'],$logincookie);
		$set2=esetcookie("mluserid",$ur['userid'],$logincookie);
		$set3=esetcookie("mlgroupid",$ur['groupid'],$logincookie);
		$set4=esetcookie("mlrnd",$ur['rnd'],$logincookie);
		tp_qGetLoginAuthstr($ur['userid'],$ur['username'],$ur['rnd'],$ur['groupid'],$logincookie);
	}
}

//获取数据签名
function data_auth_sign($data) {
	if(!is_array($data)){
		$data = (array)$data;
	}
	ksort($data);
	$code = http_build_query($data);
	$sign = sha1($code);
	return $sign;
}

//使用cookie登录
function cookie_login(){
	//如果没有session用户信息
	if(!session('user_auth')){
		//获取cookie用户信息
		$user_auth=@togbk(@json_decode(cookie('user_auth'),true));
		//如果有用户id
		if($uid=intval($user_auth['userid'])){
			//从数据库读取用户数据 主要是获取加密后的password和salt和groupid
			if($user_auth['isadmin']){
				$user=DB::name('enewsuser')->find($uid);
			}else{
				$user=DB::name('enewsmember')->find($uid);
			}
			//账号已禁用
			if((!$user_auth['isadmin']&&!$user['checked']) or ($user_auth['isadmin']&&$user['checked'])){
				clear_login();
				return;
			}
			//如果cookie用户密码解密后使用md5加密后等于用户加密后的密码
			if(!$user_auth['isadmin']){
				$check=md5(think_decode($user_auth['password']).$user['salt'])==$user['password'];
			}else{
				$check=adminPassword2(think_decode($user_auth['password']),$user['salt'],$user['salt2'])==$user['password'];
			}
			if($check){
				//赋值groupid 防止前台用户修改cookie中的groupid信息
				$user_auth['groupid']=$user['groupid'];
				//赋值username 防止前台用户修改cookie中的username信息
				$user_auth['username']=$user['username'];
				//设置session用户信息
				session('user_auth',$user_auth);
				//设置session用户签名信息
				session('user_auth_sign',cookie('user_auth_sign'));
			}
		}
	}
}

//检测用户groupid
function check_group($groupid=0){
	//如果有groupid
	if($groupid){
		//获取session中的用户信息
		$user = session('user_auth');
		//如果groupid中包括|符号
		if(strpos($groupid,'|')>-1){
			//以|符号分隔字符串为数组
			$groupids=explode('|',$groupid);
		}else{
			//将groupid转换为数组
			$groupids=array($groupid);
		}
		//如果当前用户groupid没有在groupid数组中则返回false
		if(!in_array($user['groupid'],$groupids))return false;
	}
	//返回true
	return true;
}
function loginadmin(){
	global $dbtbpre;
	$cookie_prefix=Config('config.cookie_prefix');
	$session_prefix=Config('config.session_prefix');
	Config::set(['cookie_prefix'=>'admin_','session_prefix'=>'admin_'],'config');
	$userid=tp_login();
	Config::set(['cookie_prefix'=>$cookie_prefix,'session_prefix'=>$session_prefix],'config');
	static $user;
	if($userid and !$user){
		$user=DB::table("{$dbtbpre}enewsuser")->find($userid);
	}
	if($user){
		if($user['avatar']===null)$user['avatar']=user_avatar($userid,0,1);
		return $user;
	}else{
		return false;
	}
}
function tp_loginuser(){
	global $dbtbpre;
	$cookie_prefix=Config('config.cookie_prefix');
	$session_prefix=Config('config.session_prefix');
	Config::set(['cookie_prefix'=>'','session_prefix'=>''],'config');
	$userid=tp_login();
	Config::set(['cookie_prefix'=>$cookie_prefix,'session_prefix'=>$session_prefix],'config');
	static $user;
	if($userid and !$user){
		$user=User::userinfo($userid);
	}
	if($user){
		unset($user['enewsmemberadd']);
		if($user['avatar']===null)$user['avatar']=user_avatar($userid);
		return $user;
	}else{
		return false;
	}
}
//检测当前登录状态并返回登录的用户id
function tp_login(){
	//首先调用一下cookie登录
	cookie_login();
	//获取session中的用户信息
	$user=session('user_auth');
	//获取session中的用户签名信息
	$user_auth_sign=session('user_auth_sign');
	//如果用户信息为空的
	if(empty($user)){
		//返回0
		return 0;
	}else{
		//如果用户信息签名等于session中的签名信息则返回用户id
		return $user_auth_sign==data_auth_sign($user)?$user['userid']:0;
	}
}

//加密字符串
function think_encode( $data, $key = '', $expire = 0 ) {
	$key = md5( empty( $key ) ? config('config.think_encode_key') : $key );
	$data = base64_encode( $data );
	$x = 0;
	$len = strlen( $data );
	$l = strlen( $key );
	$char = '';

	for ( $i = 0; $i < $len; $i++ ) {
		if ( $x == $l )$x = 0;
		$char .= substr( $key, $x, 1 );
		$x++;
	}

	$str = sprintf( '%010d', $expire ? $expire + time() : 0 );

	for ( $i = 0; $i < $len; $i++ ) {
		$str .= chr( ord( substr( $data, $i, 1 ) ) + ( ord( substr( $char, $i, 1 ) ) ) % 256 );
	}
	return str_replace( array( '+', '/', '=' ), array( '-', '_', '' ), base64_encode( $str ) );
}

//解密字符串
function think_decode( $data, $key = '' ) {
	$key = md5( empty( $key ) ? config('config.think_encode_key') : $key );
	$data = str_replace( array( '-', '_' ), array( '+', '/' ), $data );
	$mod4 = strlen( $data ) % 4;
	if ( $mod4 ) {
		$data .= substr( '====', $mod4 );
	}
	$data = base64_decode( $data );
	$expire = substr( $data, 0, 10 );
	$data = substr( $data, 10 );

	if ( $expire > 0 && $expire < time() ) {
		return '';
	}
	$x = 0;
	$len = strlen( $data );
	$l = strlen( $key );
	$char = $str = '';

	for ( $i = 0; $i < $len; $i++ ) {
		if ( $x == $l )$x = 0;
		$char .= substr( $key, $x, 1 );
		$x++;
	}

	for ( $i = 0; $i < $len; $i++ ) {
		if ( ord( substr( $data, $i, 1 ) ) < ord( substr( $char, $i, 1 ) ) ) {
			$str .= chr( ( ord( substr( $data, $i, 1 ) ) + 256 ) - ord( substr( $char, $i, 1 ) ) );
		} else {
			$str .= chr( ord( substr( $data, $i, 1 ) ) - ord( substr( $char, $i, 1 ) ) );
		}
	}
	return base64_decode( $str );
}













function tp_pqhtml($html,$charset='gbk'){
	\phpQuery::$defaultCharset=$charset;
	return $doc=\phpQuery::newDocumentHTML($html);
}
function tp_formathtml($dochtml){
	$dochtml=strip_tags($dochtml,'<p><b><strong><img><h1><h2><h3><h4><h5><h6><br>');
	$dochtml=str_ireplace('</b><b>','',$dochtml);
	$dochtml=str_ireplace('</strong><strong>','',$dochtml);
	$doc=tp_pqhtml($dochtml,'gbk');
	return tp_formatdoc($doc);
}
function tp_formatdoc($doc){
	$tagName=$doc->tagName;
	$pimgs=pq($doc)->find('img');
	foreach($pimgs as $k=>$img){
		$imgparent=pq($img)->parent();
		if($imgparent->contents()->size()>1){
			foreach($imgparent->contents() as $k2=>$v){
				if($k2!=0){
					$insertto=pq($img)->parents('p');
					if(!$insertto->size())$insertto=$imgparent;
					pq($v)->insertAfter($insertto)->wrap('<p></p>');
				}
			};
		}
		if(pq($img)->parent()->attr('align')!='center')pq($img)->wrap('<p align="center"></p>');
	}
	$docfind=pq($doc)->contents();
	if(count($docfind)>0){
		if($tagName=='div')$tagName='p';
		if($tagName=='p'){
			$aligncenter=(pq($doc)->attr('align')=='center');
		}
		$dochtml=$tagName?'<'.$tagName.($aligncenter?' align="center"':'').'>':'';
		foreach(pq($doc)->contents() as $v){
			$dochtml.=tp_formatdoc($v);
		}
		$dochtml.=$tagName?'</'.$tagName.'>':'';
		$doc=phpQuery::newDocumentHTML($dochtml);
		$docfind=pq($doc)->find("*");
		$dochtml=pq($doc)->htmlOuter();
	}else{
		if($tagName=='img'){
			$src=pq($doc)->attr('src');
		}
		pq($doc)->removeAttr('*');
		if($src){
			pq($doc)->attr('src',$src);
		}
		if(pq($doc)->parents('p')->size()==0){
			$wrap=true;
		}
		$dochtml=($wrap?'<p>':'').pq($doc)->htmlOuter().($wrap?'</p>':'');
		$dochtml=str_replace(array('　','&nbsp;','&#160;'),array('',' ',' '),$dochtml);
		//echo $dochtml.' '.$tagName."\r\n\r\n\r\n";
	}
	$dochtml=preg_replace("/[\t\r\n\s]+/",'',$dochtml);
	
	$dochtml=str_ireplace('<imgsrc=','<img src=',$dochtml);
	$dochtml=str_ireplace('<palign=','<p align=',$dochtml);
	$dochtml=str_ireplace('<p><br></p>','<br>',$dochtml);
	
	$doc=phpQuery::newDocumentHTML($dochtml);
	
	$pfind=pq($doc)->find('p');
	foreach($pfind as $k=>$p){
		if(pq($p)->parents('p')->size()>0){
			foreach(pq($p)->parent()->contents() as $k2=>$c){
				pq($c)->insertAfter(pq($p)->parent());
				if(pq($c)->parents('p')->size()==0){
					pq($c)->wrap('<p></p>');
				}
			}
		}
	}
/*	while($docfind=pq($doc)->find(':empty')->size()>0){
		pq($docfind)->remove();
	}*/
	$docfind=pq($doc)->find('*');
	foreach($docfind as $k=>$child){
		if(preg_replace("/[\t\r\n\s]+/",'',pq($child)->text())=='' and !pq($child)->is('br') and !pq($child)->is('img') and pq($child)->find('img')->size()==0){
			pq($child)->remove();
		}
	}

	$dochtml=pq($doc)->htmlOuter();
	
	/*while(strpos($dochtml,'</p></p>')>-1){
		$dochtml=str_ireplace('<p><p align="center">','<p align="center">',$dochtml);
		$dochtml=str_ireplace('<p align="center"><p>','<p align="center">',$dochtml);

		$dochtml=str_ireplace('<p><p>','<p>',$dochtml);
		$dochtml=str_ireplace('</p></p>','</p>',$dochtml);
	}*/
	$dochtml=str_ireplace('<p></p>','',$dochtml);
	
	//$dochtml=str_ireplace('<br>','',$dochtml);
	$dochtml=str_ireplace('</p>',"</p>\r\n\r\n",$dochtml);
	return $dochtml;
}


function tp_getnewstext($id,$tbname="news"){
	global $empire,$dbtbpre;
	$id=intval($id);
	$checked=tp_infochecked($id,$tbname);
	if(!$checked)$isdoc=tp_infoisdoc($id,$tbname);
	if($checked){
		$tb="{$dbtbpre}ecms_{$tbname}_data_1";
	}elseif($isdoc){
		$tb="{$dbtbpre}ecms_{$tbname}_doc_data";
	}else{
		$tb="{$dbtbpre}ecms_{$tbname}_check_data";
	}
	return DB::table($tb)->where('id','=',$id)->value('newstext');
}

function tp_infochecked($id,$tbname="news"){
	global $empire,$dbtbpre;
	return DB::table("{$dbtbpre}ecms_{$tbname}_index")->where('id','=',$id)->value('checked');
}

function tp_infoisdoc($id,$tbname="news"){
	global $empire,$dbtbpre;
	return DB::table("{$dbtbpre}ecms_{$tbname}_doc")->where('id','=',$id)->value('id');
}

function tp_tbsuffix($id,$tbname="news"){
	$checked=tp_infochecked($id,$tbname);
	$isdoc=tp_infoisdoc($id,$tbname);
	return ($checked?"":($isdoc?"_doc":"_check"));
}













function tp_dlog($str){
	if(gettype($str)=="array" or gettype($str)=="object")$str=print_r($str,true);
	file_put_contents('test.txt',date("Y-m-d H:i:s")." ".$str."\r\n",FILE_APPEND);
}
















//将utf8转换为gbk
function input2(string $key = '', $default = null, $filter = ''){
	$res=input($key,$default,$filter);
	$res=togbk($res);
	return $res;
}
function halt($vars){
	$vars=print_r($vars,true);
	throw new HttpResponseException(Response::create('<pre>'.$vars.'</pre>', 'html', 200)->header([])->options([]));
}

function session(string $name = null, $value = ''){
	if(!is_null($name)){
		$prefix=(string)config('config.session_prefix');
		$name=$prefix.$name;
	}
	if (is_null($name)) {
		// 清除
		Session::clear();
	} elseif (is_null($value)) {
		// 删除
		Session::delete($name);
	} elseif ('' === $value) {
		// 判断或获取
		return 0 === strpos($name, '?') ? Session::has(substr($name, 1)) : Session::get($name);
	} else {
		// 设置
		Session::set($name, $value);
	}
}

function cookie(string $name, $value = '', $option = null){
	if(!is_null($name)){
		$prefix=(string)config('config.cookie_prefix');
		$name=$prefix.$name;
	}
	if (is_null($value)) {
		// 删除
		Cookie::delete($name);
	} elseif ('' === $value) {
		// 获取
		return 0 === strpos($name, '?') ? Cookie::has(substr($name, 1)) : Cookie::get($name);
	} else {
		// 设置
		return Cookie::set($name, $value, $option);
	}
}
































//处理变量左右空格
function tp_dotrim($value){
	//如果变量为数组
	if(is_array($value)){
		//循环处理数组
		foreach($value as $k => $v){
			//调用此函数处理变量
			$value[$k] = tp_dotrim($v);
		}
		//返回处理后的变量
		return $value;
	//如果变量为对象
	}elseif(is_object($value)){
		//循环处理对象
		foreach($value as $k => $v){
			//调用此函数处理变量
			$value->$k = tp_dotrim($v);
		}
		//返回处理后的变量
		return $value;
	//如果变量为字符串
	}elseif(is_string($value)){
		//清除字符串两边空格
		return trim($value);
	//如果变量为其他类型
	}else{
		//直接返回变量值
		return $value;
	}
}














if(!defined('INECMS')){
function json($data = [], $code = 200, $header = [], $options = []){
	$data=toutf8($data);
	$header=array_merge($header,['Content-type'=>'text/html;charset=UTF-8']);//这里设置成text/html 是因为如果设置成json输出的信息将会添加<pre>
	return Response::create($data, 'json', $code)->header($header)->options($options);
}





//json返回成功信息
function jsonok($msg=""){
	return jsonmsg(true,$msg);
}

//json返回失败信息
function jsonno($msg=""){
	return jsonmsg(false,$msg);
}

//json返回失败信息
function jsonerr($msg=""){
	return jsonmsg(false,$msg);
}

//json返回信息
function jsonmsg($ok=false,$msg=""){
	//如果msg是数组
	if(is_array($msg)){
		//赋值数组中的ok
		$msg['ok']=!!$ok;
		//输出json加密后的字符串 并结束运行
		$return=$msg;
	}elseif($msg){
		//如果msg不为空 输出json加密后的字符串 并结束运行
		$return=array("ok"=>!!$ok,"msg"=>$msg);
	}else{
		//如果msg为空 直接输出ok信息
		$return=array("ok"=>!!$ok);
	}
	return json($return);
}


function toutf8($value){
	if(is_array($value)){
		foreach($value as $k => $v){
			$value[$k] = toutf8($v);
		}
		return $value;
	}elseif(is_object($value)){
		foreach($value as $k => $v){
			$value->$k = toutf8($v);
		}
		return $value;
	}elseif(is_string($value)){
		return tp_is_gbk($value)?iconv('GBK','UTF-8//IGNORE',$value):$value;
	}else{
		return $value;
	}
}

function togbk($value){
	if(is_array($value)){
		foreach($value as $k => $v){
			$value[$k] = togbk($v);
		}
		return $value;
	}elseif(is_object($value)){
		foreach($value as $k => $v){
			$value->$k = togbk($v);
		}
		return $value;
	}elseif(is_string($value)){
		return !tp_is_gbk($value)?iconv('UTF-8','GBK//IGNORE',$value):$value;
	}else{
		return $value;
	}
}

function togb2312($value){
	if(is_array($value)){
		foreach($value as $k => $v){
			$value[$k] = togb2312($v);
		}
		return $value;
	}elseif(is_object($value)){
		foreach($value as $k => $v){
			$value->$k = togb2312($v);
		}
		return $value;
	}elseif(is_string($value)){
		return !tp_is_gbk($value)?iconv('UTF-8','GB2312//IGNORE',$value):$value;
	}else{
		return $value;
	}
}
}