<?php
/**
 * @author Ronak Chauhan (ronak.chauhan@y1.de)
 * @package Y1_S3ImageHandler
 */
namespace Y1\S3ImageHandler\Plugin\Block\MediaStorage\System\Config\System\Storage\Media;

/**
 * Class Synchronise
 *
 * @package Y1\S3ImageHandler\Plugin\Block\MediaStorage\System\Config\System\Storage\Media
 */
class Synchronise
{
    /**
     * @return string
     */
    public function aroundGetTemplate(\Magento\MediaStorage\Block\System\Config\System\Storage\Media\Synchronize $subject, $proceed)
    {
        return 'Y1_S3ImageHandler::system/config/system/storage/media/synchronise.phtml';
    }
}