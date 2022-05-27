<?php
/**
 * @author Ronak Chauhan (ronak.chauhan@y1.de)
 * @package Y1_S3ImageHandler
 */
namespace Y1\S3ImageHandler\Plugin\Model\MediaStorage\Config\Source\Storage\Media;

/**
 * Class Storage
 *
 * @package Y1\S3ImageHandler\Plugin\Model\MediaStorage\Config\Source\Storage\Media
 */
class Storage
{
    /**
     * @param Storage $subject
     * @param array $result
     *
     * @return array
     */
    public function afterToOptionArray(
        \Magento\MediaStorage\Model\Config\Source\Storage\Media\Storage $subject,
        $result
    ) {
        $result[] = [
            'value' => \Y1\S3ImageHandler\Model\MediaStorage\File\Storage::STORAGE_MEDIA_S3,
            'label' => __('Amazon S3'),
        ];

        return $result;
    }
}
