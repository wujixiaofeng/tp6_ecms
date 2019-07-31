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
declare (strict_types = 1);

namespace think\model\concern;

use think\db\Query;

/**
 * ������ɾ��
 */
trait SoftDelete
{
    /**
     * �Ƿ������ɾ������
     * @var bool
     */
    protected $withTrashed = false;

    /**
     * �жϵ�ǰʵ���Ƿ���ɾ��
     * @access public
     * @return bool
     */
    public function trashed(): bool
    {
        $field = $this->getDeleteTimeField();

        if ($field && !empty($this->getOrigin($field))) {
            return true;
        }

        return false;
    }

    /**
     * ��ѯ��ɾ������
     * @access public
     * @return Query
     */
    public static function withTrashed(): Query
    {
        $model = new static();

        return $model->withTrashedData(true)->db();
    }

    /**
     * �Ƿ������ɾ������
     * @access protected
     * @param  bool $withTrashed �Ƿ������ɾ������
     * @return $this
     */
    protected function withTrashedData(bool $withTrashed)
    {
        $this->withTrashed = $withTrashed;
        return $this;
    }

    /**
     * ֻ��ѯ��ɾ������
     * @access public
     * @return Query
     */
    public static function onlyTrashed(): Query
    {
        $model = new static();
        $field = $model->getDeleteTimeField(true);

        if ($field) {
            return $model
                ->db()
                ->useSoftDelete($field, $model->getWithTrashedExp());
        }

        return $model->db();
    }

    /**
     * ��ȡ��ɾ�����ݵĲ�ѯ����
     * @access protected
     * @return array
     */
    protected function getWithTrashedExp(): array
    {
        return is_null($this->defaultSoftDelete) ? ['notnull', ''] : ['<>', $this->defaultSoftDelete];
    }

    /**
     * ɾ����ǰ�ļ�¼
     * @access public
     * @return bool
     */
    public function delete(): bool
    {
        if (!$this->isExists() || $this->isEmpty() || false === $this->trigger('BeforeDelete')) {
            return false;
        }

        $name = $this->getDeleteTimeField();

        if ($name && !$this->isForce()) {
            // ��ɾ��
            $this->set($name, $this->autoWriteTimestamp($name));

            $result = $this->exists()->withEvent(false)->save();

            $this->withEvent(true);
        } else {
            // ��ȡ��������
            $where = $this->getWhere();

            // ɾ����ǰģ������
            $result = $this->db()
                ->where($where)
                ->removeOption('soft_delete')
                ->delete();

            $this->lazySave(false);
        }

        // ����ɾ��
        if (!empty($this->relationWrite)) {
            $this->autoRelationDelete();
        }

        $this->trigger('AfterDelete');

        $this->exists(false);

        return true;
    }

    /**
     * ɾ����¼
     * @access public
     * @param  mixed $data �����б� ֧�ֱհ���ѯ����
     * @param  bool  $force �Ƿ�ǿ��ɾ��
     * @return bool
     */
    public static function destroy($data, bool $force = false): bool
    {
        // ������ɾ������
        $query = (new static())->db(false);

        if (is_array($data) && key($data) !== 0) {
            $query->where($data);
            $data = null;
        } elseif ($data instanceof \Closure) {
            call_user_func_array($data, [ & $query]);
            $data = null;
        } elseif (is_null($data)) {
            return false;
        }

        $resultSet = $query->select($data);

        foreach ($resultSet as $result) {
            $result->force($force)->delete();
        }

        return true;
    }

    /**
     * �ָ�����ɾ���ļ�¼
     * @access public
     * @param  array $where ��������
     * @return bool
     */
    public function restore($where = []): bool
    {
        $name = $this->getDeleteTimeField();

        if (!$name || false === $this->trigger('BeforeRestore')) {
            return false;
        }

        if (empty($where)) {
            $pk = $this->getPk();
            if (is_string($pk)) {
                $where[] = [$pk, '=', $this->getData($pk)];
            }
        }

        // �ָ�ɾ��
        $this->db(false)
            ->where($where)
            ->useSoftDelete($name, $this->getWithTrashedExp())
            ->update([$name => $this->defaultSoftDelete]);

        $this->trigger('AfterRestore');

        return true;
    }

    /**
     * ��ȡ��ɾ���ֶ�
     * @access protected
     * @param  bool  $read �Ƿ��ѯ���� д������ʱ����Զ�ȥ�������
     * @return string|false
     */
    protected function getDeleteTimeField(bool $read = false)
    {
        $field = property_exists($this, 'deleteTime') && isset($this->deleteTime) ? $this->deleteTime : 'delete_time';

        if (false === $field) {
            return false;
        }

        if (false === strpos($field, '.')) {
            $field = '__TABLE__.' . $field;
        }

        if (!$read && strpos($field, '.')) {
            $array = explode('.', $field);
            $field = array_pop($array);
        }

        return $field;
    }

    /**
     * ��ѯ��ʱ��Ĭ���ų���ɾ������
     * @access protected
     * @param  Query  $query
     * @return void
     */
    protected function withNoTrashed(Query $query): void
    {
        $field = $this->getDeleteTimeField(true);

        if ($field) {
            $condition = is_null($this->defaultSoftDelete) ? ['null', ''] : ['=', $this->defaultSoftDelete];
            $query->useSoftDelete($field, $condition);
        }
    }
}
