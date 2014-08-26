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
    private $substitutions;
    private $defaults;

    private $mailgun;

    public function __construct($key, $domain, array $options = array())
    {
        $substitutions = $defaults = array();
        extract($options, EXTR_IF_EXISTS);

        $this->key           = $key;
        $this->domain        = $domain;

        $this->substitutions = $substitutions;
        $this->defaults      = $defaults;
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
        $data = array_replace_recursive(
            $this->defaults,
            $data
        );

        $this->substituteString($data, 'text');
        $this->substituteString($data, 'html');

        return $this->getMailgun()->sendMessage($this->domain, $data);
    }

    private function substituteString(array & $data, $field)
    {
        if (!empty($data[$field])) {
            $data[$field] = str_replace(
                array_keys($this->substitutions),
                array_values($this->substitutions),
                $data[$field]
            );
        }
    }
}
