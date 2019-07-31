<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2019 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

namespace think\facade;

use think\Facade;

/**
 * @see \think\Db
 * @mixin \think\Db
 * @method object buildQuery(string $query, mixed $connection) static ����һ���µĲ�ѯ����
 * @method \think\db\Query connect(array $config =[], mixed $name = false) static ����/�л����ݿ�����
 * @method \think\db\Connection getConnection() static ��ȡ���ݿ����Ӷ���
 * @method \think\db\Query master() static ������������ȡ����
 * @method \think\db\Query table(string $table) static ָ�����ݱ���ǰ׺��
 * @method \think\db\Query name(string $name) static ָ�����ݱ�����ǰ׺��
 * @method \think\db\Raw raw(string $value) static ʹ�ñ��ʽ��������
 * @method \think\db\Query where(mixed $field, string $op = null, mixed $condition = null) static ��ѯ����
 * @method \think\db\Query whereRaw(string $where, array $bind = []) static ���ʽ��ѯ
 * @method \think\db\Query whereExp(string $field, string $condition, array $bind = []) static �ֶα��ʽ��ѯ
 * @method \think\db\Query when(mixed $condition, mixed $query, mixed $otherwise = null) static ������ѯ
 * @method \think\db\Query join(mixed $join, mixed $condition = null, string $type = 'INNER') static JOIN��ѯ
 * @method \think\db\Query view(mixed $join, mixed $field = null, mixed $on = null, string $type = 'INNER') static ��ͼ��ѯ
 * @method \think\db\Query field(mixed $field, boolean $except = false) static ָ����ѯ�ֶ�
 * @method \think\db\Query fieldRaw(string $field, array $bind = []) static ָ����ѯ�ֶ�
 * @method \think\db\Query union(mixed $union, boolean $all = false) static UNION��ѯ
 * @method \think\db\Query limit(mixed $offset, integer $length = null) static ��ѯLIMIT
 * @method \think\db\Query order(mixed $field, string $order = null) static ��ѯORDER
 * @method \think\db\Query orderRaw(string $field, array $bind = []) static ��ѯORDER
 * @method \think\db\Query cache(mixed $key = null , integer $expire = null) static ���ò�ѯ����
 * @method \think\db\Query withAttr(string $name, callable $callback = null) static ʹ�û�ȡ����ȡ����
 * @method mixed value(string $field) static ��ȡĳ���ֶε�ֵ
 * @method array column(string $field, string $key = '') static ��ȡĳ���е�ֵ
 * @method mixed find(mixed $data = null) static ��ѯ������¼
 * @method mixed select(mixed $data = null) static ��ѯ�����¼
 * @method integer save(boolean $forceInsert = false) static �����¼ �Զ��ж�insert����update
 * @method integer insert(array $data, boolean $getLastInsID = false, string $sequence = null) static ����һ����¼
 * @method integer insertGetId(array $data, string $sequence = null) static ����һ����¼����������ID
 * @method integer insertAll(array $dataSet) static ���������¼
 * @method integer update(array $data) static ���¼�¼
 * @method integer delete(mixed $data = null) static ɾ����¼
 * @method boolean chunk(integer $count, callable $callback, string $column = null) static �ֿ��ȡ����
 * @method \Generator cursor(mixed $data = null) static ʹ���α���Ҽ�¼
 * @method mixed query(string $sql, array $bind = [], boolean $master = false, bool $pdo = false) static SQL��ѯ
 * @method integer execute(string $sql, array $bind = [], boolean $fetch = false, boolean $getLastInsID = false, string $sequence = null) static SQLִ��
 * @method \think\Paginator paginate(integer $listRows = 15, mixed $simple = null, array $config = []) static ��ҳ��ѯ
 * @method mixed transaction(callable $callback) static ִ�����ݿ�����
 * @method void startTrans() static ��������
 * @method void commit() static ���ڷ��Զ��ύ״̬����Ĳ�ѯ�ύ
 * @method void rollback() static ����ع�
 * @method boolean batchQuery(array $sqlArray) static ������ִ��SQL���
 * @method string getLastInsID(string $sequence = null) static ��ȡ��������ID
 * @method mixed getConfig(string $name = '') static ��ȡ���ݿ�����ò���
 */
class Db extends Facade
{
    /**
     * ��ȡ��ǰFacade��Ӧ�����������Ѿ��󶨵����������ʶ��
     * @access protected
     * @return string
     */
    protected static function getFacadeClass()
    {
        return 'db';
    }
}
