<?php
namespace Logshub\SearchModule\Helper;

class Config extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * Keys: enabled, service_id, api_url, api_hash, api_secret, public_key
     * @param int $storeId
     * @return array
     */
    public function getGeneralConfig($storeId = null)
    {
        return $this->scopeConfig->getValue('logshub_search/general', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Keys: enabled, search_box_selector, type
     * @param int $storeId
     * @return array
     */
    public function getFrontendConfig($storeId = null)
    {
        return $this->scopeConfig->getValue('logshub_search/frontend', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * @return bool
     */
    public function isReadyToIndex($storeId = null)
    {
        return $this->isEnabled($storeId) &&
            $this->getServiceId($storeId) &&
            $this->getApiUrl($storeId) &&
            $this->getApiHash($storeId) &&
            $this->getApiSecret($storeId);
    }

    /**
     * @return bool
     */
    public function isEnabled($storeId = null)
    {
        $conf = $this->getGeneralConfig($storeId);

        return !empty($conf) && $conf['enabled'];
    }

    /**
     * @return string
     */
    public function getServiceId($storeId = null)
    {
        return $this->getGeneralConfigValue('service_id', $storeId);
    }

    /**
     * @return string
     */
    public function getApiUrl($storeId = null)
    {
        return $this->getGeneralConfigValue('api_url', $storeId);
    }

    /**
     * @return string
     */
    public function getApiHash($storeId = null)
    {
        return $this->getGeneralConfigValue('api_hash', $storeId);
    }

    /**
     * @return string
     */
    public function getApiSecret($storeId = null)
    {
        return $this->getGeneralConfigValue('api_secret', $storeId);
    }

    /**
     * @return string
     */
    public function getPublicKey($storeId = null)
    {
        return $this->getGeneralConfigValue('public_key', $storeId);
    }
    
    /**
     * How many products will be sent at once into the logshub search
     * @return int
     */
    public function getProductsIndexerPageLength($storeId = null)
    {
        return 5;
    }
    
    /**
     * How many categories will be sent at once into the logshub search
     * @return int
     */
    public function getCategoriesIndexerPageLength($storeId = null)
    {
        return 10;
    }

    /**
     * @return string
     */
    protected function getGeneralConfigValue($key, $storeId = null)
    {
        $conf = $this->getGeneralConfig($storeId);
        if (empty($conf[$key])) {
            return '';
        }

        return $conf[$key];
    }
}
