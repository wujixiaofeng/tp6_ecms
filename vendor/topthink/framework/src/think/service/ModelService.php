<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2019 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: yunwuxin <448901948@qq.com>
// +----------------------------------------------------------------------
declare (strict_types = 1);

namespace think\service;

use think\Model;
use think\Service;

/**
 * ģ�ͷ�����
 */
class ModelService extends Service
{
    public function boot()
    {
        Model::maker(function (Model $model) {
            $db = $this->app->db;
            $model->setDb($db);
            $model->setEvent($this->app->event);

            $isAutoWriteTimestamp = $model->getAutoWriteTimestamp();

            if (is_null($isAutoWriteTimestamp)) {
                // �Զ�д��ʱ���
                $model->isAutoWriteTimestamp($db->getConfig('auto_timestamp'));
            }

            $dateFormat = $model->getDateFormat();

            if (is_null($dateFormat)) {
                // ����ʱ�����ʽ
                $model->setDateFormat($db->getConfig('datetime_format'));
            }

            $connection = $model->getConnection();

            if (!empty($connection) && is_array($connection)) {
                // ����ģ�͵����ݿ�����
                $model->setConnection(array_merge($db->getConfig(), $connection));
            }

        });
    }
}
