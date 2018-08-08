<?php

class Webgriffe_DbIsolation_Model_Observer
{
    const ENABLED_CONFIG_PATH = 'phpunit/db_isolation/enabled';
    const WARNING_ENABLED_CONFIG_PATH = 'phpunit/db_isolation/warning_enabled';

    /**
     * @var array
     */
    private $tablesChecksums = array();

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
            $this->tablesChecksums = $this->getAllTablesChecksums($connection);
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
        if ($this->isWarningEnabled()) {
            $diff = $this->checksumsDiff($this->getAllTablesChecksums($connection), $this->tablesChecksums);
            if (count($diff) > 0) {
                /** @var PHPUnit_Framework_Test $test */
                $test = $observer->getData('test');
                if ($test instanceof PHPUnit_Framework_TestCase) {
                    $tablesString = implode(', ', $diff);
                    $result = $test->getTestResultObject();
                    $risky = new PHPUnit_Framework_RiskyTestError(
                        sprintf(
                            'Test isolation warning: database state is changed during test "%s".',
                            get_class($test) . '::' . $test->getName(true) . ' Changed tables: ' . $tablesString
                        )
                    );
                    $result->addError($test, $risky, 0);
                    return;
                }
            }
        }
    }

    /**
     * @param $connection
     * @return mixed
     * @throws \Zend_Db_Statement_Exception
     */
    private function getAllTablesChecksums(Varien_Db_Adapter_Interface $connection)
    {
        // @codingStandardsIgnoreStart
        $tables = $connection->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
        // @codingStandardsIgnoreEnd

        $checksums = array();
        foreach ($tables as $table) {
            $checksums[$table] = $connection->getTablesChecksum(array($table));
        }

        return $checksums;
    }

    /**
     * Returns an array of table names indicating which tables were added, removed or changed between the two sets
     *
     * @param array $checksums1
     * @param array $checksums2
     * @return array
     */
    private function checksumsDiff(array $checksums1, array $checksums2)
    {
        $checksum1KeysNotInChecksum2 = array_keys(array_diff_key($checksums1, $checksums2));
        $checksum2KeysNotInChecksum1 = array_keys(array_diff_key($checksums2, $checksums1));

        $diff = array_merge($checksum1KeysNotInChecksum2, $checksum2KeysNotInChecksum1);

        $checksums1 = array_diff_key($checksums1, array_fill_keys($checksum1KeysNotInChecksum2, null));
        $checksums2 = array_diff_key($checksums2, array_fill_keys($checksum2KeysNotInChecksum1, null));

        //Now every key in checksums1 should also be in checksums2 and vice versa. So no need to explicitly check
        //whether a key is present. Just check the checksum value
        foreach ($checksums1 as $table => $checksum1) {
            $checksum2 = $checksums2[$table];

            if ($checksum1 != $checksum2) {
                $diff[] = $table;
            }
        }

        return $diff;
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
