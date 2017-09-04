<?php

namespace Lidsys\Football\Command;

use Lstr\Silex\App\AppAwareInterface;
use Lstr\Silex\App\AppAwareTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ImportScheduleCommand extends Command implements AppAwareInterface
{
    use AppAwareTrait;

    protected function configure()
    {
        $this
            ->setName('football:import-schedule')
            ->setDescription("Import the current year's schedule")
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $app     = $this->getSilexApplication();

        $app['lidsys.football.schedule-import']->importThirdPartySchedule(
            (date('n') >= 5 ? date('Y') : date('Y') - 1)
        );

        $output->writeln("done");
    }
}
