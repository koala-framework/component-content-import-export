<?php
namespace ComponentContentImportExport;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ImportCommand extends Command
{
    protected function configure()
    {
        $this->setName('import')
             ->setDescription('TBD');
        $this->addOption('subrootId', 's', InputOption::VALUE_REQUIRED,
            'Defines component subroot for import. Components of another subroot will not be imported');
        $this->addOption('listImportedComponentIds', 'l', InputOption::VALUE_OPTIONAL,
            'List all successfully imported componentIds.', false);

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        $errOutput = $output instanceof ConsoleOutputInterface ? $output->getErrorOutput() : $output;

        if (!($subrootId = $input->getOption('subrootId'))) {
            throw new \RuntimeException("no subrootId provided.");
        }

        if (0 === ftell(STDIN)) {
            $contents = '';
            while (!feof(STDIN)) {
                $contents .= fread(STDIN, 1024);
            }
        } else {
            throw new \RuntimeException("Please pipe content to STDIN.");
        }

        $dataByComponentId = array();
        $contents = explode("\n", $contents);
        foreach ($contents as $exportData) {
            if (preg_match('#([^ ]+):([^ ]+) (.*)#', $exportData, $m)) {
                $componentId = $m[1];
                if (!isset($dataByComponentId[$componentId])) $dataByComponentId[$componentId] = array();
                $dataByComponentId[$componentId][$m[2]] = str_replace("\\n", "\n", $m[3]);
            }
        }

        $importedComponentIds = array();
        $componentCount = count($dataByComponentId);
        $counter = 1;
        foreach ($dataByComponentId as $componentId=>$data) {
            $cmpData = array();
            $genData = array();
            foreach ($data as $k=>$i) {
                if (substr($k, 0, 4) == 'gen_') {
                    $genData[substr($k, 4)] = $i;
                } else {
                    $cmpData[$k] = $i;
                }
            }
            $cmp = \Kwf_Component_Data_Root::getInstance()->getComponentByDbId($componentId, array('ignoreVisible' => true, 'limit'=>1));
            if ($cmp && $cmp->getSubroot()->componentId == $subrootId) {
                $errOutput->writeln("<info>Importing $componentId</info>");
                if ($cmpData) {
                    \Kwc_Abstract_Admin::getInstance($cmp->componentClass)->importContent($cmp, $cmpData);
                    $importedComponentIds[] = $componentId;
                }
                if ($genData) {
                    $cmp->generator->importContent($cmp, $genData);
                }
                if ($errOutput->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
                    $errOutput->writeln("<info>Component " . $counter . " of " . $componentCount . " imported</info>");
                }
            } else {
                if ($cmp && $cmp->getSubroot()->componentId != $subrootId) {
                    $errOutput->writeln("<error>$componentId not in subroot ({$cmp->getSubroot()->componentId})</error>");
                } else {
                    $errOutput->writeln("<error>$componentId not found</error>");
                }
            }
            \Kwf_Component_Data_Root::getInstance()->freeMemory();
            if ($errOutput->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
                $errOutput->writeln("<info>" . round(memory_get_usage() / 1024 / 1024) . "MB memory usage</info>");
            }
            $counter++;
        }

        if ($input->hasOption('listImportedComponentIds')) {
            $output->writeln('-- listImportedComponentIds');
            foreach ($importedComponentIds as $componentId) {
                $output->writeln($componentId);
            }
        }
    }
}
