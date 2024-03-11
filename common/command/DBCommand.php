<?php

namespace common\command;


use common\components\CommonUtil;
use yii\db\Command;

class DBCommand extends Command
{

    /**
     * 插入数据（重复）
     * @param $table
     * @param $columns
     * @return $this
     */
    public function ignoreInsert($table, $columns)
    {
        parent::insert($table, $columns);
        $sql = $this->getRawSql();
        $sql = 'INSERT IGNORE' . mb_substr( $sql, strlen( 'INSERT' ) );
        $this->setSql($sql);
        return $this;
    }

    /**
     * 批量添加时处理重复数据问题
     * @param $table
     * @param $columns
     * @param $rows
     * @return $this
     */
    public function batchIgnoreInsert($table, $columns, $rows)
    {
        parent::batchInsert($table, $columns, $rows);
        $sql = $this->getRawSql();
        $sql = 'INSERT IGNORE' . mb_substr( $sql, strlen( 'INSERT' ) );
        $this->setSql($sql);
        return $this;
    }

    /**
     * @param string $method
     * @param null $fetchMode
     * @return mixed
     * @throws \yii\db\Exception
     */
    protected function queryInternal($method, $fetchMode = null)
    {
        try {
            return parent::queryInternal($method, $fetchMode);
        } catch (\yii\db\Exception $e) {
            $offset = strpos($e->getMessage(), 'MySQL server has gone away');
            if (($e instanceof \yii\db\Exception) && $offset !== false) {
            //if ($e->errorInfo[1] == 2006 || $e->errorInfo[1] == 2013) {
                //echo '重连数据库';
                CommonUtil::logs('query MySQL server has gone away','mysql_error');
                $this->db->close();
                $this->db->open();
                $this->pdoStatement = null;
                return parent::queryInternal($method, $fetchMode);
            }
            throw $e;
        }
    }

    /**
     * @return int
     * @throws \yii\db\Exception
     */
    public function execute()
    {
        try {
            return parent::execute();
        } catch (\yii\db\Exception $e) {
            $offset = strpos($e->getMessage(), 'MySQL server has gone away');
            if (($e instanceof \yii\db\Exception) && $offset !== false) {
            //if ($e->errorInfo[1] == 2006 || $e->errorInfo[1] == 2013) {
                //echo '重连数据库';
                CommonUtil::logs('execute MySQL server has gone away','mysql_error');
                $this->db->close();
                $this->db->open();
                $this->pdoStatement = null;
                return parent::execute();
            }
            throw $e;
        }
    }



}