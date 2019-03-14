<?php
namespace Logshub\SearchModule\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    public function __construct(
        Config $configHelper,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Catalog\Helper\Product $productHelper,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->configHelper = $configHelper;
        $this->logger = $logger;
        $this->productFactory = $productFactory;
        $this->productHelper = $productHelper;
    }

    /**
     *
     * @return \Logshub\SearchClient\Client
     * @throws \Logshub\SearchModule\Exception\ClientException
     */
    public function getApiClient($storeId)
    {
        $conf = $this->configHelper->getGeneralConfig($storeId);
        if (empty($conf['service_id']) || empty($conf['api_url']) || empty($conf['api_hash']) || empty($conf['api_secret'])) {
            throw new \Logshub\SearchModule\Exception\ClientException('Unable to create client - configuration error');
        }

        return new \Logshub\SearchClient\Client(
            new \GuzzleHttp\Client(),
            $conf['api_url'],
            $conf['api_hash'],
            $conf['api_secret']
        );
    }

    /**
     * 
     * @param \Magento\Catalog\Model\Product $product
     * @return \Logshub\SearchClient\Model\Product
     */
    public function getApiProduct(\Magento\Catalog\Model\Product $product)
    {
        $apiProduct = new \Logshub\SearchClient\Model\Product($product->getId(), [
            'name' => $product->getName(),
            'url' => $product->getUrlModel()->getUrl($product),
            'urlImage' => $this->productHelper->getSmallImageUrl($product),
            'price' => $product->getPrice(),
            'sku' => $product->getSku(),
            // TODO: other attributes: currency, description, headline, availibility, review_score, review_count, price_old
        ]);
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $categories = $product->getCategoryIds();
        foreach ($categories as $catId){
            $cat = $objectManager->create('Magento\Catalog\Model\Category')->load($catId);
            $apiProduct->addCategory($cat->getName());
        }

        return $apiProduct;
    }
    
    /**
     * 
     * @param array $productIds
     * @return array
     */
    public function getApiProducts(array $productIds)
    {
        $apiProducts = [];
        $productsCollection = $this->productFactory->create()->getCollection()
            ->addFieldToFilter('entity_id', $productIds)
            ->addAttributeToSelect(['name', 'price', 'small_image']);
        
        foreach ($productsCollection as $prod) {
            $apiProducts[] = $this->getApiProduct($prod);
        }
        
        return $apiProducts;
    }
    
    /**
     * @param int $storeId
     * @return \Logshub\SearchModule\Model\Indexer\Products\Sender
     */
    public function getProductsSender($storeId)
    {
        return new \Logshub\SearchModule\Model\Indexer\Products\Sender(
            $this->getApiClient($storeId),
            $this->configHelper->getServiceId($storeId),
            $this->logger
        );
    }
    
    /**
     * 
     * @param int $storeId
     * @return \Logshub\SearchModule\Model\Indexer\Categories\Sender
     */
    public function getCategoriesSender($storeId)
    {
        return new \Logshub\SearchModule\Model\Indexer\Categories\Sender(
            $this->getApiClient($storeId),
            $this->configHelper->getServiceId($storeId),
            $this->logger
        );
    }
}
