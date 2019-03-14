<?php
namespace Logshub\SearchModule\Model\Indexer\Products;

class Sender
{
    /**
     * @var \Logshub\SearchClient\Client
     */
    protected $client;
    /**
     * UUID
     * @var string
     */
    protected $serviceId;
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;
    
    /**
     * 
     * @param \Logshub\SearchClient\Client $client
     * @param string $serviceId
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Logshub\SearchClient\Client $client,
        $serviceId,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->client = $client;
        $this->serviceId = $serviceId;
        $this->logger = $logger;
    }
    
    /**
     * 
     * @param array $apiProducts array of objects \Magento\Catalog\Model\Product
     * @return boolean
     */
    public function synch(array $apiProducts)
    {
        // extracting IDs for nice logging
        $idsArr = [];
        foreach ($apiProducts as $pr) {
            $idsArr[] = $pr->getId();
        }
        $idsStr = '#' . \implode(', #', $idsArr);
        
        try {
            $request = new \Logshub\SearchClient\Request\IndexProducts(
                $this->serviceId,
                $apiProducts
            );
            $response = $this->client->indexProducts($request);
            if ($response->isSuccessful()) {
                $this->logger->info('[LOGSHUBSEARCH] Product has been sent ' . $idsStr);
                
                return true;
            } else {
                $this->logger->error('[LOGSHUBSEARCH] Product sending error '.$idsStr.': ' . $response->getAck());
            }
        } catch (\Exception $e) {
            $this->logger->error('[LOGSHUBSEARCH] Product sending error '.$idsStr.': ' . $e->getMessage());
        }
        
        return false;
    }
}