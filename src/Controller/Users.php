<?php

namespace App\Controller;

class Users
{
    public function run()
    {
        $db = \App\Service\DB::get();
        $stmt = $db->prepare("
        SELECT
            *
        FROM
            `users`
        ");
        $stmt->execute();


        $view = new \App\View\Users();
        $view->render([
            'title' => 'Пользователи',
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
                    `users` (
                        `email`,
                        `password`,
                        `name`,
                        `privilege`
                    ) VALUES (
                        :email,
                        :password,
                        :name,
                        :privilege
                    )
                ");
                $stmt->execute([
                    ':email' => $_POST['email'],
                    ':password' => sha1($_POST['password']),
                    ':name' => $_POST['name'],
                    ':privilege' => $_POST['privilege'],
                ]);
                header('Location: /users'); 
                return;
        }
        $view = new \App\View\Users\Form();
        $view->render([
            'title' => 'Создание нового пользователя',
            'data' => $_POST,
            'messages' => $validator->getMessages(),
        ]);
    }

    public function runUpdate()
    {
        if (!isset($_GET['id'])) {
            header('Location: /users');
            return;
        }

        $pdo = \App\Service\DB::get();
        $stmt = $pdo->prepare("
            SELECT
                *
            FROM
                `users`
            WHERE
                `id` = :uid
        ");
        $stmt->execute([
            ':uid' => $_GET['id']
        ]);

        if (! $user = $stmt->fetch()) {
            header('Location: /users');
            return;
        }

        $validator = $this->getValidator(true);
        if ($_POST && $validator->check($_POST)) {
            if ($_POST['password'] == '') {
                $stmt = $pdo->prepare("
                    UPDATE
                        `users`
                    SET
                        `email` = :email,
                        `name` = :name,
                        `privilege` = :privilege
                    WHERE
                        `id` = :id
                    ");
                $stmt->execute([
                    ':email' => $_POST['email'],
                    ':name' => $_POST['name'],
                    ':privilege' => $_POST['privilege'],
                    ':id' => $_GET['id'],
                ]);
            } else {
                $stmt = $pdo->prepare("
                    UPDATE
                        `users`
                    SET
                        `email` = :email,
                        `name` = :name,
                        `password` = :password,
                        `privilege` = :privilege
                    WHERE
                        `id` = :id
                    ");
                $stmt->execute([
                    ':email' => $_POST['email'],
                    ':name' => $_POST['name'],
                    ':password' => sha1($_POST['password']),
                    ':privilege' => $_POST['privilege'],
                    ':id' => $_GET['id'],
                ]);
            }
          
            header ('Location: /users');
            return;
        }

        $view = new \App\View\Users\Form();
        $view->render([
            'title' => 'Редактирование пользователя',
            'data' => $user,
            'messages' => $validator->getMessages(),
        ]);
    }

    public function runDelete()
    {
        $pdo = \App\Service\DB::get();

        if (isset($_POST['id'])) {
            $stmt = $pdo->prepare("DELETE FROM `users` WHERE `id` = :uid");
            $stmt->execute([
                'uid' => $_POST['id']
            ]);
            header('Location: /users');
            return;
        }

        if (! isset($_GET['id'])) {
            header('Location: /users');
            return;
        }

       
        $stmt = $pdo->prepare("
            SELECT
                *
            FROM
                `users`
            WHERE
                `id` = :id
        ");
        $stmt->execute([
            ':id' => $_GET['id']
        ]);

        if (! $user = $stmt->fetch()) {
            header('Location: /users');
            return;
        }

        $view = new \App\View\Users\DeleteForm();
        $view->render([
            'title' => 'Удаление пользователя',
            'user' => $user,
            'url' => [
                'approve' => '/users/delete',
                'cancel' => '/users',
            ]
        ]);
    }

    private function getValidator($isUpdate = false)
    {
        $validator = new \App\Service\Validator();
            $validator
                ->setRule('email', function($value){
                    return ! is_null($value) && mb_strlen($value) > 0;
                }, 'Это поле обязательное') 
                ->setRule('email', function($value){
                    return preg_match('/^[^@]+@[^@]+$/', $value);
                }, 'Неправильный адрес электронной почты')    
                ->setRule('name', function($value){
                    return preg_match('/.{2,50}/', $value);
                }, 'Некорректно заполнено поле "Фамилия, Имя"') 
                ->setRule('privilege', function($value){
                    return in_array((int)$value, [0, 1]);
                }, 'Неверное значение привилегии')
                ->setRule('confirm-password', function($value, $data){
                    return isset($data['password']) && $data['password'] === $value;
                }, 'Пароль не соответствует введенному ранее');
           
            if ($isUpdate) {
                $validator->setRule('password', function($value){
                    return $value == '' || preg_match('/.{8,100}/', $value);
                }, 'Пароль должен быть длиною от 8 до 100 символов');
            } else {
                $validator->setRule('password', function($value){
                    return preg_match('/.{8,100}/', $value);
                }, 'Пароль должен быть длиною от 8 до 100 символов'); 
            }

            return $validator;
    }
}
