<?php
namespace ComponentContentImportExport;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;

use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConvertAbstractCommand extends command
{
    public function readInput(InputInterface $input, OutputInterface $output)
    {
        if (0 === ftell(STDIN)) {
            $contents = '';
            while (!feof(STDIN)) {
                $contents .= fread(STDIN, 1024);
            }
        } else {
            throw new \RuntimeException("Please pipe content to STDIN.");
        }
        return $contents;
    }

    public function parseIdAndContents($contents)
    {
        $data = array();
        $contents = explode("\n", $contents);
        foreach ($contents as $exportData) {
            if (preg_match('#([^ ]+):([^ ]+) (.*)#', $exportData, $m)) {
                $reference = $m[1].':'.$m[2];
                $message = $m[3];
                if (!isset($data[$message])) $data[$message] = array();
                $data[$message][] = $reference;
            }
        }
        return $data;
    }
}