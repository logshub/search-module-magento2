<?php
namespace Logshub\SearchModule\Model\Indexer;

class Products implements \Magento\Framework\Indexer\ActionInterface, \Magento\Framework\Mview\ActionInterface
{
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Psr\Log\LoggerInterface $logger,
        \Logshub\SearchModule\Helper\Data $helper,
        \Logshub\SearchModule\Helper\Config $configHelper
    ) {
        $this->storeManager = $storeManager;
        $this->logger = $logger;
        $this->helper = $helper;
        $this->configHelper = $configHelper;
    }

    /*
     * Used by mview, allows process indexer in the "Update on schedule" mode
     */
    public function execute($ids)
    {
        // TODO: check configurable products

        $storeIds = array_keys($this->storeManager->getStores());
        foreach ($storeIds as $storeId) {
            if (!$this->configHelper->isEnabled($storeId)) {
                $this->logger->info('[LOGSHUBSEARCH] Synchronizing disabled for #'.$storeId);
                return;
            }
            if (!$this->configHelper->isReadyToIndex($storeId)) {
                $this->logger->error('[LOGSHUBSEARCH] Unable to update products index. Configuration error #'.$storeId);
                return;
            }

            if (!is_array($ids) || empty($ids)) {
                $ids = $this->getAllProductIds();
                $this->logger->info('[LOGSHUBSEARCH] Synchronizing all the '.count($ids).' products, store: #'.$storeId);
            }
            
            $sender = $this->helper->getProductsSender($storeId);
            $pageLength = $this->configHelper->getProductsIndexerPageLength($storeId);
            foreach (array_chunk($ids, $pageLength) as $chunk) {
                $apiProducts = $this->helper->getApiProducts($chunk);
                $sender->synch($apiProducts);
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
    
    /**
     * Returns array of product IDs.
     * Not by collection because it is heavy and there could be 1000s of products.
     * 
     * @return array
     */
    protected function getAllProductIds()
    {
        $om = \Magento\Framework\App\ObjectManager::getInstance();
        $connection = $om->get('Magento\Framework\App\ResourceConnection')->getConnection();
        // TODO: use getTable()
        
        $visibilityAttrId = 99; // TODO: get it dynamically: visibility
        $select = $connection->select()
            ->from(['main_table' => 'catalog_product_entity'], ['entity_id'])
            ->join(
                'catalog_product_entity_int as avisib',
                'main_table.entity_id = avisib.entity_id AND avisib.attribute_id = '.$visibilityAttrId,
                []
            )
            ->where('avisib.value IN(?)', [
                \Magento\Catalog\Model\Product\Visibility::VISIBILITY_IN_SEARCH,
                \Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH
            ]);
        
        return $connection->fetchCol($select);
    }
}
