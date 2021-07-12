<?php

namespace App\Controller;

class Tasks
{
    public function run()
    {
        $db = \App\Service\DB::get();
        $stmt = $db->prepare("
        SELECT
            `tasks`.*,
            `accounts`.`login`
        FROM
            `tasks`
        LEFT JOIN
            `accounts`
            ON `tasks`.`id_account` = `accounts`.`id`
        ");
        $stmt->execute();


        $view = new \App\View\Tasks();
        $view->render([
            'title' => 'Задачи на публикацию',
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
                    `tasks` (
                        `id_user`,
                        `id_account`,
                        `title`,
                        `description`,
                        `date_plan`
                    ) VALUES (
                        :idu,
                        :ida,
                        :title,
                        :desc,
                        :dplan
                    )
            ");



                $stmt->execute([
                    ':idu' => $_SESSION['auth']['id'],
                    ':ida' => $_POST['id_account'],
                    ':title' => $_POST['title'],
                    ':desc' => $_POST['description'],
                    ':dplan' => $this->formatDateTime($_POST['date_plan']),

                ]);

                header('Location: /tasks'); 
                return;

        }

        $view = new \App\View\Tasks\Form();
        $view->render([
            'title' => 'Добавление нового аккаунта',
            'data' => $_POST,
            'messages' => $validator->getMessages(),
            'accounts' => $this->getUserAccounts(),
        ]);
    }

    public function runUpdate()
    {
        if (! isset($_GET['id'])) {
            header('Location: /tasks');
            return;
        }

        $pdo = \App\Service\DB::get();
        $stmt = $pdo->prepare("
            SELECT
                *
            FROM
                `tasks`
            WHERE
                `id` = :idt AND `id_user` = :idu
        ");
        $stmt->execute([
            ':idt' => $_GET['id'],
            ':idu' => $_SESSION['auth']['id']
        ]);

        if (! $task = $stmt->fetch()) {
            header('Location: /tasks');
            return;
        }

     

        $validator = $this->getValidator(true);
        if ($_POST && $validator->check($_POST)) {
                $stmt = $pdo->prepare("
                    UPDATE
                        `tasks`
                    SET
                        `id_account` = :ida,
                        `title` = :title,
                        `description` = :desc,
                        `date_plan` = :dplan
                    WHERE
                        `id` = :id AND `id_user` = :idu
                    ");
                $stmt->execute([
                    ':ida' => $_POST['id_account'],
                    ':title' => $_POST['title'],
                    ':desc' => $_POST['description'],
                    ':dplan' => $this->formatDateTime($_POST['date_plan']),
                    ':id' => $_GET['id'],
                    ':idu' => $_SESSION['auth']['id'],
                ]);

            header ('Location: /tasks');
            return;
        }

        $view = new \App\View\Tasks\Form();
        $view->render([
            'title' => 'Редактирование задачи',
            'data' => $task,
            'messages' => $validator->getMessages(),
            'accounts' => $this->getUserAccounts(),

        ]);
    }

    public function runDelete()
    {
        $pdo = \App\Service\DB::get();

        if (isset($_POST['id'])) {
            $stmt = $pdo->prepare("DELETE FROM `tasks` WHERE `id` = :idt AND `id_user` = :idu");
            $stmt->execute([
                'idt' => $_POST['id'],
                ':idu' => $_SESSION['auth']['id'],
            ]);
            header('Location: /tasks');
            return;
        }

        if (! isset($_GET['id'])) {
            header('Location: /tasks');
            return;
        }

       
        $stmt = $pdo->prepare("
            SELECT
                *
            FROM
                `tasks`
            WHERE
                `id` = :id AND `id_user` = :id_user
        ");
        $stmt->execute([
            ':id' => $_GET['id'],
            ':id_user' => $_SESSION['auth']['id'],
        ]);

        if (! $task = $stmt->fetch()) {
            header('Location: /tasks');
            return;
        }

        $view = new \App\View\Tasks\DeleteForm();
        $view->render([
            'title' => 'Удаление задачи',
            'task' => $task,
            'url' => [
                'approve' => '/tasks/delete',
                'cancel' => '/tasks',
            ]
        ]);
    }

    private function getValidator($isUpdate = false)
    {
        $validator = new \App\Service\Validator();
            $validator
                ->setRule('id_account', function($value){
                    $userAccounts = $this->getUserAccounts();
                    $accountsID = [];
                    foreach ($userAccounts as $account) {
                        $accountsID[] = $account['id'];
                    }

                    return ! is_null($value) && in_array($value, $accountsID);
                }, 'Неверный аккаунт')
                ->setRule('date_plan', function($value){
                    return ! is_null($value) && preg_match('/^[0-9]{2}\.[0-9]{2}\.[0-9]{4} [0-9]{2}\:[0-9]{2}/', $value);
                }, 'Это поле обязательное и должно соответствовать формату ДД.ММ.ГГГГ чч:мм')
                ->setRule('title', function($value){
                    return ! is_null($value) && mb_strlen($value) > 0;
                }, 'Это поле обязательное')
                ->setRule('description', function($value){
                    return ! is_null($value) && mb_strlen($value) > 0;
                }, 'Это поле обязательное');

           return $validator;
    }

    private function getUserAccounts()
    {
        $db = \App\Service\DB::get();
        $stmt = $db->prepare("
            SELECT
                *
            FROM
                `accounts`
            WHERE
                `id_user` = :idu
        ");
        
        $stmt->execute([
            ':idu' => $_SESSION['auth']['id']
        ]);
        
        return $stmt->fetchAll();
    }

    private function formatDateTime($datetime)
    {
        $datePlan = new \DateTime($_POST['date_plan']);
        return $datePlan->format('Y-m-d H:i:s');
    }
}
