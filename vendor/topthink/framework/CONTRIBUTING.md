��ι����ҵ�Դ����
===

���ĵ������� ThinkPHP �Ŷӵ�����Լ���ת���ƣ����ύ�Ĵ��뽫�� ThinkPHP ��Ŀ����ʲô�ô����Լ���β��ܼ������ǵ����С�

## ͨ�� Github ���״���

ThinkPHP Ŀǰʹ�� Git �����Ƴ���汾���������Ϊ ThinkPHP ����Դ���룬���ȴ����˽� Git ��ʹ�÷���������Ŀǰ����Ŀ�й��� GitHub �ϣ��κ� GitHub �û������������ǹ��״��롣

����ķ�ʽ�ܼ򵥣�`fork`һ�� ThinkPHP �Ĵ��뵽��Ĳֿ��У��޸ĺ��ύ���������Ƿ���`pull request`���룬���ǻἰʱ�Դ��������鲢����������벢�����ͨ������Ĵ��뽫��`merge`�����ǵĲֿ��У�������ͻ��Զ������ڹ������������ˣ��ǳ����㡣

����ϣ���㹱�׵Ĵ�����ϣ�

* ThinkPHP �ı���淶
* �ʵ���ע�ͣ����������˶���
* ��ѭ Apache2 ��ԴЭ��

**�����Ҫ�˽����ϸ�ڻ����κ����ʣ�������Ķ����������**

### ע������

* ����Ŀ�����ʽ����׼ѡ�� [**PSR-2**](http://www.kancloud.cn/thinkphp/php-fig-psr/3141)��
* ���������ļ�����ѭ [**PSR-4**](http://www.kancloud.cn/thinkphp/php-fig-psr/3144)��
* ���� Issues �Ĵ�����ʹ������ `fix #xxx(Issue ID)` �� commit title ֱ�ӹر� issue��
* ϵͳ���Զ��� PHP 5.4 5.5 5.6 7.0 �� HHVM �ϲ����޸ģ����� HHVM �µĲ�����������ȷ������޸ķ��� PHP 5.4 ~ 5.6 �� PHP 7.0 ���﷨�淶��
* ����Ա����ϲ���� CI faild ���޸ģ������� CI faild �����Լ���Դ������޸���Ӧ��[��Ԫ�����ļ�](tests)��

## GitHub Issue

GitHub �ṩ�� Issue ���ܣ��ù��ܿ������ڣ�

* ��� bug
* ������ܸĽ�
* ����ʹ������

�ù��ܲ�Ӧ�����ڣ�

 * ����޸�������漰�����������޶�׷�����⣩
 * �����Ƶ�����

## �����޸�

**GitHub �ṩ�˿��ٱ༭�ļ��Ĺ���**

1. ��¼ GitHub �ʺţ�
2. �����Ŀ�ļ����ҵ�Ҫ�����޸ĵ��ļ���
3. ������Ͻ�Ǧ��ͼ������޸ģ�
4. ��д `Commit changes` ������ݣ�Title �����
5. �ύ�޸ģ��ȴ� CI ��֤�͹���Ա�ϲ���

**������Ҫһ���ύ�����޸ģ�������Ķ����������**

## ��������

1. `fork`����Ŀ��
2. ��¡(`clone`)�� `fork` ����Ŀ�����أ�
3. �½���֧(`branch`)�����(`checkout`)�·�֧��
4. ��ӱ���Ŀ����ı��� git �ֿ���Ϊ����(`upstream`)��
5. �����޸ģ�������޸İ���������������������ǵ��޸�[��Ԫ�����ļ�](tests)��
6. ������ܺ� `rebase`����ķ�֧������ master ��֧��
7. `push` ��ı��زֿ⵽ GitHub��
8. �ύ `pull request`��
9. �ȴ� CI ��֤������ͨ�����ظ� 5~7��GitHub ���Զ�������� `pull request`����
10. �ȴ�����Ա��������ʱ `rebase` ��ķ�֧������ master ��֧�������� master ��֧���޸ģ���

*���б�Ҫ������ `git push -f` ǿ������ rebase ��ķ�֧���Լ��� `fork`*

*���Բ�����ʹ�� `git push -f` ǿ�������޸ĵ�����*

### ע������

* ���������������κβ�����ĵط�������� GIT �̳̣��� [���](http://backlogtool.com/git-guide/cn/)��
* ���ڴ���**��ͬ����**���޸ģ������Լ� `fork` ����Ŀ��**������ͬ�ķ�֧**��ԭ��μ�`��������`��9����ע���֣���
* ���������ʽ��������μ� [Git ����ʽ���](http://pakchoi.me/2015/03/17/git-interactive-rebase/)

## �Ƽ���Դ

### ��������

* XAMPP for Windows 5.5.x
* WampServer (for Windows)
* upupw Apache PHP5.4 ( for Windows)

�����а�װ

- Apache / Nginx
- PHP 5.4 ~ 5.6
- MySQL / MariaDB

*Windows �û��Ƽ���� PHP bin Ŀ¼�� PATH������ʹ�� composer*

*Linux �û��������û����� Mac �û��Ƽ�ʹ������ Apache ��� Homebrew ��װ PHP �� MariaDB*

### �༭��

Sublime Text 3 + phpfmt ���

phpfmt �������

```json
{
	"autocomplete": true,
	"enable_auto_align": true,
	"format_on_save": true,
	"indent_with_space": true,
	"psr1_naming": false,
	"psr2": true,
	"version": 4
}
```

������ �༭�� / IDE ��� PSR2 �Զ���ʽ������

### Git GUI

* SourceTree
* GitHub Desktop

������ Git ͼ�ν���ͻ���
