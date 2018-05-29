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
            ->setAliases(['db:dump'])
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

        $pathinfo = pathinfo($db->getPath());
        $path = realpath($pathinfo['dirname']).'/'.$pathinfo['basename'];

        $output->writeln('Dumping to: <comment>'.$path.'</comment>');

        $db->dump();

        // @todo сделать копирование основного дампа опциональным.
        if ($db->getFilename()) {
            $path = $this->getContainer()->getParameter('kernel.root_dir').'/../'.$db->getFilename().'.sql';
        } else {
            $path = $this->getContainer()->getParameter('kernel.root_dir').'/../'.$this->getContainer()->getParameter('database_name').'.sql';
        }

        if ($this->getContainer()->getParameter('smart_db_dumper.archive') == 'gz') {
            $gzfile = $this->gzip($db->getPath());

            $fs = new Filesystem();
            $fs->copy($gzfile, $path.'.gz');

            unlink($db->getPath());
        } else {
            $fs = new Filesystem();
            $fs->copy($db->getPath(), $path);
        }

        $time = round(microtime(true) - $start_time, 2);

        $output->writeln("<info>Backup complete in $time sec.</info>");
    }

    protected function gzip($filename)
    {
        // Name of the gz file we're creating
        $gzfile = $filename.".gz";

        // Open the gz file (w9 is the highest compression)
        $fp = gzopen($gzfile, 'w9');

        // Compress the file
        gzwrite($fp, file_get_contents($filename));

        // Close the gz file and we're done
        gzclose($fp);

        return $gzfile;
    }
}
