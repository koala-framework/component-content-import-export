<?php
namespace ComponentContentImportExport;

use Sepia\PoParser;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConvertFromPoCommand extends Command
{
    protected function configure()
    {
        $this->setName('convert:from-po');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (0 === ftell(STDIN)) {
            $contents = '';
            while (!feof(STDIN)) {
                $contents .= fread(STDIN, 1024);
            }
        } else {
            throw new \RuntimeException("Please pipe content to STDIN.");
        }

        $poFile = new PoParser(new \Sepia\StringHandler($contents));
        foreach ($poFile->parse() as $poElement) {
            if (!isset($poElement['msgid'])) {
                continue;
            }
            $msgstr = implode("", $poElement['msgstr']);
            $msgid = implode("", $poElement['msgid']);
            if ($msgid) {
                $output->writeln($msgid.' '.str_replace("\n", "\\n", $msgstr));
            }
        }
    }
}
