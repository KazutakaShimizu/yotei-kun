<?php

namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class BaseCommand extends ContainerAwareCommand
{

    abstract protected function executeCommand(InputInterface $input, OutputInterface $output);

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $logger = $this->getContainer()->get('logger');
        $logger->info($this->getName()." start");

        try {
            $this->executeCommand($input, $output);
        } catch (\Exception $e) {
            $logger->critical($e->getMessage()." LineNumber:".$e->getLine()." FileName:".$e->getFile(). "\n Stacktrace: ".$e->getTraceAsString());
        }

        $logger->info($this->getName()." end");
    }
}
