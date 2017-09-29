<?php
namespace ComponentContentImportExport;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
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
        set_time_limit(0);
        ini_set('memory_limit', '512M');

        $errOutput = $output instanceof ConsoleOutputInterface ? $output->getErrorOutput() : $output;

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
            if ($cmp) {
                $errOutput->writeln("<info>Importing $componentId</info>");
                if ($cmpData) {
                    \Kwc_Abstract_Admin::getInstance($cmp->componentClass)->importContent($cmp, $cmpData);
                }
                if ($genData) {
                    $cmp->generator->importContent($cmp, $genData);
                }
            } else {
                $errOutput->writeln("<error>$componentId not found</error>");
            }
            \Kwf_Component_Data_Root::getInstance()->freeMemory();
            if ($errOutput->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
                $errOutput->writeln("<info>" . round(memory_get_usage() / 1024 / 1024) . "MB memory usage</info>");
                $errOutput->writeln("<info>Component " . $counter . " of " . $componentCount . " imported</info>");
                $counter++;
            }
        }
    }
}
