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
        $this->addOption('isTrl', 't', InputOption::VALUE_NONE,
            'Set to true if import is used for trl. Parameter subrootId has to be the subroot of the trl-master-component');
        $this->addOption('listImportedComponentIds', 'l', InputOption::VALUE_NONE,
            'List all successfully imported componentIds.');

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

        if ($input->getOption('isTrl')) {
            $chainedMasterComponent = \Kwf_Component_Data_Root::getInstance()->getComponentByDbId($subrootId, array('ignoreVisible' => true, 'limit'=>1));
            if (!$chainedMasterComponent) {
                $errOutput->writeln("<error>Chained Master Component $subrootId not found</error>");
                return;
            }
        }

        $importedComponentIds = array();
        $componentCount = count($dataByComponentId);
        $counter = 1;
        foreach ($dataByComponentId as $componentId=>$data) {
            $cmp = \Kwf_Component_Data_Root::getInstance()->getComponentByDbId($componentId, array('ignoreVisible' => true, 'limit'=>1));
            if (!$cmp) {
                $errOutput->writeln("<error>Component $componentId not found</error>");
                continue;
            }
            if ($input->getOption('isTrl')) {
                $cmp = \Kwc_Chained_Abstract_Component::getChainedByMaster($cmp, $chainedMasterComponent, 'Trl', array('ignoreVisible' => true));
                if (!$cmp) {
                    $errOutput->writeln("<error>Chained Component for $componentId not found</error>");
                    continue;
                }
                $componentId = $cmp->componentId;
            }

            $cmpData = array();
            $genData = array();
            foreach ($data as $k=>$i) {
                if (substr($k, 0, 4) == 'gen_') {
                    $genData[substr($k, 4)] = $i;
                } else {
                    $cmpData[$k] = $i;
                }
            }

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

        if ($input->getOption('isTrl')) {
            if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
                $output->writeln("<info>Set translated components visible</info>");
            }
            \Kwf_Events_ModelObserver::getInstance()->disable();
            $component = $chainedMasterComponent->chained;
            $this->_setTrlComponentsVisibleRecursive($component, $chainedMasterComponent, $output);
        }

        if ($input->getOption('listImportedComponentIds')) {
            $output->writeln('-- listImportedComponentIds');
            foreach ($importedComponentIds as $componentId) {
                $output->writeln($componentId);
            }
        }
    }

    private function _setTrlComponentsVisibleRecursive(\Kwf_Component_Data $cmp, \Kwf_Component_Data $chainedMasterComponent, $output)
    {
        if (isset($cmp->row) && isset($cmp->row->visible)) {

            $chainedComponent = null;
            $model = null;
            if (get_class($cmp->row) != 'stdClass') {
                $model = $cmp->row->getModel();
            }

            if ($model instanceof \Kwc_Root_Category_GeneratorModel ||
                $model instanceof \Kwc_Paragraphs_Model ||
                $model instanceof \Kwc_Abstract_List_Model
            ) {
                $chainedComponent = \Kwc_Chained_Abstract_Component::getChainedByMaster($cmp, $chainedMasterComponent, 'Trl', array('ignoreVisible' => true));
                $modelClass = null;
                if ($model instanceof \Kwc_Root_Category_GeneratorModel) {
                    $modelClass = 'Kwc_Root_Category_Trl_GeneratorModel';
                } else if (\Kwc_Abstract::hasSetting($chainedComponent->parent->componentClass, 'childModel')) {
                    $modelClass = \Kwc_Abstract::getSetting($chainedComponent->parent->componentClass, 'childModel');
                }
                if ($modelClass) {
                    $trlRow = \Kwf_Model_Abstract::getInstance($modelClass)->getRow($chainedComponent->componentId);
                    if (!$trlRow) {
                        $trlRow = \Kwf_Model_Abstract::getInstance($modelClass)->createRow();
                        $trlRow->component_id = $chainedComponent->componentId;
                    }
                    $trlRow->visible = true;
                    $trlRow->save();
                }
            }

            if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
                if ($chainedComponent) {
                    $output->writeln("<info>Set visible: ".$chainedComponent->componentId."</info>");
                } else if ($model) {
                    $output->writeln("<error>Cannot set visible for chained component of master component with id " . $cmp->componentId . "</error>");
                }
            }
        }
        \Kwf_Component_Data_Root::getInstance()->freeMemory();
        foreach ($cmp->getChildComponents() as $childCmp) {
            $this->_setTrlComponentsVisibleRecursive($childCmp, $chainedMasterComponent, $output);
        }
    }
}
