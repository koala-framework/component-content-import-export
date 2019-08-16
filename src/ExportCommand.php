<?php
namespace ComponentContentImportExport;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

ini_set('max_execution_time', 0);

class ExportCommand extends Command
{
    protected function configure()
    {
        $this->setName('export')
            ->addOption('component-id', null, InputOption::VALUE_REQUIRED, 'Component ID where the export should start.', 'root')
            ->addOption('addInvisibleChildComponents', 'aiccmp', InputOption::VALUE_NONE, 'Include all invisible child-components.')
            ->addOption('isTrl', 't', InputOption::VALUE_NONE, 'Use export to translate for another language (skips invisible pages).')
            ->setDescription('Export Component Content');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $errOutput = $output instanceof ConsoleOutputInterface ? $output->getErrorOutput() : $output;

        $queueFile = 'temp/componentContentExportQueue';

        $componentId  = $input->getOption('component-id');
        file_put_contents($queueFile, $componentId);

        while (true) {
            $cmd = "php vendor/bin/component-content-import-export export:worker";
            if ($input->getOption('addInvisibleChildComponents')) {
                $cmd .= " --addInvisibleChildComponents";
            }
            if ($input->getOption('isTrl')) {
                $cmd .= " --isTrl";
            }
            $process = new Process($cmd);

            $process->setTimeout(null);
            $this->getHelper('process')->mustRun($output, $process, 'export:worker failed', function ($type, $data) use ($output, $errOutput) {
                if (Process::ERR === $type) {
                    $errOutput->write($data);
                } else {
                    $output->write($data);
                }
            });

            if (!file_get_contents($queueFile)) {
                break;
            }
            $errOutput->writeln("starting a new process...");
        }
    }
}
