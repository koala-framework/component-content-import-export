<?php
namespace ComponentContentImportExport;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
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
        $this->addOption('reference-file', 'r', InputOption::VALUE_REQUIRED,
            'Use a different file for references (e.g. new Export). If no file is provided, input-file is used for references');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $errOutput = $output instanceof ConsoleOutputInterface ? $output->getErrorOutput() : $output;
        $contents = $this->readInput($input, $output);

        $referenceFileName = $input->getOption('reference-file');
        $referenceFile = null;
        if ($referenceFileName) {
            $referenceFile = new Pofile();
            $referenceFile->parsePoSource(file_get_contents($referenceFileName));
        }
        $poFile = new PoFile();
        $poFile->parsePoSource($contents);
        foreach ($poFile->getEntries() as $entry) {
            $message = $entry->getAsString(PoTokens::MESSAGE);
            $translated = $entry->getAsString(PoTokens::TRANSLATED);

            if ($referenceFileName) {
                $poReferences = $referenceFile->findEntry($entry->getAsString(PoTokens::MESSAGE));
                if (!empty($poReferences)) {
                    $poReferences = $poReferences->getAsStringArray(PoTokens::REFERENCE);
                } else {
                    $errOutput->writeln('msgid "'.$entry->getAsString(PoTokens::MESSAGE).'" was not found in reference file, skipped'.PHP_EOL);
                    continue;
                }
            } else {
                $poReferences = $entry->getAsStringArray(PoTokens::REFERENCE);
            }
            $references = explode(' ', implode(' ', $poReferences));
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
