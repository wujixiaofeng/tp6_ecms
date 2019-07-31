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

namespace think;

use SplFileObject;

/**
 * �ļ��ϴ���
 */
class File extends SplFileObject
{
    /**
     * ������Ϣ
     * @var string|array
     */
    private $error = '';

    /**
     * ��ǰ�����ļ���
     * @var string
     */
    protected $filename;

    /**
     * �ϴ��ļ���
     * @var string
     */
    protected $saveName;

    /**
     * �ϴ��ļ���������
     * @var string|\Closure
     */
    protected $rule = 'date';

    /**
     * �ϴ��ļ���֤����
     * @var array
     */
    protected $validate = [];

    /**
     * �Ƿ�Ԫ����
     * @var bool
     */
    protected $isTest;

    /**
     * �ϴ��ļ���Ϣ
     * @var array
     */
    protected $info = [];

    /**
     * �ļ�hash����
     * @var array
     */
    protected $hash = [];

    public function __construct(string $filename, string $mode = 'r')
    {
        parent::__construct($filename, $mode);

        $this->filename = $this->getRealPath() ?: $this->getPathname();
    }

    /**
     * �Ƿ����
     * @access public
     * @param  bool   $test �Ƿ����
     * @return $this
     */
    public function isTest(bool $test = false)
    {
        $this->isTest = $test;

        return $this;
    }

    /**
     * �����ϴ���Ϣ
     * @access public
     * @param  array   $info �ϴ��ļ���Ϣ
     * @return $this
     */
    public function setUploadInfo(array $info)
    {
        $this->info = $info;

        return $this;
    }

    /**
     * ��ȡ�ϴ��ļ�����Ϣ
     * @access public
     * @return array
     */
    public function getInfo(): array
    {
        return $this->info;
    }

    /**
     * ��ȡ�ϴ��ļ���name
     * @access public
     * @return string
     */
    public function getName(): string
    {
        return $this->info['name'] ?? '';
    }

    /**
     * ��ȡ�ϴ��ļ����ļ���
     * @access public
     * @return string
     */
    public function getSaveName(): string
    {
        return $this->saveName;
    }

    /**
     * �����ϴ��ļ��ı����ļ���
     * @access public
     * @param  string   $saveName
     * @return $this
     */
    public function setSaveName(string $saveName)
    {
        $this->saveName = $saveName;

        return $this;
    }

    /**
     * ��ȡ�ļ��Ĺ�ϣɢ��ֵ
     * @access public
     * @param  string $type
     * @return string
     */
    public function hash(string $type = 'sha1'): string
    {
        if (!isset($this->hash[$type])) {
            $this->hash[$type] = hash_file($type, $this->filename);
        }

        return $this->hash[$type];
    }

    /**
     * ���Ŀ¼�Ƿ��д
     * @access protected
     * @param  string   $path    Ŀ¼
     * @return bool
     */
    protected function checkPath(string $path): bool
    {
        if (is_dir($path)) {
            return true;
        }

        try {
            $result = mkdir($path, 0755, true);
        } catch (\Exception $e) {
            // ����ʧ��
            $result = false;
        }

        if ($result) {
            return true;
        }

        $this->error = ['directory {:path} creation failed', ['path' => $path]];
        return false;
    }

