<?php
namespace ComponentContentImportExport;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;

class ExportWorkerCommand extends Command
{
    protected function configure()
    {
        $this->setName('export:worker')
            ->addOption('addInvisibleChildComponents', 'aiccmp', InputOption::VALUE_NONE, 'Include all invisible child-components.')
            ->addOption('isTrl', 't', InputOption::VALUE_NONE, 'Use export to translate for another language (skips invisible pages).')
            //->setHidden(true)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $errOutput = $output instanceof ConsoleOutputInterface ? $output->getErrorOutput() : $output;

        set_time_limit(0);
        \Kwf_Util_MemoryLimit::set(512);

        $queueFile = 'temp/componentContentExportQueue';
        $failedExports = array();

        while (true) {
            $errOutput->writeLn("memory_usage (child): ".(memory_get_usage()/(1024*1024))."MB", OutputInterface::VERBOSITY_VERY_VERBOSE);
            if (memory_get_usage() > 128*1024*1024) {
                $errOutput->writeln("new process...");
                break;
            }

            $queue = file_get_contents($queueFile);
            if (!$queue) break;

            $queue = explode("\n", $queue);
            if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
                $errOutput->writeln("queued: " . count($queue) . ' :: ' . round(memory_get_usage() / 1024 / 1024, 2) . "MB");
            }
            $componentId = array_pop($queue);
            file_put_contents($queueFile, implode("\n", $queue));

            if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERY_VERBOSE) {
                $errOutput->write("==> " . $componentId);
            }
            if ($input->getOption('isTrl')) {
                $page = \Kwf_Component_Data_Root::getInstance()->getComponentById($componentId);
                if (!$page) {
                    $errOutput->writeLn("$componentId not found or visible");
                    continue;
                }
            } else {
                $page = \Kwf_Component_Data_Root::getInstance()->getComponentById($componentId, array('ignoreVisible' => true));
                if (!$page) {
                    throw new \Exception("$componentId not found");
                    continue;
                }
            }

            $select = new \Kwf_Component_Select();
            if ($input->getOption('addInvisibleChildComponents')) $select->ignoreVisible(true);
            foreach ($page->getChildComponents($select) as $c) {
                if (\Kwc_Abstract::getFlag($c->componentClass, 'skipContentExportRecursive')) continue;
                if ($c->parent->componentId != $page->componentId) continue; //skip unique box under other parent
                $errOutput->writeLn("queued $c->componentId", OutputInterface::VERBOSITY_VERBOSE);
                $queue[] = $c->componentId;
                file_put_contents($queueFile, implode("\n", $queue));
            }
            unset($c);
            try {
                $exportData = \Kwc_Abstract_Admin::getInstance($page->componentClass)->exportContent($page);
                if (isset($page->generator)) {
                    foreach ($page->generator->exportContent($page) as $k=>$i) {
                        $exportData['gen_'.$k] = $i;
                    }
                }
                if ($exportData) {
                    $errOutput->writeln("<info>Exported $page->dbId</info>");
                    foreach ($exportData as $k=>$i) {
                        if ($i) {
                            $output->writeln("$page->dbId:$k ".str_replace("\n", "\\n", $i));
                        }
                    }
                }
            } catch (\Exception $e) {
                $failedExports[] = $page->dbId;
                continue;
            }
            unset($page);
        }

        if (count($failedExports) > 0) {
            $errOutput->writeLn(count($failedExports) . ' Exports failed:');
            foreach ($failedExports as $failedExport) {
                $errOutput->writeLn($failedExport);
            }
        } else {
            $errOutput->writeLn('No errors occurred');
        }
    }
}
