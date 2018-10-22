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
}
