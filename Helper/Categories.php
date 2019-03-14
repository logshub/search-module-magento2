<?php
namespace Logshub\SearchModule\Helper;

class Categories extends \Magento\Framework\App\Helper\AbstractHelper
{
    public function __construct(
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Helper\Category $categoryHelper,
        \Logshub\SearchModule\Helper\Data $helper
    ) {
        $this->categoryFactory = $categoryFactory;
        $this->storeManager = $storeManager;
        $this->categoryHelper = $categoryHelper;
        
        $this->helper = $helper;
    }
    
    /**
     * 
     * @param int $storeId
     * @return array of \Logshub\SearchClient\Model\Category
     */
    public function getAllApiCategories($storeId)
    {
        $apiCategories = [];
        foreach ($this->getStoreCategories($storeId) as $cat) {
            $apiCategories = array_merge($apiCategories, $this->getApiCategories($cat));
        }
        
        return $apiCategories;
    }
    
    /**
     * 
     * @param array $ids
     * @return array of \Logshub\SearchClient\Model\Category
     */
    public function getApiCategoriesByIds(array $ids)
    {
        $apiCategories = [];
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        foreach ($ids as $categoryId) {
            $cat = $objectManager->create('Magento\Catalog\Model\Category')->load($categoryId);
            $apiCategories = array_merge($apiCategories, $this->helper->getApiCategories($cat));
        }
        
        return $apiCategories;
    }
    
    /**
     * Returns new API Category, without child categories
     * @param \Magento\Catalog\Model\Category $category
     * @return \Logshub\SearchClient\Model\Category
     */
    public function getApiCategory(\Magento\Catalog\Model\Category $category)
    {
        $apiCategory = new \Logshub\SearchClient\Model\Category('c'.$category->getId(), [
            'name' => $category->getName(),
            'url' => $this->categoryHelper->getCategoryUrl($category),
            // TODO: other attributes: description, urlImage
        ]);
        
        return $apiCategory;
    }
    
    /**
     * Pass main category, and get it converted with sub-categories
     * @param \Magento\Catalog\Model\Category $category Main category
     * @return array
     */
    public function getApiCategories(\Magento\Catalog\Model\Category $category)
    {
        $apiCategories = [];
        $apiCat = $this->getApiCategory($category);
        foreach ($this->getChildCategories($category) as $subcat) {
            $apiSubCat = $this->getApiCategory($subcat);
            $apiSubCat->addCategory($apiCat->getName());
            $apiCategories[] = $apiSubCat;
            foreach ($this->getChildCategories($subcat) as $subsubcat) {
                $apiSubSubCat = $this->getApiCategory($subsubcat);
                $apiSubSubCat->addCategory($apiCat->getName());
                $apiSubSubCat->addCategory($apiSubCat->getName());
                $apiCategories[] = $apiSubSubCat;
                foreach ($this->getChildCategories($subsubcat) as $subsubsubcat) {
                    $apiSubSubSubCat = $this->getApiCategory($subsubsubcat);
                    $apiSubSubSubCat->addCategory($apiCat->getName());
                    $apiSubSubSubCat->addCategory($apiSubCat->getName());
                    $apiSubSubSubCat->addCategory($apiSubSubCat->getName());
                    $apiCategories[] = $apiSubSubSubCat;
                }
            }
        }
        $apiCategories[] = $apiCat;
        
        return $apiCategories;
    }
    
    /**
     * 
     * @param int $storeId
     * @param bool $sorted
     * @param bool $asCollection
     * @param bool $toLoad
     * @return \Magento\Framework\Data\Tree\Node\Collection|\Magento\Catalog\Model\ResourceModel\Category\Collection
     */
    public function getStoreCategories($storeId, $sorted = false, $asCollection = true, $toLoad = true)
    {
        $parent = $this->storeManager->getStore($storeId)->getRootCategoryId();
        
        return $this->categoryFactory->create()->getCategories($parent, 3, $sorted, $asCollection, $toLoad);
    }
    
    /**
     * Retrieve child store categories
     * @return \Magento\Framework\Data\Tree\Node\Collection|\Magento\Catalog\Model\ResourceModel\Category\Collection
     */
    public function getChildCategories(\Magento\Catalog\Model\Category $category)
    {
        // TODO: check if category is flat
        // \Magento\Catalog\Model\Indexer\Category\Flat\State
        //  if ($this->categoryFlatConfig->isFlatEnabled() && $category->getUseFlatResource()) {
        // $subcategories = (array)$category->getChildrenNodes();
        return $this->categoryFactory->create()->getCategories($category->getId(), 3, false, true, true);
    }
}