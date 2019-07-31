<?php
use think\facade\Db;

function tp_paihang_ul(){
	global $class_r,$emod_r,$dbtbpre;
	static $paihang_ul;
	if(!$paihang_ul){
		ob_start();?>
		
				<ul>
<?php
$list=res_cache_clicklist('classid,titleurl,username,title,newstime',8,50,'news,pictures');
$i=0;
foreach($list as $k=>$v){
	$i++;
	?>
	<li class="clearfix">
		<div class="lileft"><div class="num<?php echo $i?>"><?php echo $i?></div></div>
		<div class="liright">
			<a href="<?php echo $v['titleurl']?>"><?php echo $v['title']?></a>
			<div class="clearfix">
				<div class="writer fl"><?php echo $v['username']?></div>
				<div class="classname fr"><?php echo $class_r[$v['classid']]['classname']?></div>
			</div>
		</div>
	</li>
	<?
}
?>
				</ul>
		<?php
		$paihang_ul=ob_get_contents();
		ob_end_clean();
	}
	return $paihang_ul;
}
?>