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
        $substitutions = $defaults = $overrides = array();
        extract($options, EXTR_IF_EXISTS);

        $this->key           = $key;
        $this->domain        = $domain;

        $this->substitutions = $substitutions;
        $this->defaults      = $defaults;
        $this->overrides     = $overrides;
    }

    private function getMailgun()
    {
        if ($this->mailgun) {
            return $this->mailgun;
        }

        $this->mailgun = new Mailgun($this->key, 'bourne.r34d.me', 'v2', false);

        return $this->mailgun;
    }

    public function sendMessage(array $data, array $local_subs = array())
    {
        $data = array_replace_recursive(
            $this->defaults,
            $data,
            $this->overrides
        );

        $substitutions = array_replace_recursive(
            $this->substitutions,
            $local_subs
        );

        $this->substituteString($data, 'text', $substitutions);
        $this->substituteString($data, 'html', $substitutions);

        return $this->getMailgun()->sendMessage($this->domain, $data);
    }

    private function substituteString(array & $data, $field, $substitutions)
    {
        if (!empty($data[$field])) {
            $data[$field] = str_replace(
                array_keys($substitutions),
                array_values($substitutions),
                $data[$field]
            );
        }
    }
}
