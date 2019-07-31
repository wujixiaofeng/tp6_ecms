<?php
namespace app\common\traits;
use think\exception\ValidateException;
use think\Validate;
trait ValidateTrait{
	/**
	 * ��֤����
	 * @access protected
	 * @param  array        $data     ����
	 * @param  string|array $validate ��֤����������֤��������
	 * @param  array        $message  ��ʾ��Ϣ
	 * @param  bool         $batch    �Ƿ�������֤
	 * @return array|string|true
	 * @throws ValidateException
	 */
	public function validate( array $data, $validate, array $message = [], bool $batch = false ) {
		if ( is_array( $validate ) ) {
			$v = new Validate();
			$v->rule( $validate );
		} else {
			if ( strpos( $validate, '.' ) ) {
				// ֧�ֳ���
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

		// �Ƿ�������֤
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