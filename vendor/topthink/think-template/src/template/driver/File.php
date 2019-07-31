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

namespace think\template\driver;

use Exception;

class File
{
    protected $cacheFile;

    /**
     * д����뻺��
     * @access public
     * @param  string $cacheFile ������ļ���
     * @param  string $content ���������
     * @return void
     */
    public function write(string $cacheFile, string $content): void
    {
        // ���ģ��Ŀ¼
        $dir = dirname($cacheFile);

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        // ����ģ�建���ļ�
        if (false === file_put_contents($cacheFile, $content)) {
            throw new Exception('cache write error:' . $cacheFile, 11602);
        }
    }

    /**
     * ��ȡ�������
     * @access public
     * @param  string  $cacheFile ������ļ���
     * @param  array   $vars ��������
     * @return void
     */
    public function read(string $cacheFile, array $vars = []): void
    {
        $this->cacheFile = $cacheFile;

        if (!empty($vars) && is_array($vars)) {
            // ģ�����б����ֽ��Ϊ��������
            extract($vars, EXTR_OVERWRITE);
        }

        //����ģ�滺���ļ�
        include $this->cacheFile;
    }

    /**
     * �����뻺���Ƿ���Ч
     * @access public
     * @param  string  $cacheFile ������ļ���
     * @param  int     $cacheTime ����ʱ��
     * @return bool
     */
    public function check(string $cacheFile, int $cacheTime): bool
    {
        // �����ļ�������, ֱ�ӷ���false
        if (!file_exists($cacheFile)) {
            return false;
        }

        if (0 != $cacheTime && time() > filemtime($cacheFile) + $cacheTime) {
            // �����Ƿ�����Ч��
            return false;
        }

        return true;
    }
}
