<?php
/**
 * Created by PhpStorm.
 * User: kraken
 * Date: 19/10/18
 * Time: 18.07
 */

class Webgriffe_DbIsolation_Model_Db_Adapter_Pdo_Mysql extends Magento_Db_Adapter_Pdo_Mysql
{
    const SAVEPOINT_NAME = 'db_isolation_savepoint';

    protected $transactionLevelOffset = 0;

    public function getTransactionLevel()
    {
        return parent::getTransactionLevel() - $this->transactionLevelOffset;
    }

    public function setTransactionLevelOffset($offset)
    {
        $this->transactionLevelOffset = $offset;
        return $this;
    }

    public function getTransactionLevelOffset()
    {
        return $this->transactionLevelOffset;
    }

    /**
     * {@inheritdoc}
     */
    public function disableTableKeys($tableName, $schemaName = null)
    {
        if (parent::getTransactionLevel() === 0 || !$this->isEnabled()) {
            //Only do this when there is really no transaction open. Otherwise the ALTER TABLE operation of the parent
            //method would kill the transaction
            return parent::disableTableKeys($tableName, $schemaName);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function enableTableKeys($tableName, $schemaName = null)
    {
        if (parent::getTransactionLevel() === 0 || !$this->isEnabled()) {
            //Only do this when there is really no transaction open. Otherwise the ALTER TABLE operation of the parent
            //method would kill the transaction
            return parent::enableTableKeys($tableName, $schemaName);
        }

        return $this;
    }

    public function truncateTable($tableName, $schemaName = null)
    {
        if (parent::getTransactionLevel() === 0 || !$this->isEnabled()) {
            return parent::truncateTable($tableName, $schemaName);
        }

        //If there is an open transaction, a TRUNCATE TABLE statment is not feasible, as that would terminate the
        //transaction.
        //Try to emulate it with a DELETE statement. This is not exactly equivalent to a TRUNCATE TABLE, as a DELETE
        //statement does nto reset the autoincrement counter and is still subject to foreign key checks, but it's better
        //than breaking the isolation
        if (!$this->isTableExists($tableName, $schemaName)) {
            throw new Zend_Db_Exception(sprintf('Table "%s" is not exists', $tableName));
        }

        $table = $this->quoteIdentifier($this->_getTableName($tableName, $schemaName));
        $query = 'DELETE FROM ' . $table;
        $this->query($query);

        return $this;
    }

    public function beginTransaction()
    {
        if ($this->getTransactionLevel() == 0 && $this->getTransactionLevelOffset() > 0) {
            $savepointName = self::SAVEPOINT_NAME;
            $this->getConnection()->exec("SAVEPOINT {$savepointName}");
        }

        return parent::beginTransaction();
    }

    public function rollback()
    {
        $result = parent::rollback();

        if ($this->getTransactionLevel() == 0 && $this->getTransactionLevelOffset() > 0) {
            $savepointName = self::SAVEPOINT_NAME;
            $this->getConnection()->exec("ROLLBACK TO SAVEPOINT {$savepointName}");
        }

        return $result;
    }

    public function commit()
    {
        $result = parent::commit();

        if ($this->getTransactionLevel() == 0 && $this->getTransactionLevelOffset() > 0) {
            $savepointName = self::SAVEPOINT_NAME;
            $this->getConnection()->exec("RELEASE SAVEPOINT {$savepointName}");
        }

        return $result;
    }

    /**
     * @return bool
     */
    private function isEnabled()
    {
        return Mage::helper('webgriffe_dbisolation')->isEnabled();
    }
}
