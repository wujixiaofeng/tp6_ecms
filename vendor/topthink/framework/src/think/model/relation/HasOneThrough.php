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

namespace think\model\relation;

use Closure;
use think\App;
use think\Model;

/**
 * Զ��һ��һ������
 */
class HasOneThrough extends HasManyThrough
{

    /**
     * �ӳٻ�ȡ��������
     * @access public
     * @param  array   $subRelation �ӹ�����
     * @param  Closure $closure     �հ���ѯ����
     * @return Model
     */
    public function getRelation(array $subRelation = [], Closure $closure = null)
    {
        if ($closure) {
            $closure($this);
        }

        $this->baseQuery();

        $relationModel = $this->query->relation($subRelation)->find();

        if ($relationModel) {
            $relationModel->setParent(clone $this->parent);
        }

        return $relationModel;
    }

    /**
     * Ԥ���������ѯ�����ݼ���
     * @access protected
     * @param  array   $resultSet   ���ݼ�
     * @param  string  $relation    ��ǰ������
     * @param  array   $subRelation �ӹ�����
     * @param  Closure $closure     �հ�
     * @return void
     */
    public function eagerlyResultSet(array &$resultSet, string $relation, array $subRelation = [], Closure $closure = null): void
    {
        $localKey   = $this->localKey;
        $foreignKey = $this->foreignKey;

        $range = [];
        foreach ($resultSet as $result) {
            // ��ȡ��������б�
            if (isset($result->$localKey)) {
                $range[] = $result->$localKey;
            }
        }

        if (!empty($range)) {
            $this->query->removeWhereField($foreignKey);

            $data = $this->eagerlyWhere([
                [$this->foreignKey, 'in', $range],
            ], $foreignKey, $relation, $subRelation, $closure);

            // ����������
            $attr = App::parseName($relation);

            // �������ݷ�װ
            foreach ($resultSet as $result) {
                // ����ģ��
                if (!isset($data[$result->$localKey])) {
                    $relationModel = null;
                } else {
                    $relationModel = $data[$result->$localKey];
                    $relationModel->setParent(clone $result);
                    $relationModel->exists(true);
                }

                // ���ù�������
                $result->setRelation($attr, $relationModel);
            }
        }
    }

    /**
     * Ԥ���������ѯ�����ݣ�
     * @access protected
     * @param  Model   $result      ���ݶ���
     * @param  string  $relation    ��ǰ������
     * @param  array   $subRelation �ӹ�����
     * @param  Closure $closure     �հ�
     * @return void
     */
    public function eagerlyResult(Model $result, string $relation, array $subRelation = [], Closure $closure = null): void
    {
        $localKey   = $this->localKey;
        $foreignKey = $this->foreignKey;

        $this->query->removeWhereField($foreignKey);

        $data = $this->eagerlyWhere([
            [$foreignKey, '=', $result->$localKey],
        ], $foreignKey, $relation, $subRelation, $closure);

        // ����ģ��
        if (!isset($data[$result->$localKey])) {
            $relationModel = null;
        } else {
            $relationModel = $data[$result->$localKey];
            $relationModel->setParent(clone $result);
            $relationModel->exists(true);
        }

        $result->setRelation(App::parseName($relation), $relationModel);
    }

    /**
     * ����ģ��Ԥ��ѯ
     * @access public
     * @param  array   $where       ����Ԥ��ѯ����
     * @param  string  $key         ��������
     * @param  string  $relation    ������
     * @param  array   $subRelation �ӹ���
     * @param  Closure $closure
     * @return array
     */
    protected function eagerlyWhere(array $where, string $key, string $relation, array $subRelation = [], Closure $closure = null): array
    {
        // Ԥ���������ѯ ֧��Ƕ��Ԥ����
        $keys = $this->through->where($where)->column($this->throughPk, $this->foreignKey);

        if ($closure) {
            $closure($this);
        }

        $list = $this->query->where($this->throughKey, 'in', $keys)->select();

        // ��װģ������
        $data = [];
        $keys = array_flip($keys);

        foreach ($list as $set) {
            $data[$keys[$set->{$this->throughKey}]] = $set;
        }

        return $data;
    }

}
