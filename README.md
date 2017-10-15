# guestbook

В тестовом приложении "Гостевая книга", написанном на PHP по схеме MVC, использована библиотека
[sergechurkin/cform](https://github.com/sergechurkin/cform),
которая подключена как расширение. При установке с помощью
[composer](http://getcomposer.org/download/)
формируется ватозагрузчик библиотеки. Приложение зарегистрировано на
[packagist](https://packagist.org/packages/sergechurkin/guestbook).

## Установка


```
composer create-project sergechurkin/guestbook  path "1.1.x-dev"
```

Параметры подключения к базе MySQL заданы в /src/Model.php:

```
    private $host     = 'localhost';
    private $database = 'yii2basic';
    private $username = 'root';
    private $password = '';
```

Требуется создать таблицу tst_gbook скриптом gbook.sql.

## Описание

При запуске приложения выводится таблица, содержащая колонки с полями книги. 
Каждое сообщение можно просмотреть, нажав на иконки в правой части строк таблицы. 
Новое сообщение можно ввести, выбрав пункт меню "Ввести сообщение". Если нажать на 
кнопку "Вход администратора", появится форма входа, где надо задать 
имя и пароль (admin/admin). Тогда в правой части строк таблицы появятся иконки,
позволяющие редактировать и удалять записи, в форме просмотра сообщений появится 
кнопка "Удалить", которая позволит администратору удалять сообщения. 

Запустить приложение можно [по ссылке](http://sergechurkin-guestbook.vacau.com/).
