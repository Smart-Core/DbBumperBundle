<?php

namespace SmartCore\Bundle\DbDumperBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

class DumpCommand extends ContainerAwareCommand
{
    /**
     * Configure the command.
     */
    protected function configure()
    {
        $this
            ->setName('smart:dbdumper:dump')
            ->setAliases(['db:dump'])
            ->addOption('archive', 'a', InputOption::VALUE_OPTIONAL, 'Use archive compression')
            ->setDescription('Dump backup of your database.');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $start_time = microtime(true);

        $db = $this->getContainer()->get('smart_db_dumper.manager');

        if (!empty($input->getOption('archive'))) {
            $db->setArchive(str_replace('=', '', $input->getOption('archive')));
        }

        $pathinfo = pathinfo($db->getPath());
        $path = realpath($pathinfo['dirname']).'/'.$pathinfo['basename'];

        $output->writeln('Dumping to: <comment>'.$path.'</comment>');

        $dumpFilePath = $db->dump();

        if ($this->getContainer()->getParameter('smart_db_dumper.make_copy_to_project_root')) {
            if ($db->getFilename()) {
                $path = $this->getContainer()->getParameter('kernel.root_dir').'/../'.$db->getFilename(true);
            } else {
                $path = $this->getContainer()->getParameter('kernel.root_dir').'/../'.$this->getContainer()->getParameter('database_name').$db->getFilenameExtension();
            }

            $fs = new Filesystem();
            $fs->copy($dumpFilePath, $path);
        }

        $time = round(microtime(true) - $start_time, 2);

        $output->writeln("<info>Backup complete in $time sec.</info>");
    }
}
