<?php

namespace SmartCore\Bundle\DbDumperBundle\Database;

use Dizda\CloudBackupBundle\Database\BaseDatabase;

/**
 * Отличие от оригинального класса Dizda:
 *
 * 1) Значения параметров в двойных кавычках т.к. на Windows одинарные не срабатывают.
 *
 * 2) Прекфикс имени файла.
 */
class MySQL extends BaseDatabase
{
    const DB_PATH = 'mysql';

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

        $params = $params['mysql'];
        $this->allDatabases = $params['all_databases'];
        $this->database     = $params['database'];
        $this->auth         = '';

        if ($this->allDatabases) {
            $this->database = '--all-databases';
            $this->fileName = $fileNamePrefix.'all-databases.sql';
        } else {
            if ($filename) {
                $this->fileName = $fileNamePrefix.$filename.'.sql';
            } else {
                $this->fileName = $fileNamePrefix.$this->database.'.sql';
            }
        }

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

        /* if user is set, we add authentification */
        if ($params['db_user']) {
            $this->auth = sprintf('-u%s', $params['db_user']);

            if ($params['db_password']) {
                $this->auth = sprintf('--host="%s" --port="%d" --user="%s" --password="%s"', $params['db_host'], $params['db_port'], $params['db_user'], $params['db_password']);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function dump()
    {
        $this->preparePath();
        $this->execute($this->getCommand());
    }

    /**
     * {@inheritdoc}
     */
    protected function getCommand()
    {
        return sprintf('mysqldump %s %s %s > %s',
            $this->auth,
            $this->database,
            $this->ignoreTables,
            $this->dataPath.$this->fileName
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'MySQL';
    }

    // Дополнительные методы ---------------

    public function import($path = null)
    {
        $this->preparePath();
        $this->execute($this->getImportCommand($path));
    }

    public function getPath()
    {
        return $this->dataPath.$this->fileName;
    }

    protected function getImportCommand($path = null)
    {
        return sprintf('mysql %s %s < %s',
            $this->auth,
            $this->database,
            $path ?: $this->dataPath.$this->fileName
        );
    }
}
