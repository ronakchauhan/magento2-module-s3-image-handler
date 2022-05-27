<?php
/**
 * @author Ronak Chauhan (ronak.chauhan@y1.de)
 * @package Y1_S3ImageHandler
 */
namespace Y1\S3ImageHandler\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;
use Y1\S3ImageHandler\Helper\Data;

/**
 * Class Region
 *
 * @package Y1\S3ImageHandler\Model\Config\Source
 */
class Region implements ArrayInterface
{
    /**
     * @var \Y1\S3ImageHandler\Helper\Data
     */
    private $helper;

    /**
     * Region constructor.
     *
     * @param \Y1\S3ImageHandler\Helper\Data $helper
     */
    public function __construct(Data $helper)
    {

        $this->helper = $helper;
    }

    /**
     * @inheritDoc
     */
    public function toOptionArray()
    {
        return $this->helper->getRegions();
    }
}