<?php
namespace ComponentContentImportExport;

use Geekwright\Po\PoHeader;
use Geekwright\Po\PoTokens;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Geekwright\Po\PoFile;
use Geekwright\Po\PoEntry;

class ConvertToPoCommand extends Command
{
    protected function configure()
    {
        $this->setName('convert:to-po');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $header = new PoHeader();
        $header->setHeader('Content-Type', 'text/plain; charset=UTF-8');
        $poFile = new PoFile($header);

        if (0 === ftell(STDIN)) {
            $contents = '';
            while (!feof(STDIN)) {
                $contents .= fread(STDIN, 1024);
            }
        } else {
            throw new \RuntimeException("Please pipe content to STDIN.");
        }

        $data = array();
        $contents = explode("\n", $contents);
        foreach ($contents as $exportData) {
            if (preg_match('#([^ ]+) ([^ ]+) (.*)#', $exportData, $m)) {
                $reference = $m[1].' '.$m[2];
                $message = $m[3];
                if (!isset($data[$message])) $data[$message] = array();
                $data[$message][] = $reference;
            }
        }
        foreach ($data as $message=>$references) {
            $entry = new PoEntry;
            $entry->set(PoTokens::MESSAGE, str_replace("\\n", "\n", $message));
            $entry->set(PoTokens::REFERENCE, $references);
            $poFile->addEntry($entry);
        }

        $output->writeln($poFile->dumpString());
    }
}
