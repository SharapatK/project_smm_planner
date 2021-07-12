<?php

namespace App\Controller;

class Login
{
    public function run()
    {
        $message = null;
        if(isset($_POST['email'], $_POST['password'])) {
            $pdo = \App\Service\DB::get();
            $stmt = $pdo->prepare("
                SELECT
                    *
                FROM
                    `users`
                WHERE
                    `email` = :email AND `password` = :password
            ");
            $stmt->execute([
                ':email' => $_POST['email'],
                ':password' => sha1($_POST['password']),
            ]);
            
            if($user = $stmt->fetch()) {
               $_SESSION['auth'] = $user;
               header('Location: /');
               return;
            } else {
                $message = 'Вы ввели неверные данные, пожалуйста перепроверьте и попробуйте снова.';
            }
        }

        $view = new \App\View\Login();
        $view->render([
            'title' => 'Авторизация',
            'message' => $message
        ]);
    }

    public function runLogout()
    {
        unset($_SESSION['auth']);
        header('Location: /');
    }
}