<?php
/*
 * Lightdatasys web site source code
 *
 * Copyright Matt Light <matt.light@lightdatasys.com>
 *
 * For copyright and licensing information, please view the LICENSE
 * that is distributed with this source code.
 */

namespace Lidsys\Application\Service;

use Mailgun\Mailgun;
use Silex\Application;

class MailerService
{
    private $key;
    private $domain;

    private $mailgun;

    public function __construct($key, $domain)
    {
        $this->key    = $key;
        $this->domain = $domain;
    }

    private function getMailgun()
    {
        if ($this->mailgun) {
            return $this->mailgun;
        }

        $this->mailgun = new Mailgun($this->key);

        return $this->mailgun;
    }

    public function sendMessage(array $data)
    {
        return $this->getMailgun()->sendMessage($this->domain, $data);
    }
}
