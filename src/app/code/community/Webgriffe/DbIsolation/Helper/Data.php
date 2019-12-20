<?php

class Webgriffe_DbIsolation_Helper_Data extends Mage_Core_Helper_Abstract
{
    const ENABLED_CONFIG_PATH = 'phpunit/db_isolation/enabled';

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return (bool)(string)Mage::getConfig()->getNode(self::ENABLED_CONFIG_PATH);
    }
}
