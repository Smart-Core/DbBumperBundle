<?php

declare(strict_types=1);

namespace SmartCore\Bundle\DbDumperBundle\Database;

class PostgreSQL extends AbstractDatabase
{
    const DB_PATH = 'postgresql';

    private $allDatabases;
    private $database;
    private $auth = '';
    private $fileName;
    private $ignoreTables = '';

    /**
     * @param array  $params
     * @param string $basePath
     * @param string $fileNamePrefix
     * @param string $filename
     */
    public function __construct($params, $basePath, $fileNamePrefix = '', $filename = null)
    {
        parent::__construct($basePath);

        $params = $params['postgresql'];
        $this->allDatabases = $params['all_databases'];
        $this->database     = $params['database'];
        $this->auth         = '';

        $this->host = $params['db_host'];
        $this->port = $params['db_port'];
        $this->user = $params['db_user'];
        $this->password = $params['db_password'];

        if ($filename) {
            $this->fileName = $fileNamePrefix.$filename.'.sql';
        } else {
            $this->fileName = $fileNamePrefix.$this->database.'.sql';
        }

        /* @todo ignore_tables
        if (isset($params['ignore_tables'])) {
            foreach ($params['ignore_tables'] as $ignoreTable) {
                if ($this->allDatabases) {
                    if (false === strpos($ignoreTable, '.')) {
                        throw new \LogicException(
                            'When dumping all databases both database and table must be specified when ignoring table'
                        );
                    }
                    $this->ignoreTables .= sprintf('--ignore-table=%s ', $ignoreTable);
                } else {
                    $this->ignoreTables .= sprintf('--ignore-table=%s.%s ', $this->database, $ignoreTable);
                }
            }
        }
        */

        $this->auth = sprintf('--dbname=postgresql://%s:%s@%s:%s/', $params['db_user'], $params['db_password'], $params['db_host'], $params['db_port']);
    }

    /**
     * {@inheritdoc}
     */
    public function dump()
    {
        $this->preparePath();

        \Spatie\DbDumper\Databases\PostgreSql::create()
            ->setDbName($this->database)
            ->setHost($this->host)
            ->setPort((int) $this->port)
            ->setUserName($this->user)
            ->setPassword($this->password)
            ->excludeTables($this->ignoreTables)
            ->dumpToFile($this->dataPath.$this->getFileName())
        ;

//        $this->execute($this->getCommand());
    }

    /**
     * {@inheritdoc}
     *
     * @deprecated
     */
    protected function getCommand()
    {
        return sprintf('pg_dump %s%s > %s',
            $this->auth,
            $this->database,
            $this->dataPath.$this->getFileName()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'PostgreSQL';
    }

    // Дополнительные методы ---------------

    public function import($path = null)
    {
        $this->preparePath();
        $this->execute($this->getImportCommand($path));
    }

    public function getPath()
    {
        return $this->dataPath.$this->getFileName();
    }

    protected function getImportCommand($path = null)
    {
        return sprintf('psql %s%s -f %s',
            $this->auth,
            $this->database,
            $path ?: $this->dataPath.$this->getFileName()
        );
    }

    /**
     * @return string
     */
    public function getFileName()
    {
        return $this->fileName;
    }

    /**
     * @param string $fileName
     *
     * @return $this
     */
    public function setFileName($fileName)
    {
        $this->fileName = $fileName;

        return $this;
    }
}
