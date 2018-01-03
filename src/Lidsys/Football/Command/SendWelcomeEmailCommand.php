<?php

namespace Lidsys\Football\Command;

use DateInterval;
use DateTime;

use Lstr\Silex\App\AppAwareInterface;
use Lstr\Silex\App\AppAwareTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SendWelcomeEmailCommand extends Command implements AppAwareInterface
{
    use AppAwareTrait;

    protected function configure()
    {
        $this
            ->setName('football:send-welcome')
            ->setDescription('Send welcome emails')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $app     = $this->getSilexApplication();

        $one_week_from_now = new DateTime();
        $one_week_from_now->add(DateInterval::createFromDateString('7 days'));
        $count = $app['lidsys.football.notification']
            ->sendWelcomeEmailForDate($one_week_from_now);

        if ($count) {
            $output->writeln("done sending {$count} emails");
        } else {
            $output->writeln("no emails sents");
        }
    }
}
