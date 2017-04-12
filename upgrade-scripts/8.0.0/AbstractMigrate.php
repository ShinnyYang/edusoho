<?php

abstract class AbstractMigrate
{
    protected $kernel;

    protected $perPageCount = 10000;

    public function __construct($kernel)
    {
        $this->kernel = $kernel;
    }

    /**
     * @return \Topxia\Service\Common\Connection
     */
    public function getConnection()
    {
        return $this->kernel->getConnection();
    }

    protected function createService($name)
    {
        return $this->kernel->createService($name);
    }

    protected function createDao($name)
    {
        return $this->kernel->createDao($name);
    }

    protected function exec($statement)
    {
        return $this->getConnection()->exec($statement);
    }

    protected function isFieldExist($table, $filedName)
    {
        $sql = "DESCRIBE `{$table}` `{$filedName}`;";
        $result = $this->getConnection()->fetchAssoc($sql);

        return empty($result) ? false : true;
    }

    protected function isTableExist($table)
    {
        $sql = "SHOW TABLES LIKE '{$table}'";
        $result = $this->getConnection()->fetchAssoc($sql);

        return empty($result) ? false : true;
    }

    protected function isIndexExist($table, $indexName)
    {
        $sql = "show index from `{$table}` where Key_name = '{$indexName}';";
        $result = $this->getConnection()->fetchAssoc($sql);

        return empty($result) ? false : true;
    }

    protected function isX8()
    {
        $sql = "select * from cloud_app where code='MAIN';";
        $result = $this->getConnection()->fetchAssoc($sql);
        return version_compare($result['fromVersion'], '7.5.14', '>');
    }

    protected function isCrontabJobExist($code)
    {
        $sql = "select * from crontab_job where name='{$code}'";
        $result = $this->getConnection()->fetchAssoc($sql);

        return empty($result) ? false : true;
    }

    protected function getLastPage($count)
    {
        return ceil($count / $this->perPageCount);
    }

    protected function getNextPage($count, $currentPage)
    {
        $diff = $this->getLastPage($count) - $currentPage;
        return $diff > 0 ? $currentPage + 1 : 0;
    }

    protected function getStart($page)
    {
        return ($page - 1) * $this->perPageCount;
    }

    abstract public function update($page);
}
