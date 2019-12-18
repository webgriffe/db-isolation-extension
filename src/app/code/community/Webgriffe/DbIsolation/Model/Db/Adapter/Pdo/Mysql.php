<?php
/**
 * Created by PhpStorm.
 * User: kraken
 * Date: 19/10/18
 * Time: 18.07
 */

class Webgriffe_DbIsolation_Model_Db_Adapter_Pdo_Mysql extends Magento_Db_Adapter_Pdo_Mysql
{
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
        if (parent::getTransactionLevel() === 0) {
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
        if (parent::getTransactionLevel() === 0) {
            //Only do this when there is really no transaction open. Otherwise the ALTER TABLE operation of the parent
            //method would kill the transaction
            return parent::enableTableKeys($tableName, $schemaName);
        }

        return $this;
    }
}
