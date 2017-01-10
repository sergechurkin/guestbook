<?php

namespace guestbook;

use guestbook\Controller;
/* **ext** */
use sergechurkin\cform\cForm;
use sergechurkin\cform\Captcha;
 
class Model {
    public $url;
    public $page;
    public $action;
    public $error;
    public $user;
    private $id;
    private $grcaptcha;
    private $entry_on_page  = 5; // количество записей на странице
    private $max_pages_list = 5; // сколько номеров страниц показывать
    private $count_entrys   = 0; // количество записей всего
    private $current_page   = 1; // текущая страница
/** @var  \PDO */
    private $pdo = null;
    private $host = 'localhost';
    private $database = 'yii2basic';
    private $username = 'root';
    private $password = '';
    private $fields = [];
    
    public function connect() {
        try {
            $this->pdo = new \PDO( "mysql:dbname=$this->database;host=$this->host",$this->username,$this->password);
        } catch (Exception $e) {
            throw new \RuntimeException($e->getMessage());
        }
    }    
    public function validate() {
        $error = false;
        foreach ($this->fields as $key => $field) {
            $this->fields[$key][5] = trim((string) filter_input(INPUT_POST, $field[2]));
            if (empty($this->fields[$key][5])) {
                $error = true;
                $this->fields[$key][6] = 'Значение поля не должно быть пустым';
            }
        }
        return $error;
    }
    
    public function chkPassword($n, $pw) {
        if ($n == 'admin' && $pw == 'admin') {
            $error = false;
        } else {
            $error = true;
        }
        return $error;
    }

