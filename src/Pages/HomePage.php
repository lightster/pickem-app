<?php

namespace App\Pages;

class HomePage extends BasePage
{
    public function invoke()
    {
        $this->set('title', 'Pickem');
        $this->render('home.phtml');
    }
}
