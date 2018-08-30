<?php
namespace ComponentContentImportExport;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;

class ExportWorkerCommand extends Command
{
    protected function configure()
    {
        $this->setName('export:worker')
            //->setHidden(true)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $errOutput = $output instanceof ConsoleOutputInterface ? $output->getErrorOutput() : $output;

        set_time_limit(0);
        \Kwf_Util_MemoryLimit::set(512);

        $queueFile = 'temp/componentContentExportQueue';

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
            $page = \Kwf_Component_Data_Root::getInstance()->getComponentById($componentId, array('ignoreVisible' => true));
            if (!$page) {
                throw new \Exception("$componentId not found");
                continue;
            }
            if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERY_VERBOSE) {
                $errOutput->writeLn(" :: $page->url");
            }

            $childPages = $page->getChildComponents();
            foreach ($childPages as $c) {
                if ($c->parent->componentId != $page->componentId) continue; //skip unique box under other parent
                $errOutput->writeLn("queued $c->componentId", OutputInterface::VERBOSITY_VERBOSE);
                $queue[] = $c->componentId;
                file_put_contents($queueFile, implode("\n", $queue));
            }
            unset($c);
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
            unset($page);
        }
    }
}
