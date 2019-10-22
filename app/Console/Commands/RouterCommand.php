<?php

namespace App\Console\Commands;

use App\Libraries\WampServer\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class RouterCommand extends Command
{

    protected function configure()
    {
        $this
            ->setName('wamp:router:start')
            ->addArgument('loop', InputArgument::OPTIONAL, 'Use event loop - default true')
            ->setDescription('Start Thruway WAMP router.')
            ->setHelp('Start the default Thruway WAMP router')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $output->writeln('Start Thruway WAMP router');
        $useLoop = (bool)($input->getArgument('loop') ? $input->getArgument('loop') : true);

        Application::startServer($useLoop);
    }
}
