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

class ConvertFromPoCommand extends Command
{
    protected function configure()
    {
        $this->setName('convert:from-po');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $errOutput = $output instanceof ConsoleOutputInterface ? $output->getErrorOutput() : $output;

        if (0 === ftell(STDIN)) {
            $contents = '';
            while (!feof(STDIN)) {
                $contents .= fread(STDIN, 1024);
            }
        } else {
            throw new \RuntimeException("Please pipe content to STDIN.");
        }

        $poFile = new PoFile();
        $poFile->parsePoSource($contents);
        foreach ($poFile->getEntries() as $entry) {
            $message = $entry->getAsString(PoTokens::MESSAGE);
            $translated = $entry->getAsString(PoTokens::TRANSLATED);
            $references = $entry->getAsStringArray(PoTokens::REFERENCE);
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
