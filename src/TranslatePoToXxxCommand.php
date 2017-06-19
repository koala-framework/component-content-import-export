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

class TranslatePoToXxxCommand extends Command
{
    protected function configure()
    {
        $this->setName('translate-po-to-xxx');
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
            $entry->set(PoTokens::TRANSLATED, 'Xxx');
        }

        $output->writeln($poFile->dumpString());
    }
}
