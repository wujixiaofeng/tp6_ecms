# think-view

ThinkPHP6.0 Think-Templateģ����������


## ��װ

~~~php
composer require topthink/think-view
~~~

## �÷�ʾ��

����չ���ܵ���ʹ�ã�����ThinkPHP6.0+

��������configĿ¼�µ�template.php�����ļ���Ȼ����԰���������÷�ʹ�á�

~~~php

use think\facade\View;

// ģ�������ֵ����Ⱦ���
View::assign(['name' => 'think'])
	// �������
	->filter(function($content){
		return str_replace('search', 'replace', $content);
	})
	// ��ȡģ���ļ���Ⱦ���
	->fetch('index');


// ����ʹ�����ֺ���
view('index', ['name' => 'think']);
~~~

�����ģ������������ο�think-template�⡣