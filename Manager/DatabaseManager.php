<?php

namespace SmartCore\Bundle\DbDumperBundle\Manager;

use Doctrine\ORM\EntityManager;
use SmartCore\Bundle\DbDumperBundle\Database\MySQL;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class DatabaseManager
{
    use ContainerAwareTrait;

    /**
     * @var \SmartCore\Bundle\DbDumperBundle\Database\MySQL
     */
    protected $db = null;

    /** @var string */
    protected $backups_dir;

    /** @var \Doctrine\ORM\EntityManager */
    protected $em;

    /** @var string */
    protected $platform = null;

    /** @var int */
    protected $timeout;

    /**
     * @param EntityManager $em
     * @param string $backups_dir
     * @param int $timeout
     */
    public function __construct(EntityManager $em, $backups_dir, $timeout)
    {
        $this->em = $em;
        $this->backups_dir = $backups_dir;
        $this->timeout = $timeout;
    }

    public function init()
    {
        if ($this->db) {
            return;
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
                $this->db = new MySQL($params, $this->backups_dir, date('Y-m-d_H-i-s_'));
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
    }

    public function getPath()
    {
        return $this->db->getPath();
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
        return $this->container->getParameter('kernel.root_dir').'/../'.$this->container->getParameter('database_name').'.sql';
    }
}
