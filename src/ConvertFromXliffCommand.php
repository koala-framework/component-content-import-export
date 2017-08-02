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
        $this->setDescription('Converts a xliff-document into the format that can be used for import.');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $errOutput = $output instanceof ConsoleOutputInterface ? $output->getErrorOutput() : $output;
        $contents = $this->readInput($input, $output);

        $dom = new DOMDocument();
        $dom->loadXML($contents);
        $xliff = XliffDocument::fromDom($dom);

        foreach ($xliff->file()->body()->units() as $unit) {
            if ($unit->source() && empty($unit->source()->getTextContent())) continue;
            $translated = false;
            $translated = $unit->target() ? $unit->target()->getTextContent() : false;

            if (empty(trim(strip_tags($translated)))) $translated = false;
            if (!$translated) {
                $translated = '';
                if ($unit->target()) {
                    foreach ($unit->target()->mrks() as $mrk) {
                        $translated .= $mrk->getTextContent();
                    }
                }
            }
            $translated = str_replace(chr(0xe3).chr(0x80).chr(0x80), ' ', $translated);
            if (empty(trim(strip_tags($translated)))) {
                $translated = false;
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