<?php
namespace Logshub\SearchModule\Model\Indexer\Categories;

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
     * @var array
     */
    protected $sentIds = [];
    
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
     * @param array $apiCategories array of objects \Magento\Catalog\Model\Category
     * @return boolean
     */
    public function synch(array $apiCategories)
    {
        // extracting IDs for nice logging + skipping categories already sent
        $idsArr = [];
        foreach ($apiCategories as $k => $cat) {
            if (in_array($cat->getId(), $this->sentIds)) {
                unset($apiCategories[$k]);
                continue;
            }
            $idsArr[] = $cat->getId();
            $this->sentIds[] = $cat->getId();
        }
        $idsStr = '#' . \implode(', #', $idsArr);
        
        if (empty($apiCategories)) {
            $this->logger->info('[LOGSHUBSEARCH] No categories to send');
            return false;
        }
        
        try {
            $request = new \Logshub\SearchClient\Request\IndexCategories(
                $this->serviceId,
                $apiCategories
            );
            $response = $this->client->indexCategories($request);
            if ($response->isSuccessful()) {
                $this->logger->info('[LOGSHUBSEARCH] Categories has been sent ' . $idsStr);
                
                return true;
            } else {
                $this->logger->error('[LOGSHUBSEARCH] Categories sending error '.$idsStr.': ' . $response->getAck());
            }
        } catch (\Exception $e) {
            $this->logger->error('[LOGSHUBSEARCH] Categories sending error '.$idsStr.': ' . $e->getMessage());
        }
        
        return false;
    }
}