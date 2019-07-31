# think-template

����XML�ͱ�ǩ��ı�����ģ������

## ��Ҫ����

- ֧��XML��ǩ�����ͨ��ǩ�Ļ�϶��壻
- ֧��ֱ��ʹ��PHP������д��
- ֧���ļ�������
- ֧�ֶ༶��ǩǶ�ף�
- ֧�ֲ���ģ�幦�ܣ�
- һ�α��������У����������Ч�ʷǳ��ߣ�
- ģ���ļ��Ͳ���ģ����£��Զ�����ģ�建�棻
- ϵͳ�������踳ֱֵ�������
- ֧�ֶ�ά����Ŀ��������
- ֧��ģ�������Ĭ��ֵ��
- ֧��ҳ�����ȥ��Html�հף�
- ֧�ֱ�����ϵ������͸�ʽ�����ܣ�
- ������ģ����ú����ͽ���PHP�﷨��
- ͨ����ǩ�ⷽʽ��չ��

## ��װ

~~~php
composer require topthink/think-template
~~~

## �÷�ʾ��

�ڸ�Ŀ¼�´���index.php����ļ����ԣ�
~~~php
<?php
namespace think;

require __DIR__.'/vendor/autoload.php';

// ����ģ���������
$config = [
	'view_path'	=>	'./template/',
	'cache_path'	=>	'./runtime/',
	'view_suffix'   =>	'html',
];

$template = new Template($config);
// ģ�������ֵ
$template->assign('name','think');
// ��ȡģ���ļ���Ⱦ���
$template->fetch('index');
// ����ģ���ļ���Ⱦ
$template->fetch('./template/test.php');
// ��Ⱦ�������
$template->display($content);
~~~

��ϸ�÷��ο�[����](https://www.kancloud.cn/manual/thinkphp5_1/354069)