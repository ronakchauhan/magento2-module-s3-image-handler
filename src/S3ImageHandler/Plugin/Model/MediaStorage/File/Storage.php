<?php
/**
 * @author Ronak Chauhan (ronak.chauhan@y1.de)
 * @package Y1_S3ImageHandler
 */
namespace Y1\S3ImageHandler\Plugin\Model\MediaStorage\File;

use Y1\S3ImageHandler\Model\MediaStorage\File\Storage\S3ImageHandlerFactory;

/**
 * Class Storage
 *
 * @package Y1\S3ImageHandler\Plugin\Model\MediaStorage\File
 */
class Storage
{
    /**
     * @var S3ImagehandlerFactory
     */
    private $s3ImagehandlerFactory;

    /**
     * @var \Magento\MediaStorage\Helper\File\Storage
     */
    private $storageHelper = null;

    /**
     * @param S3ImagehandlerFactory $s3ImagehandlerFactory
     * @param \Magento\MediaStorage\Helper\File\Storage $storageHelper
     */
    public function __construct(
        S3ImagehandlerFactory $s3ImagehandlerFactory,
        \Magento\MediaStorage\Helper\File\Storage $storageHelper
    ) {
        $this->s3ImagehandlerFactory = $s3ImagehandlerFactory;
        $this->storageHelper = $storageHelper;
    }

    /**
     * @param \Magento\MediaStorage\Model\File\Storage $subject
     * @param $proceed
     * @param null $storage
     * @param array $params
     *
     * @return false|\Y1\S3ImageHandler\Model\MediaStorage\File\Storage\S3ImageHandler
     */
    public function aroundGetStorageModel(\Magento\MediaStorage\Model\File\Storage $subject, $proceed, $storage = null, array $params = [])
    {
        $storageModel = $proceed($storage, $params);
        if ($storageModel === false) {
            if (null === $storage) {
                $storage = $this->storageHelper->getCurrentStorageCode();
            }
            switch ($storage) {
                case \Y1\S3ImageHandler\Model\MediaStorage\File\Storage::STORAGE_MEDIA_S3:
                    $storageModel = $this->s3ImagehandlerFactory->create();
                    break;
                default:
                    return false;
            }

            if (isset($params['init']) && $params['init']) {
                $storageModel->init();
            }
        }

        return $storageModel;
    }
}
