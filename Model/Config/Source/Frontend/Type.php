<?php
namespace Logshub\SearchModule\Model\Config\Source\Frontend;

class Type implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [['value' => 'fullscreen', 'label' => __('Fullscreen')]];
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return ['fullscreen' => __('Fullscreen')];
    }
}
