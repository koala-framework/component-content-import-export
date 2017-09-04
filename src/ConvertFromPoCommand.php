<?php
namespace ComponentContentImportExport;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Geekwright\Po\PoHeader;
use Geekwright\Po\PoTokens;
use Geekwright\Po\PoFile;
use Geekwright\Po\PoEntry;

class ConvertFromPoCommand extends ConvertAbstractCommand
{
    protected function configure()
    {
        $this->setName('convert:from-po');
        $this->setDescription('Converts a exported document to the po format.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $errOutput = $output instanceof ConsoleOutputInterface ? $output->getErrorOutput() : $output;
        $contents = $this->readInput($input, $output);

        $poFile = new PoFile();
        $poFile->parsePoSource($contents);
        foreach ($poFile->getEntries() as $entry) {
            $message = $entry->getAsString(PoTokens::MESSAGE);
            $translated = $entry->getAsString(PoTokens::TRANSLATED);
            $references = explode(' ', implode(' ', $entry->getAsStringArray(PoTokens::REFERENCE)));
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
