<?php
namespace app\common\subscribe;
use think\facade\Db;

class DeleteFile {
	public function onDeleteNewsFile($info) {
		global $class_r, $addgethtmlpath;
		list($filename,$newspath,$classid,$newstext,$groupid)=$info;
		//文件类型
		if ( $groupid ) {
			$filetype = ".php";
		} else {
			$filetype = $class_r[ $classid ][ filetype ];
		}
		//是否有日期目录
		if ( empty( $newspath ) ) {
			$mynewspath = "";
		} else {
			$mynewspath = $newspath . "/";
		}
		$iclasspath = ReturnSaveInfoPath( $classid, $id );
		$r = explode( "[!--empirenews.page--]", $newstext );
		for ( $i = 1; $i <= count( $r ); $i++ ) {
			if ( strstr( $filename, '/' ) ) {
				DelPath( eReturnTrueEcmsPath() . 'mobile/' . $iclasspath . $mynewspath . ReturnInfoSPath( $filename ) );
				DelPath( eReturnTrueEcmsPath() . 'mip/' . $iclasspath . $mynewspath . ReturnInfoSPath( $filename ) );
			} else {
				if ( $i == 1 ) {
					$file1 = eReturnTrueEcmsPath() . 'mobile/' . $iclasspath . $mynewspath . $filename . $filetype;
					$file2 = eReturnTrueEcmsPath() . 'mip/' . $iclasspath . $mynewspath . $filename . $filetype;
				} else {
					$file1 = eReturnTrueEcmsPath() . 'mobile/' . $iclasspath . $mynewspath . $filename . "_" . $i . $filetype;
					$file2 = eReturnTrueEcmsPath() . 'mip/' . $iclasspath . $mynewspath . $filename . "_" . $i . $filetype;
				}
				DelFiletext( $file1 );
				DelFiletext( $file2 );
			}
		}
	}
	public function onDeleteOtherFile($info) {
		global $class_r,$dbtbpre;
		list($classid,$id,$r,$delfile,$delpl)=$info;
		if($class_r[$classid]['tbname']=="youji"){
			@unlink(ECMS_PATH.substr($r['titlepic'],1));
			@unlink(ECMS_PATH.substr($r['background'],1));
			$list=DB::query("select * from {$dbtbpre}youji_img where yjid=$id");
			foreach($list as $v){
				@unlink(ECMS_PATH.substr($v['img'],1));
				@unlink(ECMS_PATH.substr($v['cimg'],1));
				@unlink(ECMS_PATH.substr($v['simg'],1));
			}
			DB::execute("delete from {$dbtbpre}youji_img where yjid=$id");
			DB::execute("delete from {$dbtbpre}youji_place where yjid=$id");
		}
		if($class_r[$classid][tbname]=="ershou"){
			@unlink(ECMS_PATH.substr($r['titlepic'],1));
			$list=DB::queryquery("select * from {$dbtbpre}ershou_img where esid=$id");
			foreach($list as $v){
				@unlink(ECMS_PATH.substr($v['img'],1));
				@unlink(ECMS_PATH.substr($v['simg'],1));
			}
			DB::execute("delete from {$dbtbpre}ershou_img where esid=$id");
		}
	}
}