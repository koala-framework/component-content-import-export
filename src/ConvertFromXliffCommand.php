<?php
namespace ComponentContentImportExport;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use ComponentContentImportExport\XliffDocument;
use DOMDocument;

class ConvertFromXliffCommand extends ConvertAbstractCommand
{
    protected function configure()
    {
        $this->setName('convert:from-xliff');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $errOutput = $output instanceof ConsoleOutputInterface ? $output->getErrorOutput() : $output;
        $contents = $this->readInput($input, $output);

        $dom = new DOMDocument();
        $dom->loadXML($contents);
        $xliff =  XliffDocument::fromDom($dom);

        foreach ($xliff->file()->body()->units() as $unit) {
            $message    = ($unit->source() ? $unit->source()->getTextContent() : false);
            $translated = ($unit->target() ? $unit->target()->getTextContent() : false);
            if (!$message) continue;
            if (!$translated) {
                $translated = ($unit->target()->mrk() ? $unit->target()->mrk()->getTextContent() : false);
            }

            $references = explode(";", $unit->getAttribute('resname'));
            if (!$references) {
                $errOutput->writeln("<error>missing reference for $message</error>");
            }
            if ($translated) {
                foreach ($references as $reference) {
                    $output->writeln($reference.' '.str_replace("\n", "\\n", $translated));
                }
            }
        }
    }
}