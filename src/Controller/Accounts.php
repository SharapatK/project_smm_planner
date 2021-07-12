<?php

namespace App\Controller;

class Accounts
{
    public function run()
    {
        $db = \App\Service\DB::get();
        $stmt = $db->prepare("
        SELECT
            *
        FROM
            `accounts`
        ");
        $stmt->execute();


        $view = new \App\View\Accounts();
        $view->render([
            'title' => 'Instagram аккаунты',
            'data' => $stmt->fetchAll(),
        ]);
    }

    public function runAdd()
    {
        $validator = $this->getValidator();
        if ($_POST && $validator->check($_POST)) {
            $db = \App\Service\DB:: get();
            $stmt = $db->prepare("
                INSERT INTO
                    `accounts` (
                        `login`,
                        `password`,
                        `id_user`
                    ) VALUES (
                        :login,
                        :password,
                        :id_user
                    )
                ");
                $stmt->execute([
                    ':login' => $_POST['login'],
                    ':password' => $_POST['password'],
                    ':id_user' => $_SESSION['auth']['id'],
                ]);
                header('Location: /accounts'); 
                return;
        }
        $view = new \App\View\Accounts\Form();
        $view->render([
            'title' => 'Добавление нового аккаунта',
            'data' => $_POST,
            'messages' => $validator->getMessages(),
        ]);
    }

    public function runUpdate()
    {
        if (!isset($_GET['id'])) {
            header('Location: /accounts');
            return;
        }

        $pdo = \App\Service\DB::get();
        $stmt = $pdo->prepare("
            SELECT
                *
            FROM
                `accounts`
            WHERE
                `id` = :uid AND `id_user` = :id_user
        ");
        $stmt->execute([
            ':uid' => $_GET['id'],
            ':id_user' => $_SESSION['auth']['id']
        ]);

        if (! $account = $stmt->fetch()) {
            header('Location: /accounts');
            return;
        }

        $validator = $this->getValidator(true);
        if ($_POST && $validator->check($_POST)) {
                $stmt = $pdo->prepare("
                    UPDATE
                        `accounts`
                    SET
                        `login` = :login,
                        `password` = :password
                    WHERE
                        `id` = :id AND `id_user` = :id_user
                    ");
                $stmt->execute([
                    ':login' => $_POST['login'],
                    ':password' => $_POST['password'],
                    ':id' => $_GET['id'],
                    ':id_user' => $_SESSION['auth']['id'],
                ]);
            header ('Location: /accounts');
            return;
        }

        $view = new \App\View\Accounts\Form();
        $view->render([
            'title' => 'Редактирование Instagram аккаунта',
            'data' => $account,
            'messages' => $validator->getMessages(),
        ]);
    }

    public function runDelete()
    {
        $pdo = \App\Service\DB::get();

        if (isset($_POST['id'])) {
            $stmt = $pdo->prepare("DELETE FROM `accounts` WHERE `id` = :uid AND `id_user` = :id_user");
            $stmt->execute([
                'uid' => $_POST['id'],
                ':id_user' => $_SESSION['auth']['id'],
            ]);
            header('Location: /accounts');
            return;
        }

        if (! isset($_GET['id'])) {
            header('Location: /accounts');
            return;
        }

       
        $stmt = $pdo->prepare("
            SELECT
                *
            FROM
                `accounts`
            WHERE
                `id` = :id AND `id_user` = :id_user
        ");
        $stmt->execute([
            ':id' => $_GET['id'],
            ':id_user' => $_SESSION['auth']['id'],
        ]);

        if (! $account = $stmt->fetch()) {
            header('Location: /accounts');
            return;
        }

        $view = new \App\View\Accounts\DeleteForm();
        $view->render([
            'title' => 'Удаление аккаунта',
            'account' => $account,
            'url' => [
                'approve' => '/accounts/delete',
                'cancel' => '/accounts',
            ]
        ]);
    }

    private function getValidator($isUpdate = false)
    {
        $validator = new \App\Service\Validator();
            $validator
                ->setRule('login', function($value){
                    return ! is_null($value) && mb_strlen($value) > 0;
                }, 'Это поле обязательное')
                ->setRule('password', function($value){
                    return ! is_null($value) && mb_strlen($value) > 0;
                }, 'Это поле обязательное');
               
           return $validator;
    }
}
