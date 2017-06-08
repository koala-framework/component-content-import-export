<?php
namespace ComponentContentImportExport;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ImportCommand extends Command
{
    protected function configure()
    {
        $this->setName('import')
            ->setDescription('TBD');

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // ...
    }
}