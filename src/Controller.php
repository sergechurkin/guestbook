<?php

namespace guestbook;

use guestbook\Model;
 
class Controller {
/*
 * sergechurkin/guestbook
*/
    public function run() {
        $model = new Model();
        $model->url    = (string)filter_input(INPUT_SERVER , 'REQUEST_URI');
        $model->page   = (string)filter_input(INPUT_GET, 'page');
        $model->action = (string)filter_input(INPUT_GET, 'action');
        $model->user   = (string)filter_input(INPUT_COOKIE, 'user'); //
        if (!empty((string)filter_input(INPUT_POST, 'action'))) {
            $model->action = (string)filter_input(INPUT_POST, 'action');
        }
        $model->error = false;
        $model->setProp();
        if ($model->page == 'logout') {
            setcookie("user", ''); 
            $model->user = '';
            $model->page == 'home';
        }
        $cform = $model->createForm();
        if ($model->page == 'formcreate') {
            if ($model->action == 'view') {
                $model->createView($cform);
            }
            elseif ($model->action == 'create' || $model->action == 'validate') {
                $model->actionCreate($cform);
            }
            if ($model->action == 'delete') {
                $model->actionDelete($cform);
            }
        }
        elseif ($model->page == 'formlogin') {
            $model->createFormLogin($cform);
        } else {
            $model->createDefaultMenu($cform);
        }
        if (empty($model->action) && !$model->error) {
            $model->createBrw($cform);
        }                               
    }                               
}