    public function setProp() {
        $this->id        = (int)filter_input(INPUT_GET, 'id');
        $this->grcaptcha = (string)filter_input(INPUT_COOKIE, 'captcha');
        if (!file_exists('./tmp')) {
            mkdir('./tmp');
        }
        if (file_exists((string) filter_input(INPUT_COOKIE, 'captchafile'))) {
            unlink((string) filter_input(INPUT_COOKIE, 'captchafile'));
        }
        setcookie("captcha", '');
        setcookie("captchafile", '');
        $this->current_page = (string) filter_input(INPUT_GET, 'current_page'); 
        if (empty($this->current_page)) {
            $this->current_page = 1;
        }
    }
    /*
     * Создание шапки
     */
    public function createForm() {
        $cform = new cform();
        $cform->bldHeader('Гостевая книга');
        return $cform;
    }
    /*
     * Создание меню с отмеченным первым пунктом
     */
    public function createDefaultMenu($cform) {
        $cform->bldMenu([['Главная', 'active', './'],
                         ['Ввести сообщение', '', './?page=formcreate&action=create']],
                         [(!empty($this->user)) ? 'Выйти (' .
                             $this->user . ')' : 'Войти',
                             '', 
                             (!empty($this->user)) ? './?page=logout' : './?page=formlogin&action=login']);
    }
    /*
     * Просмотр записей в таблице
     */
    public function createBrw($cform) {
        $this->fields = [];
        $this->fields[] = ['#', 'id',];
        $this->fields[] = ['Имя', 'name',];
        $this->fields[] = ['Эл.почта', 'email',];
        $this->fields[] = ['Тема', 'subject',];
        $this->fields[] = ['Текст', 'body',];
        $buttons[] = ['Создать запись', 'button', 'btn btn-success', './?page=home&action=create'];
        $buttons[] = ['Отмена', 'button', 'btn btn-default', './'];
        $this->connect();
        $sql = 'SELECT COUNT(*) as count FROM `tst_gbook`';
        $result = $this->pdo->prepare($sql);
        $result->execute(); 
        $this->count_entrys = $result->fetchColumn(); 
        $nfirstrec = ($this->current_page - 1) * $this->entry_on_page;
        $sql = 'SELECT * FROM `tst_gbook` LIMIT ' . $this->entry_on_page . ' OFFSET ' . $nfirstrec;
        $i = 0;
        foreach ($this->pdo->query($sql) as $row) {
            $i++;
            $r[] = [$row['id'],
                ((strlen($row['name']) > 20) ? ( mb_substr($row['name'], 0, 20) . '...') : ($row['name'])),
                $row['email'],
                ((strlen($row['subject']) > 20) ? (mb_substr($row['subject'], 0, 20) . '...') : ($row['subject'])),
                ((strlen($row['body']) > 50) ? (mb_substr($row['body'], 0, 50) . '...') : ($row['body'])),
                '<a href="' . './?page=formcreate&action=view&id=' . $row['id'] . '" title="Просмотр сообщения" aria-label="Просмотр" data-pjax="0"><span class="glyphicon glyphicon-eye-open"></span></a>',
            ];
        }
        $this->pdo = null; // Close connectuin
        $cform->bldTable('Гостевая книга', 'Список сообщений', 12, $this->fields, $r, $buttons, 
                         $this->count_entrys, $this->entry_on_page, $this->current_page, $this->max_pages_list,
                         ($nfirstrec + 1), ($nfirstrec + $i));
    }
    /*
     * Просмотр одной записи
     */
    public function createView($cform) {
        $this->connect();
        $sql = 'SELECT * FROM `tst_gbook` WHERE id=' . $this->id;
        $result = $this->pdo->prepare($sql);
        $result->execute(); 
        $row = $result->fetch($this->pdo::FETCH_ASSOC); 
        $this->fields[] = ['Имя пользователя:', 3, 'name', 9, 'text', $row['name'], '', 'readonly="readonly"'];
        $this->fields[] = ['Адрес электронной почты:', 3, 'email', 9, 'text', $row['email'], '', 'readonly="readonly"'];
        $this->fields[] = ['Заголовок сообщения:', 3, 'subject', 9, 'text', $row['subject'], '', 'readonly="readonly"'];
        $this->fields[] = ['Текст сообщения:', 3, 'body', 9, 'textarea', $row['body'], '', 'readonly="readonly"'];
        $this->pdo = null; // Close connectuin
        if ($this->user == 'admin') {
            $buttons[] = ['Удалить', 'button', 'btn btn-danger', './?page=formcreate&action=delete&id=' . $this->id];
        }
        $buttons[] = ['Вернуться', 'button', 'btn btn-default', './'];
        $cform->bldMenu([['Главная', 'active', './'], 
                         ['Ввести сообщение', '', './?page=formcreate&action=create']], 
                         [(!empty($this->user)) ? 'Выйти (' . $this->user . ')' : 'Войти', '', (!empty($this->user)) ? './?page=logout' : './?page=formlogin&action=login']);
        $cform->bldForm('Гостевая книга', 'Просмотр сообщения', 10, $this->fields, $buttons);
    }
    /*
     * Ввод записи
     */
    public function actionCreate($cform) {
        $this->fields[] = ['Имя пользователя:', 3, 'name', 9, 'text', '', '', ''];
        $this->fields[] = ['Адрес электронной почты:', 3, 'email', 9, 'text', '', '', ''];
        $this->fields[] = ['Заголовок сообщения:', 3, 'subject', 9, 'text', '', '', ''];
        $this->fields[] = ['Текст сообщения:', 3, 'body', 9, 'textarea', '', '', ''];
        $this->fields[] = [['Введите:', ''], 3, 'captcha', 9, 'text', ' ', '', ''];
        $buttons[] = ['Сохранить', 'submit', 'btn btn-success', ''];
        $buttons[] = ['Вернуться', 'button', 'btn btn-default', './'];
        if ($this->action == 'validate') {
            $this->error = $this->validate();
            if (!filter_var($this->fields[1][5], FILTER_VALIDATE_EMAIL) && !empty($this->fields[1][5])) {
                $this->error = true;
                $this->fields[1][6] = 'E-mail (' . $this->fields[1][5] . ') задан некорректно.';
            }
            if ($this->grcaptcha !== $this->fields[4][5] && !empty($this->fields[4][5])) {
                $this->error = true;
                $this->fields[4][6] = 'Неверно введены символы капчи';
            }
        }
        if ($this->action == 'create' || $this->error == true) {
            $captcha = new captcha();
            $arrcaptcha = $captcha->generatecaptcha();
            $captcha = null;
            setcookie("captcha", $arrcaptcha[0]);
            setcookie("captchafile", $arrcaptcha[1]);
            $this->fields[4][0][1] = ' <img src=\'' . $arrcaptcha[1] . '\'/> ';
            $cform->bldMenu([['Главная', '', './'], ['Ввести сообщение', 'active', './?page=formcreate&action=create']], [(!empty($this->user)) ? 'Выйти (' . $this->user . ')' : 'Войти', '', (!empty($this->user)) ? './?page=logout' : './?page=formlogin&action=login']);
            $cform->bldForm('Гостевая книга', 'Ввод сообщения', 10, $this->fields, $buttons);
        } else { // Ошибок нет 
            $this->action = '';
            $cform->bldMenu([['Главная', 'active', './'], ['Ввести сообщение', '', './?page=formcreate&action=create']], [(!empty($this->user)) ? 'Выйти (' . $this->user . ')' : 'Войти', '', (!empty($this->user)) ? './?page=logout' : './?page=formlogin&action=login']);
            $this->connect();
            $sql = "INSERT INTO `tst_gbook`(`name`, `email`, `subject`, `body`) VALUES ('" . $this->fields[0][5] . "','" . $this->fields[1][5] . "','" . $this->fields[2][5] . "', '" . $this->fields[3][5] . "')";
            $result = $this->pdo->prepare($sql);
            $result->execute();
            $this->pdo = null; // Close connectuin
        }
    }    
    /*
     * Форма регистрации
     */
    public function createFormLogin($cform) {
        $this->fields[] = ['Имя', 2, 'name', 10, 'text', '', '', '',];
        $this->fields[] = ['Пароль', 2, 'password', 10, 'password', '', '', '',];
        $buttons[] = ['Войти', 'submit', 'btn btn-success', ''];
        $buttons[] = ['Отмена', 'button', 'btn btn-default', './'];
        if ($this->action == 'validate') {
            $this->error = $this->validate();
            if (!$this->error) {
                $this->error = $this->chkPassword($this->fields[0][5], $this->fields[1][5]);
                if ($this->error) {
                    $this->fields[1][6] = 'Неверные имя/пароль';
                }    
            }
        }
        if ($this->action == 'login' || $this->error == true) {
            $cform->bldMenu([['Главная', '', './'], ['Ввести сообщение', '', './?page=formcreate&action=create']], [(!empty($this->user)) ? 'Выйти (' . $this->user . ')' : 'Войти', 'active', (!empty($this->user)) ? './?page=logout' : './?page=formlogin&action=login']);
            $cform->bldForm('Гостевая книга', 'Регистрация пользователя', 4, $this->fields, $buttons);
        } else { // Ошибок нет 
            setcookie('user', 'admin', time() + 3600);
            $this->action = '';
            $this->user = 'admin';
            $cform->bldMenu([['Главная', 'active', './'], ['Ввести сообщение', '', './?page=formcreate&action=create']], [(!empty($this->user)) ? 'Выйти (' . $this->user . ')' : 'Войти', '', (!empty($this->user)) ? './?page=logout' : './?page=formlogin&action=login']);
        }
    }
    /*
     * Удаление записи
     */
    public function actionDelete($cform) {
        if (!empty($this->id)) {
            $this->connect();
            $sql = "DELETE FROM `tst_gbook` WHERE `id` = " . $this->id;
            $result = $this->pdo->prepare($sql);
            $result->execute();
            $this->pdo = null; // Close connectuin
        }
        $this->action = '';
        $cform->bldMenu([['Главная', 'active', './'], ['Ввести сообщение', '', './?page=formcreate&action=create']], [(!empty($this->user)) ? 'Выйти (' . $this->user . ')' : 'Войти', '', (!empty($this->user)) ? './?page=logout' : './?page=formlogin&action=login']);
    }
}
