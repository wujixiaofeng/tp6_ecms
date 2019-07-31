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

use think\Exception;

/**
 * �ֹ���
 */
trait OptimLock
{
    protected function getOptimLockField()
    {
        return property_exists($this, 'optimLock') && isset($this->optimLock) ? $this->optimLock : 'lock_version';
    }

    /**
     * ���ݼ��
     * @access protected
     * @return void
     */
    protected function checkData(): void
    {
        $this->isExists() ? $this->updateLockVersion() : $this->recordLockVersion();
    }

    /**
     * ��¼�ֹ���
     * @access protected
     * @return void
     */
    protected function recordLockVersion(): void
    {
        $optimLock = $this->getOptimLockField();

        if ($optimLock) {
            $this->set($optimLock, 0);
        }
    }

    /**
     * �����ֹ���
     * @access protected
     * @return void
     */
    protected function updateLockVersion(): void
    {
        $optimLock = $this->getOptimLockField();

        if ($optimLock && $lockVer = $this->getOrigin($optimLock)) {
            // �����ֹ���
            $this->set($optimLock, $lockVer + 1);
        }
    }

    public function getWhere()
    {
        $where     = parent::getWhere();
        $optimLock = $this->getOptimLockField();

        if ($optimLock && $lockVer = $this->getOrigin($optimLock)) {
            $where[] = [$optimLock, '=', $lockVer];
        }

        return $where;
    }

    protected function checkResult($result): void
    {
        if (!$result) {
            throw new Exception('record has update');
        }
    }

}
