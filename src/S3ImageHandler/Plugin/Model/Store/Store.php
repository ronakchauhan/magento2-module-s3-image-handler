<?php
/**
 * @author Ronak Chauhan (ronak.chauhan@y1.de)
 * @package Y1_S3ImageHandler
 */
namespace Y1\S3ImageHandler\Plugin\Model\Store;

/**
 * Class Store
 *
 * @package Y1\S3ImageHandler\Plugin\Model\Store
 */
class Store
{
    /**
     * This plugin fixes a bug where Magento incorrectly appends two forward
     * slashes to the media rewrite script. We remove one of those extra forward
     * slashes.
     *
     * @param Store $subject
     * @param string $result
     * @return string
     */
    public function afterGetBaseUrl(\Magento\Store\Model\Store $subject, $result)
    {
        return str_replace('//get.php/', '/get.php/', $result);
    }
}
