<?php

namespace guestbook;

use guestbook\Model;
 
class Controller {
/*
 * sergechurkin/guestbook
*/
    public function run() {
        $model = new Model();
        $model->page   = (string)filter_input(INPUT_GET, 'page');
        if (empty($model->page)) {
            $model->page = 'gb';
        }
        $model->action = (string)filter_input(INPUT_GET, 'action');
        if (empty($model->action)) {
            $model->action = 'brw';
        }
        if (!empty((string)filter_input(INPUT_POST, 'action'))) {
            $model->action = (string)filter_input(INPUT_POST, 'action');
        }
        $model->setProp();
        $model->createForm();
        /* Возможен вызов следующих методов:
         * gb_brw
         * gb_view
         * gb_create
         * gb_validate
         * gb_create
         * gb_update
         * gb_logout
         * login_create
         * login_validate
         */
        $method = $model->page . '_' . $model->action;
        if(method_exists($model, $method)) {
            $model->$method ();
        }   else {
            throw new \RuntimeException('Вызван не существуующий метод ' . $method . ' в классе Model');
        }                               
        $model->closeForm();
    }                               
}
