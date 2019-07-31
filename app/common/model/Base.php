<?php
namespace app\common\model;
use think\Model;

class Base extends Model {
	use \app\common\traits\ValidateTrait;
	//开启自动写入时间戳字段
	protected $autoWriteTimestamp = true;
}