<?php

namespace SmartCore\Bundle\DbDumperBundle\Command;

use SmartCore\Bundle\DbDumperBundle\Database\MySQL;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
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
        $db = $this->getContainer()->get('smart_db_dumper.manader');

        $output->writeln('Dumping to: <comment>'.$db->getPath().'</comment>');

        $db->dump();

        // @todo сделать копирование основного дампа опциональным.
        $fs = new Filesystem();
        $fs->copy($db->getPath(), $this->getContainer()->getParameter('kernel.root_dir').'/../'.$this->getContainer()->getParameter('database_name').'.sql');

        $output->writeln('<info>Backup complete.</info>');
    }
}
