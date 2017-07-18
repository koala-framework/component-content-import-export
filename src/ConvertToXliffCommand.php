<?php
namespace ComponentContentImportExport;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;

use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use ComponentContentImportExport\XliffDocument;

class ConvertToXliffCommand extends ConvertAbstractCommand
{
    protected function configure()
    {
        $this->setName('convert:to-xliff');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $xliff = new XliffDocument();
        $xliff->file(true)->body(true);

        $contents = $this->readInput($input, $output);
        $data = $this->parseIdAndContents($contents);

        foreach ($data as $message=>$references) {
            $xliff->file()->body()->unit(true)->source(true)->setTextContent($message);
            $refAppend = '';
            foreach ($references as $ref) {
                $refAppend .= $ref;
                if ($ref !== end($references)) $refAppend .= ';';
            }
            $refAppend = $refAppend;
            $xliff->file()->body()->unit()->setAttribute('resname', $refAppend);
        }
        $format = $xliff->toDOM();
        $format->preserveWhiteSpace = false;
        $format->formatOutput = true;
        $output->writeln($format->saveXML());
    }
}