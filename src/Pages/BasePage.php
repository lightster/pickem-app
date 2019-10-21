<?php

namespace App\Pages;

abstract class BasePage extends FwPage
{
    protected function before()
    {
        $this->handleTurbolinksRedirects();

        $this->setLayout('default_layout.phtml');
    }
}
