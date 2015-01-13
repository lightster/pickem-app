<?php

namespace Lidsys\Football\Command;

use DateTime;
use Exception;

use Lstr\Silex\App\AppAwareInterface;
use Lstr\Silex\App\AppAwareTrait;
use Silex\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SendReminderCommand extends Command implements AppAwareInterface
{
    use AppAwareTrait;

    protected function configure()
    {
        $this
            ->setName('football:send-reminder')
            ->setDescription('Send weekly reminder email')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $app     = $this->getSilexApplication();

        $now = new DateTime();
        $count = $app['lidsys.football.notification']
            ->sendReminderEmailForDate($now);

        if ($count) {
            $output->writeln("done sending {$count} emails");
        } else {
            $output->writeln("no emails sents");
        }
    }
}
