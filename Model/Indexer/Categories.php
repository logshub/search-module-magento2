<?php
namespace Logshub\SearchModule\Model\Indexer;

class Categories implements \Magento\Framework\Indexer\ActionInterface, \Magento\Framework\Mview\ActionInterface
{
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Psr\Log\LoggerInterface $logger,
        \Logshub\SearchModule\Helper\Data $helper,
        \Logshub\SearchModule\Helper\Config $configHelper,
        \Logshub\SearchModule\Helper\Categories $categoriesHelper
    ) {
        $this->storeManager = $storeManager;
        $this->logger = $logger;
        $this->helper = $helper;
        $this->configHelper = $configHelper;
        $this->categoriesHelper = $categoriesHelper;
    }

    /*
     * Used by mview, allows process indexer in the "Update on schedule" mode
     */
    public function execute($ids)
    {
        $storeIds = array_keys($this->storeManager->getStores());
        foreach ($storeIds as $storeId) {
            if (!$this->configHelper->isEnabled($storeId)) {
                $this->logger->info('[LOGSHUBSEARCH] Synchronizing disabled for #'.$storeId);
                return;
            }
            if (!$this->configHelper->isReadyToIndex($storeId)) {
                $this->logger->error('[LOGSHUBSEARCH] Unable to update categories index. Configuration error #'.$storeId);
                return;
            }
            
            if (is_array($ids) && !empty($ids)) {
                $apiCategories = $this->categoriesHelper->getApiCategoriesByIds($ids);
            } else {
                $apiCategories = $this->categoriesHelper->getAllApiCategories($storeId);
            }
            
            $sender = $this->helper->getCategoriesSender($storeId);
            $pageLength = $this->configHelper->getCategoriesIndexerPageLength($storeId);
            foreach (array_chunk($apiCategories, $pageLength) as $chunk) {
                $sender->synch($chunk);
            }
        }
    }

    /*
     * Will take all of the data and reindex
     * Will run when reindex via command line
     */
    public function executeFull()
    {
        $this->execute([]);
    }

    /*
     * Works with a set of entity changed (may be massaction)
     */
    public function executeList(array $ids)
    {
        $this->execute($ids);
    }

    /*
     * Works in runtime for a single entity using plugins
     */
    public function executeRow($id)
    {
        $this->execute([$id]);
    }
}
