<?php
namespace ComponentContentImportExport;

use Geekwright\Po\PoHeader;
use Geekwright\Po\PoTokens;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Geekwright\Po\PoFile;
use Geekwright\Po\PoEntry;

class ConvertToPoCommand extends ConvertAbstractCommand
{
    protected function configure()
    {
        $this->setName('convert:to-po');
        $this->setDescription('Converts a po-document into the format that can be used for import.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $header = new PoHeader();
        $header->setHeader('Content-Type', 'text/plain; charset=UTF-8');
        $poFile = new PoFile($header);

        $contents = $this->readInput($input, $output);
        $data = $this->parseIdAndContents($contents);

        foreach ($data as $message=>$references) {
            $entry = new PoEntry;
            $entry->set(PoTokens::MESSAGE, str_replace("\\n", "\n", $message));
            $entry->set(PoTokens::REFERENCE, $references);
            $poFile->addEntry($entry);
        }
        $output->writeln($poFile->dumpString());
    }
}
