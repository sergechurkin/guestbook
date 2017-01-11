# guestbook

� �������� ���������� "�������� �����" ������������ ����������
[sergechurkin/cform](https://github.com/sergechurkin/cform),
������� ���������� ��� ����������. ��� ��������� � �������
[composer](http://getcomposer.org/download/)
����������� ������������� ����������. ���������� ���������������� ��
[packagist](https://packagist.org/packages/sergechurkin/guestbook).

## ���������


```
composer create-project sergechurkin/guestbook  path "1.1.x-dev"
```

��������� ����������� � ���� MySQL ������ � /src/Model.php:

```
    private $host     = 'localhost';
    private $database = 'yii2basic';
    private $username = 'root';
    private $password = '';
```

��������� ������� ������� tst_gbook �������� gbook.sql.

## ��������

��� ������� ���������� ��������� �������, ���������� ������� � ������ �����. 
������ ��������� ����� �����������, ����� �� ������ � ������ ����� ����� �������. 
����� ��������� ����� ������, ����� ������ "������ ���������". ���� ������ �� 
������ "���� ��������������", �������� ����� �����, ��� ���� ������ 
��� � ������ (admin/admin). ����� � ����� ��������� ��������� �������� ������ 
"�������", ������� �������� �������������� ������� ���������. 
