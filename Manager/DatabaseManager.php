<?php

namespace SmartCore\Bundle\DbDumperBundle\Manager;

use Doctrine\ORM\EntityManager;
use SmartCore\Bundle\DbDumperBundle\Database\MySQL;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class DatabaseManager
{
    use ContainerAwareTrait;

    /** @var \SmartCore\Bundle\DbDumperBundle\Database\MySQL */
    protected $db = null;

    /** @var string */
    protected $archive;

    /** @var string */
    protected $backups_dir;

    /** @var \Doctrine\ORM\EntityManager */
    protected $em;

    /** @var string */
    protected $platform = null;

    /** @var int */
    protected $timeout;

    /** @var string */
    protected $filename;

    /**
     * @param EntityManager $em
     * @param string $backups_dir
     * @param int $timeout
     * @param string|null $filename
     */
    public function __construct(EntityManager $em, $backups_dir, $timeout, $filename = null)
    {
        $this->em           = $em;
        $this->archive      = null;
        $this->backups_dir  = $backups_dir;
        $this->filename     = $filename;
        $this->timeout      = $timeout;
    }

    public function init()
    {
        if ($this->db) {
            return;
        }

        $archive = $this->container->getParameter('smart_db_dumper.archive');

        if ($archive !== 'none') {
            $this->archive = $archive;
        }

        $this->platform = $this->em->getConnection()->getDatabasePlatform()->getName();

        $paramsCommon = [
            'all_databases' => false,
            'database' => $this->container->getParameter('database_name'),
            'db_user' => $this->container->getParameter('database_user'),
            'db_password' => $this->container->getParameter('database_password'),
            'db_host' => $this->container->getParameter('database_host'),
            'db_port' => $this->container->getParameter('database_port') ?: 3306,
            'ignore_tables' => [],
        ];

        switch ($this->platform) {
            case 'mysql':
                $params['mysql'] = $paramsCommon;
                $this->db = new MySQL($params, $this->backups_dir, date('Y-m-d_H-i-s_'), $this->filename);
                break;
            default:
                throw new \Exception('Unknown database platform: '.$this->platform);
        }
    }

    public function import($path = null)
    {
        return $this->db->import($path);
    }

    public function dump()
    {
        $this->db->dump();

        if ($this->archive == 'gz') {
            $file = $this->gzip($this->db->getPath());

            unlink($this->db->getPath());

            return $file;
        }

        return $this->db->getPath();
    }

    public function getPath()
    {
        return $this->archive ? $this->db->getPath().'.'.$this->archive : $this->db->getPath();
    }

    /**
     * @return MySQL
     */
    public function getDb()
    {
        return $this->db;
    }

    /**
     * @return string
     */
    public function getPlatform()
    {
        return $this->platform;
    }

    /**
     * @return int
     */
    public function getTimeout()
    {
        return $this->timeout;
    }

    /**
     * @return string
     */
    public function getBackupsDir()
    {
        return $this->backups_dir;
    }

    /**
     * @return string
     */
    public function getDefaultDumpFilePath()
    {
        if ($this->filename) {
            $path = $this->container->getParameter('kernel.root_dir').'/../'.$this->filename.'.sql';
        } else {
            $path = $this->container->getParameter('kernel.root_dir').'/../'.$this->container->getParameter('database_name').'.sql';
        }

        return $path;
    }

    /**
     * @param bool $ext
     *
     * @return null|string
     */
    public function getFilename($ext = false)
    {
        $filename = $this->filename;

        if ($ext) {
            $filename .= $this->getFilenameExtension();
        }

        return $filename;
    }

    /**
     * @return string
     */
    public function getFilenameExtension()
    {
        $ext = '.sql';

        if ($this->archive) {
            $ext .= '.'.$this->archive;
        }

        return $ext;
    }

    /**
     * @return string
     */
    public function getArchive()
    {
        return $this->archive;
    }

    /**
     * @param string $archive
     *
     * @return $this
     */
    public function setArchive($archive)
    {
        $this->archive = $archive;

        return $this;
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
