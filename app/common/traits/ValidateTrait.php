<?php
namespace app\common\traits;
use think\exception\ValidateException;
use think\Validate;
trait ValidateTrait{
	/**
	 * 验证数据
	 * @access protected
	 * @param  array        $data     数据
	 * @param  string|array $validate 验证器名或者验证规则数组
	 * @param  array        $message  提示信息
	 * @param  bool         $batch    是否批量验证
	 * @return array|string|true
	 * @throws ValidateException
	 */
	public function validate( array $data, $validate, array $message = [], bool $batch = false ) {
		if ( is_array( $validate ) ) {
			$v = new Validate();
			$v->rule( $validate );
		} else {
			if ( strpos( $validate, '.' ) ) {
				// 支持场景
				list( $validate, $scene ) = explode( '.', $validate );
			}
			if(false !== strpos( $validate, '\\' )){
				$class=$validate;
			}else if(!$this->app){
				if($GLOBALS['app']){
					$class=$GLOBALS['app']->parseClass( 'validate', $validate );
				}else{
					throw new Exception('app not exists');
				}
			}else{
				$class=$this->app->parseClass( 'validate', $validate );
			}
			$v = new $class();
			if ( !empty( $scene ) ) {
				$v->scene( $scene );
			}
		}

		$v->message( $message );

		// 是否批量验证
		if ( $batch || $this->batchValidate ) {
			$v->batch( true );
		}

		$result = $v->failException( false )->check( $data );
		if ( true !== $result ) {
			return $v->getError();
		} else {
			return $result;
		}
	}
}
?>