<?php

class Webgriffe_DbIsolation_Model_Observer
{
    const ENABLED_CONFIG_PATH = 'phpunit/db_isolation/enabled';
    const WARNING_ENABLED_CONFIG_PATH = 'phpunit/db_isolation/warning_enabled';

    /**
     * @var array
     */
    private $tablesChecksum = array();

    /**
     * @observedEvent: phpunit_test_start_before
     *
     * @param Varien_Event_Observer $observer
     * @throws \Zend_Db_Statement_Exception
     */
    public function beginTransaction(Varien_Event_Observer $observer)
    {
        $connection = Mage::getSingleton('core/resource')->getConnection('core_write');
        if ($this->isWarningEnabled()) {
            $this->tablesChecksum = $this->getAllTablesChecksum($connection);
        }
        if ($this->isEnabled()) {
            $connection->beginTransaction();
        }
    }

    /**
     * @observedEvent: phpunit_test_end_after
     *
     * @param Varien_Event_Observer $observer
     * @throws \Zend_Db_Statement_Exception
     */
    public function rollbackTransaction(Varien_Event_Observer $observer)
    {
        $connection = Mage::getSingleton('core/resource')->getConnection('core_write');
        if ($this->isEnabled()) {
            $connection->rollBack();
        }
        if ($this->isWarningEnabled() && $this->getAllTablesChecksum($connection) !== $this->tablesChecksum) {
            /** @var PHPUnit_Framework_Test $test */
            $test = $observer->getData('test');
            if ($test instanceof PHPUnit_Framework_TestCase) {
                $result = $test->getTestResultObject();
                $risky = new PHPUnit_Framework_RiskyTestError(
                    sprintf(
                        'Test isolation warning: database state is changed during test "%s".',
                        get_class($test) . '::' . $test->getName(true)
                    )
                );
                $result->addError($test, $risky, 0);
                return;
            }
        }
    }

    /**
     * @param $connection
     * @return mixed
     * @throws \Zend_Db_Statement_Exception
     */
    private function getAllTablesChecksum(Varien_Db_Adapter_Interface $connection)
    {
        // @codingStandardsIgnoreStart
        $tables = $connection->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
        // @codingStandardsIgnoreEnd
        return $connection->getTablesChecksum($tables);
    }

    /**
     * @return bool
     */
    private function isEnabled()
    {
        return (bool)(string)Mage::getConfig()->getNode(self::ENABLED_CONFIG_PATH);
    }

    /**
     * @return bool
     */
    private function isWarningEnabled()
    {
        return (bool)(string)Mage::getConfig()->getNode(self::WARNING_ENABLED_CONFIG_PATH);
    }
}
