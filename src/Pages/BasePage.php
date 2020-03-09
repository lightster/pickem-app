<?php

namespace App\Pages;

use App\Models\User;

abstract class BasePage extends FwPage
{
    protected function before()
    {
        $this->handleTurbolinksRedirects();

        $this->setLayout('default_layout.phtml');
        if ($this->isLoggedIn()) {
            $this->set('user', [
                'username' => $this->getUser()->getData('username'),
            ]);
        }
    }

    protected function getUser()
    {
        return User::find($this->getSessionParam('user_id'));
    }

    protected function isLoggedIn()
    {
        return (bool) $this->getSessionParam('user_id');
    }
}
