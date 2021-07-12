<?php

namespace App\View;

class Users extends Main
{
    public function content(array $data)
    {
        ?>
            <div class="row">                
                <div class="col-lg-12">
                <div class="block">
                <div class="block-header">
                                    <div class="block-options">
                                       <a class="btn btn-info push-5-r push-10" href="/users/add"><i class="fa fa-plus"></i></a>

                                    </div>
                                </div>
                                <div class="block-content">
                                <?php $this->table($this->getColumns(), $data['data']); ?>
                                </div>
                            </div>

                    
                    
                </div>
            </div>
        <?php
    }

    private function getColumns()
    {
        return [
            'id' => [
                'label' => '#',
                'class' => 'text-center',
                'style' => 'width: 50px;'
            ],
            'email' => [
                'label' => 'Email пользователя',
                'class' => '',
                'style' => ''
            ],
            'name' => [
                'label' => 'Имя пользователя',
                'class' => '',
                'style' => ''
            ],
            'privilege' => [
                'label' => 'Привилегия',
                'class' => '',
                'style' => ''
            ],
            'table-action' => [
                'label' => 'Действие',
                'class' => 'text-center',
                'style' => 'width: 200px;',
                'buttons' => [
                    'update' => [
                        'icon' => 'fa fa-pencil',
                        'url' => '/users/update',
                    ],
                    'delete' => [
                        'icon' => 'fa fa-trash',
                        'url' => '/users/delete',
                    ],
                ]
            ],
        ];
    }
}