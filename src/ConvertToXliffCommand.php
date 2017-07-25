<?php
namespace ComponentContentImportExport;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;

use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use ComponentContentImportExport\XliffDocument;

class ConvertToXliffCommand extends ConvertAbstractCommand
{
    protected function configure()
    {
        $this->setName('convert:to-xliff');
        $this->addOption('source-lang', null, InputOption::VALUE_REQUIRED,
            'Defines the source-language attribute for the whole file');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $xliff = new XliffDocument();
        $xliff->file(true)->body(true);

        $sourceLang = !$input->getOption('source-lang') ? 'de-AT' : $input->getOption('source-lang');

        $xliff->file()->setAttribute('original', 'db_export')
                      ->setAttribute('source-language', $sourceLang)
                      ->setAttribute('datatype', 'database');

        $contents = $this->readInput($input, $output);
        $data = $this->parseIdAndContents($contents);

        $counter = 0;
        foreach ($data as $message=>$references) {
            $xliff->file()->body()->unit(true)->setAttribute('id', $counter)
                                              ->source(true)->setTextContent(
                                                  str_replace("\\n", "\n", $message));
            $refAppend = '';
            foreach ($references as $ref) {
                $refAppend .= $ref;
                if ($ref !== end($references)) $refAppend .= ';';
            }
            $xliff->file()->body()->unit()->setAttribute('resname', $refAppend);
            $counter++;
        }
        $format = $xliff->toDOM();
        $format->preserveWhiteSpace = false;
        $format->formatOutput = true;
        $output->writeln($format->saveXML());
    }
}
