<?php
namespace ComponentContentImportExport;

use Sepia\PoParser;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConvertToPoCommand extends Command
{
    protected function configure()
    {
        $this->setName('convert:to-po');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $poFile = new PoParser(new \Sepia\StringHandler(''));
        $poFile->setHeaders(array(
            '"Content-Type: text/plain; charset=UTF-8\n"'
        ));

        if (0 === ftell(STDIN)) {
            $contents = '';
            while (!feof(STDIN)) {
                $contents .= fread(STDIN, 1024);
            }
        } else {
            throw new \RuntimeException("Please pipe content to STDIN.");
        }

        $contents = explode("\n", $contents);
        foreach ($contents as $exportData) {
            if (preg_match('#([^ ]+) ([^ ]+) (.*)#', $exportData, $m)) {
                $poElement = array(
                    'msgid' => $m[1].' '.$m[2],
                    'msgstr' => str_replace("\\n", "\n", $m[3])
                );
                $poFile->setEntry($poElement['msgid'], $poElement, true);
            }
        }

        $output->writeln($poFile->compile());
    }
}
