<?php

namespace SmartCore\Bundle\DbDumperBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Process\Process;

class RestoreCommand extends ContainerAwareCommand
{
    /**
     * Configure the command.
     */
    protected function configure()
    {
        $this
            ->setName('smart:dbdumper:restore')
            ->setDescription('Restore default backup.');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dbdumper = $this->getContainer()->get('smart_db_dumper.manager');

        /** @var QuestionHelper $dialog */
        $dialog   = $this->getHelper('question');

        $dumpFile = $dbdumper->getDefaultDumpFilePath();

        if (is_dir($dbdumper->getBackupsDir().$dbdumper->getPlatform())) {
            $finder = new Finder();
            $files = $finder->ignoreDotFiles(true)->in($dbdumper->getBackupsDir().$dbdumper->getPlatform());

            if ($files->count()) {
                $size = round((new \SplFileInfo($dumpFile))->getSize() / 1024 / 1024, 2);

                $output->writeln('<info>Select backup file:</info>');
                $output->writeln('0) <comment>'.realpath($dumpFile).'</comment> '.$size.'MB');

                $count = 0;
                $fileNames = [];
                $fileNames[$count++] = $dumpFile;
                /** @var \Symfony\Component\Finder\SplFileInfo $file */
                foreach ($files as $file) {
                    $size = round($file->getSize() / 1024 / 1024, 2);

                    $output->writeln($count.') <comment>'.$file->getRelativePathname().'</comment> '.$size.'MB');
                    $fileNames[$count++] = $file->getRelativePathname();
                }

                $fileId = $dialog->ask($input, $output, new Question('Please enter the number of dump file [0]: ', '0'));

                if (!isset($fileNames[$fileId])) {
                    $output->writeln('<error>Error:</error> File number <comment>'.$fileId.'</comment> does\'t exists.');

                    return false;
                }

                if ($fileId) {
                    $dumpFile = $dbdumper->getBackupsDir().$dbdumper->getPlatform().'/'.$fileNames[$fileId];
                }
            }
        }

        if (!file_exists($dumpFile)) {
            $output->writeln('<error>Error:</error> File <comment>'.$dumpFile.'</comment> does\'t exists.');

            return false;
        }

        $confirm = $dialog->ask($input, $output, new Question('<question>Warning:</question> This action is drop all your database and import from file <comment>'.realpath($dumpFile).'</comment> [y,N]: ', 'n'));

        if (strtolower($confirm) !== 'y') {
            $output->writeln('<info>Abort.</info>');

            return false;
        }

//        $this->executeCommand('doctrine:schema:update', ['--force' => true, '--complete' => true]);
//        $this->executeCommand('doctrine:schema:drop', ['--force' => true]);
        $this->executeShellCommand('php bin/console doctrine:schema:update --force --complete', $output);
        $this->executeShellCommand('php bin/console doctrine:schema:drop --force', $output);

        $output->writeln('Importing from: <comment>'.$dumpFile.'</comment>');

        $dbdumper->import($dumpFile);

        $output->writeln('<info>Restore complete.</info>');
    }

    protected function executeCommand($cmd, array $args = [])
    {
        $args['command'] = $cmd;

        $application = new Application($this->getContainer()->get('kernel'));
        $application->setAutoExit(false);
        $input = new ArrayInput($args);
        $output = new BufferedOutput();

        return $application->run($input, $output);
    }

    protected function executeShellCommand($cmd, OutputInterface $output = null)
    {
        $process = new Process($cmd, null, null, null, 600);
        $process->run(function ($type, $buffer) use ($output) {
            $output->write($buffer, false);
        });

        if (!$process->isSuccessful()) {
            throw new \RuntimeException(sprintf('An error occurred when executing the "%s" command.', escapeshellarg($cmd)));
        }
    }
}