    /**
     * ��ȡ�ļ�������Ϣ
     * @access public
     * @return string
     */
    public function getMime(): string
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);

        return finfo_file($finfo, $this->filename);
    }

    /**
     * �����ļ�����������
     * @access public
     * @param  mixed   $rule    �ļ���������
     * @return $this
     */
    public function rule($rule)
    {
        $this->rule = $rule;

        return $this;
    }

    /**
     * �����ϴ��ļ�����֤����
     * @access public
     * @param  array   $rule    ��֤����
     * @return $this
     */
    public function validate(array $rule = [])
    {
        $this->validate = $rule;

        return $this;
    }

    /**
     * ����Ƿ�Ϸ����ϴ��ļ�
     * @access public
     * @return bool
     */
    public function isValid(): bool
    {
        if ($this->isTest) {
            return is_file($this->filename);
        }

        return is_uploaded_file($this->filename);
    }

    /**
     * ����ϴ��ļ�
     * @access public
     * @param  array   $rule    ��֤����
     * @return bool
     */
    public function check(array $rule = []): bool
    {
        $rule = $rule ?: $this->validate;

        if ((isset($rule['size']) && !$this->checkSize($rule['size']))
            || (isset($rule['type']) && !$this->checkMime($rule['type']))
            || (isset($rule['ext']) && !$this->checkExt($rule['ext']))
            || !$this->checkImg()) {
            return false;
        }

        return true;
    }

    /**
     * ����ϴ��ļ���׺
     * @access public
     * @param  array|string   $ext    �����׺
     * @return bool
     */
    public function checkExt($ext): bool
    {
        if (is_string($ext)) {
            $ext = explode(',', $ext);
        }

        $extension = strtolower(pathinfo($this->getName(), PATHINFO_EXTENSION));

        if (!in_array($extension, $ext)) {
            $this->error = 'extensions to upload is not allowed';
            return false;
        }

        return true;
    }

    /**
     * ���ͼ���ļ�
     * @access public
     * @return bool
     */
    public function checkImg(): bool
    {
        $extension = strtolower(pathinfo($this->getName(), PATHINFO_EXTENSION));

        /* ��ͼ���ļ������ϸ��� */
        if (in_array($extension, ['gif', 'jpg', 'jpeg', 'bmp', 'png', 'swf']) && !in_array($this->getImageType($this->filename), [1, 2, 3, 4, 6, 13])) {
            $this->error = 'illegal image files';
            return false;
        }

        return true;
    }

    // �ж�ͼ������
    protected function getImageType(string $image)
    {
        if (function_exists('exif_imagetype')) {
            return exif_imagetype($image);
        }

        try {
            $info = getimagesize($image);
            return $info ? $info[2] : false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * ����ϴ��ļ���С
     * @access public
     * @param  integer   $size    ����С
     * @return bool
     */
    public function checkSize(int $size): bool
    {
        if ($this->getSize() > $size) {
            $this->error = 'filesize not match';
            return false;
        }

        return true;
    }

    /**
     * ����ϴ��ļ�����
     * @access public
     * @param  array|string   $mime    ��������
     * @return bool
     */
    public function checkMime($mime): bool
    {
        if (is_string($mime)) {
            $mime = explode(',', $mime);
        }

        if (!in_array(strtolower($this->getMime()), $mime)) {
            $this->error = 'mimetype to upload is not allowed';
            return false;
        }

        return true;
    }

    /**
     * �ƶ��ļ�
     * @access public
     * @param  string           $path    ����·��
     * @param  string|bool      $savename    ������ļ��� Ĭ���Զ�����
     * @param  boolean          $replace ͬ���ļ��Ƿ񸲸�
     * @return false|File       false-ʧ�� ���򷵻�Fileʵ��
     */
    public function move(string $path, $savename = true, bool $replace = true)
    {
        // �ļ��ϴ�ʧ�ܣ�����������
        if (!empty($this->info['error'])) {
            $this->error($this->info['error']);
            return false;
        }

        // ���Ϸ���
        if (!$this->isValid()) {
            $this->error = 'upload illegal files';
            return false;
        }

        // ��֤�ϴ�
        if (!$this->check()) {
            return false;
        }

        $path = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        // �ļ�������������
        $saveName = $this->buildSaveName($savename);
        $filename = $path . $saveName;

        // ���Ŀ¼
        if (false === $this->checkPath(dirname($filename))) {
            return false;
        }

        /* ������ͬ���ļ� */
        if (!$replace && is_file($filename)) {
            $this->error = ['has the same filename: {:filename}', ['filename' => $filename]];
            return false;
        }

        /* �ƶ��ļ� */
        if ($this->isTest) {
            rename($this->filename, $filename);
        } elseif (!move_uploaded_file($this->filename, $filename)) {
            $this->error = 'upload write error';
            return false;
        }

        // ���� File����ʵ��
        $file = new self($filename);
        $file->setSaveName($saveName);
        $file->setUploadInfo($this->info);

        return $file;
    }

    /**
     * ��ȡ�����ļ���
     * @access protected
     * @param  string|bool   $savename    ������ļ��� Ĭ���Զ�����
     * @return string
     */
    protected function buildSaveName($savename): string
    {
        if (true === $savename) {
            // �Զ������ļ���
            $savename = $this->autoBuildName();
        } elseif ('' === $savename || false === $savename) {
            // ����ԭ�ļ���
            $savename = $this->getName();
        }

        if (!strpos($savename, '.')) {
            $savename .= '.' . pathinfo($this->getName(), PATHINFO_EXTENSION);
        }

        return $savename;
    }

    /**
     * �Զ������ļ���
     * @access protected
     * @return string
     */
    protected function autoBuildName(): string
    {
        if ($this->rule instanceof \Closure) {
            $savename = call_user_func_array($this->rule, [$this]);
        } else {
            switch ($this->rule) {
                case 'date':
                    $savename = date('Ymd') . DIRECTORY_SEPARATOR . md5((string) microtime(true));
                    break;
                default:
                    if (in_array($this->rule, hash_algos())) {
                        $hash     = $this->hash($this->rule);
                        $savename = substr($hash, 0, 2) . DIRECTORY_SEPARATOR . substr($hash, 2);
                    } elseif (is_callable($this->rule)) {
                        $savename = call_user_func($this->rule);
                    } else {
                        $savename = date('Ymd') . DIRECTORY_SEPARATOR . md5((string) microtime(true));
                    }
            }
        }

        return $savename;
    }

    /**
     * ��ȡ���������Ϣ
     * @access private
     * @param  int $errorNo  �����
     */
    private function error(int $errorNo): void
    {
        switch ($errorNo) {
            case 1:
            case 2:
                $this->error = 'upload File size exceeds the maximum value';
                break;
            case 3:
                $this->error = 'only the portion of file is uploaded';
                break;
            case 4:
                $this->error = 'no file to uploaded';
                break;
            case 6:
                $this->error = 'upload temp dir not found';
                break;
            case 7:
                $this->error = 'file write error';
                break;
            default:
                $this->error = 'unknown upload error';
        }
    }

    /**
     * ��ȡ������Ϣ��֧�ֶ����ԣ�
     * @access public
     * @return string
     */
    public function getError(): string
    {
        $lang = Container::pull('lang');

        if (is_array($this->error)) {
            list($msg, $vars) = $this->error;
        } else {
            $msg  = $this->error;
            $vars = [];
        }

        return $lang->has($msg) ? $lang->get($msg, $vars) : $msg;
    }

    public function __call($method, $args)
    {
        return $this->hash($method);
    }
}
