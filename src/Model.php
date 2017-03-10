<?php

namespace guestbook;

use guestbook\Controller;
/* **ext** */
use sergechurkin\cform\cForm;
use sergechurkin\cform\Captcha;
 
class Model {
    public $page;
    public $action;
    private $cform;
    private $user;
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
    private $fields  = [];
    private $buttons = [];
    
    public function setProp() {
        $this->user      = (string)filter_input(INPUT_COOKIE, 'user'); 
        $this->id        = (int)filter_input(INPUT_GET, 'id');
        if (!empty((string)filter_input(INPUT_POST, 'id'))) {
            $this->id = (string)filter_input(INPUT_POST, 'id');
        }
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
            if (!empty((string)filter_input(INPUT_COOKIE, 'current_page'))) {
                $this->current_page = (string)filter_input(INPUT_COOKIE, 'current_page');
            } else {
                $this->current_page = 1;
            }    
        } else {
            setcookie("current_page", $this->current_page);
        }
    }

    public function connect() {
        try {
            $this->pdo = new \PDO( "mysql:dbname=$this->database;host=$this->host",$this->username,$this->password);
        } catch (Exception $e) {
            throw new \RuntimeException($e->getMessage());
        }
    }    

    public function validateEmpty() {
        $rv = true;
        foreach ($this->fields as $key => $field) {
            $this->fields[$key][5] = trim((string) filter_input(INPUT_POST, $field[2]));
            if (empty($this->fields[$key][5]) && $this->fields[$key][7] !== 'hidden') {
                $rv = false;
                $this->fields[$key][6] = 'Значение поля не должно быть пустым';
            }
        }
        return $rv;
    }

    public function validateEmail($email, $num) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->fields[$num][6] = 'E-mail (' . $this->fields[$num][5] . ') задан некорректно.';
        }
        return empty($this->fields[$num][6]);
    }

    public function validateCaptcha($captcha, $num) {
        if ($this->grcaptcha !== $captcha) {
            $this->fields[$num][6] = 'Неверно введены символы капчи';
        }
        return empty($this->fields[$num][6]);
    }
    
    public function chkPassword($n, $pw) {
        if ($n !== 'admin' || $pw !== 'admin') {
            return false;
        } else {
            return true;
        }
    }

    /*
     * Создание шапки
     */
    public function createForm() {
        $this->cform = new cform();
        $this->cform->bldHeader('Гостевая книга');
    }
    /*
     * Отрисовка меню
     */
    public function bldMenu($num_select) {
        $arrmenu = [['Главная', '', './'],
                    ['Ввести сообщение', '', './?page=gb&action=create']];
        $arrmenur = [(!empty($this->user)) ? 'Выйти (' .
                        $this->user . ')' : 'Войти',
                        '', 
                        (!empty($this->user)) ? './?page=gb&action=logout' : './?page=login&action=create'];
        if ($num_select == -1) {
            $arrmenur[1] = 'active';
        } else {
            $arrmenu[$num_select][1] = 'active';
        }    
        $this->cform->bldMenu($arrmenu, $arrmenur);
    }
    private function cutField($str, $len) {
        return ((iconv_strlen($str) > $len) ? ( mb_substr($str, 0, $len) . '...') : ($str));
    }
    /*
     * Просмотр записей в таблице
     */
    public function gb_brw() {
        $this->bldMenu(0);
        $this->fields = [];
        $this->fields[] = '#';
        $this->fields[] = 'Имя';
        $this->fields[] = 'Эл.почта';
        $this->fields[] = 'Тема';
        $this->fields[] = 'Текст';
        $this->connect();
        $sql = 'SELECT COUNT(*) as count FROM `tst_gbook`';
        $result = $this->pdo->prepare($sql);
        $result->execute(); 
        $this->count_entrys = $result->fetchColumn(); 
        $max_count_pages = ceil($this->count_entrys / $this->entry_on_page);
        if ($this->current_page > $max_count_pages) {
            $this->current_page = $max_count_pages;
        }
        $nfirstrec = ($this->current_page - 1) * $this->entry_on_page;
        $sql = 'SELECT * FROM `tst_gbook` LIMIT ' . $this->entry_on_page . ' OFFSET ' . $nfirstrec;
        $i = 0;
        foreach ($this->pdo->query($sql) as $row) {
            $r[] = [$row['id'],
                $this->cutField($row['name'], 20),
                $row['email'],
                $this->cutField($row['subject'], 20),
                $this->cutField($row['body'], 50),
                '<a href="' . './?page=gb&action=view&id=' . $row['id'] . '" title="Просмотр сообщения" aria-label="Просмотр" data-pjax="0"><span class="glyphicon glyphicon-eye-open"></span></a>',
            ];
            if ($this->user == 'admin') {
                $r[$i][6] = '<a href="' . './?page=gb&action=update&id=' . $row['id'] . '" title="Изменение сообщения" aria-label="Изменение" data-pjax="0"><span class="glyphicon glyphicon-pencil"></span></a>';
                $r[$i][7] = '<a href="' . './?page=gb&action=delete&id=' . $row['id'] . '" title="Удалить сообщения" aria-label="Удалить" data-pjax="0"><span class="glyphicon glyphicon-trash"></span></a>';
            }
            $i++;
        }
        $this->pdo = null; // Close connectuin
        $this->cform->bldTable('Гостевая книга', 'Список сообщений', 12, $this->fields, $r, [], 
                         $this->count_entrys, $this->entry_on_page, $this->current_page, $this->max_pages_list,
                         ($nfirstrec + 1), ($nfirstrec + $i));
    }

    private function readMess($isview = false) {
        $this->connect();
        $sql = 'SELECT * FROM `tst_gbook` WHERE id=' . $this->id;
        $result = $this->pdo->prepare($sql);
        $result->execute();
        $row = $result->fetch($this->pdo::FETCH_ASSOC);
        $this->gb_fields($isview);
        $this->fields[0][5] = $row['name'];
        $this->fields[1][5] = $row['email'];
        $this->fields[2][5] = $row['subject'];
        $this->fields[3][5] = $row['body'];
        $this->fields[5][5] = $row['id'];
        $this->pdo = null; // Close connectuin
    }
    /*
     * Просмотр одной записи
     */
    public function gb_view() {
        $this->readMess(true);
        foreach ($this->fields as $key=>$field) {
            if ($key < 4) {
                $this->fields[$key][7] = 'readonly="readonly"';
            }
        }
        if ($this->user == 'admin') {
            $buttons[] = ['Удалить', 'button', 'btn btn-danger', './?page=gb&action=delete&id=' . $this->id];
        }
        $buttons[] = ['Вернуться', 'button', 'btn btn-default', './'];
        $this->bldMenu(0);
        $this->cform->bldForm('Гостевая книга', 'Просмотр сообщения', 10, $this->fields, $buttons);
    }
    /*
     * Ввод записи
     */
    private function gb_fields($isview = false) {
        $this->fields[] = ['Имя пользователя:', 3, 'name', 9, 'text', '', '', ''];
        $this->fields[] = ['Адрес электронной почты:', 3, 'email', 9, 'text', '', '', ''];
        $this->fields[] = ['Заголовок сообщения:', 3, 'subject', 9, 'text', '', '', ''];
        $this->fields[] = ['Текст сообщения:', 3, 'body', 9, 'textarea', '', '', ''];
        $this->fields[] = [['Введите:', ''], 3, 'captcha', 9, 'text', ' ', '', ($isview) ? 'hidden' : ''];
        $this->fields[] = ['', 3, 'id', 9, 'text', ' ', '', 'hidden'];
    }
    private function gb_buttons() {
        $this->buttons[] = ['Сохранить', 'submit', 'btn btn-success', ''];
        $this->buttons[] = ['Вернуться', 'button', 'btn btn-default', './'];
    }
    public function gb_create() {
        if (count($this->fields) == 0) {
            $this->gb_fields();
            $this->gb_buttons();
        }
        $captcha = new captcha();
        $arrcaptcha = $captcha->generatecaptcha();
        $captcha = null;
        setcookie("captcha", $arrcaptcha[0]);
        setcookie("captchafile", $arrcaptcha[1]);
        $this->fields[4][0][1] = ' <img src=\'' . $arrcaptcha[1] . '\'/> ';
        $this->bldMenu(1);
        $this->cform->bldForm('Гостевая книга', 'Ввод сообщения', 10, $this->fields, $this->buttons);
    }    
    /*
     * Изменение записи
     */
    public function gb_update() {
        $this->readMess();
        $this->gb_buttons();
        $this->gb_create();
    }

    /*
     * Валидация записи
     */
    public function gb_validate() {
        $this->gb_fields();
        $this->gb_buttons();
        if (!$this->validateEmpty()) {
            $this->gb_create();
        } else {
            if (!$this->validateEmail($this->fields[1][5], 1) || !$this->validateCaptcha($this->fields[4][5], 4)) {
                $this->gb_create();
            } else { // Ошибок нет 
                $this->connect();
                if (empty((int)$this->id)) {
                    $sql = "INSERT INTO `tst_gbook`(`name`, `email`, `subject`, `body`) VALUES ('" . 
                            $this->fields[0][5] . "','" . 
                            $this->fields[1][5] . "','" . 
                            $this->fields[2][5] . "', '" . 
                            $this->fields[3][5] . "')";
                } else {
                    $sql = "UPDATE `tst_gbook` SET " .
                            "`name` = '" . $this->fields[0][5] . "'" .
                            ", `email` = '" . $this->fields[1][5] . "'" .
                            ", `subject` = '" . $this->fields[2][5] . "'" .
                            ", `body` = '" . $this->fields[3][5] . "'" .
                            " WHERE id = " . $this->id;
                }
                $result = $this->pdo->prepare($sql);
                $result->execute();
                $this->pdo = null; // Close connectuin
                $this->gb_brw();
            }
        }
    }
    /*
     * Форма регистрации
     */
    private function login_fields() {
        $this->fields[] = ['Имя', 2, 'name', 10, 'text', '', '', '',];
        $this->fields[] = ['Пароль', 2, 'password', 10, 'password', '', '', '',];
        $this->buttons[] = ['Войти', 'submit', 'btn btn-success', ''];
        $this->buttons[] = ['Отмена', 'button', 'btn btn-default', './'];
    }    
    public function login_create() {
        if (count($this->fields) == 0) {
            $this->login_fields();
        }
        $this->bldMenu(-1);
        $this->cform->bldForm('Гостевая книга', 'Регистрация пользователя', 4, $this->fields, $this->buttons);
    }
    /*
     * Валидация пользователя
     */
    public function login_validate() {
        $this->login_fields();
        if (!$this->validateEmpty()) {
            $this->login_create();
        } else {
            if (!$this->chkPassword($this->fields[0][5], $this->fields[1][5])) {
                $this->fields[1][6] = 'Неверные имя/пароль';
                $this->login_create();
        } else { // Ошибок нет 
            setcookie('user', 'admin', time() + 3600);
            $this->user = 'admin';
            $this->page == 'gb';
            $this->action == 'brw';
            $this->gb_brw();
            }
        }
    }
    public function gb_logout() {
        setcookie("user", ''); 
        $this->user = '';
        $this->page = 'gb';
        $this->action = 'brw';
        $this->gb_brw();
    }
    /*
     * Удаление записи
     */
    public function gb_delete() {
        if (!empty($this->id)) {
            $this->connect();
            $sql = "DELETE FROM `tst_gbook` WHERE `id` = " . $this->id;
            $result = $this->pdo->prepare($sql);
            $result->execute();
            $this->pdo = null; // Close connectuin
        }
        $this->page = 'gb';
        $this->action = 'brw';
        $this->gb_brw();
    }
    public function closeForm() {
        $this->cform->bldFutter();
        $this->cform = null;
    }
}
