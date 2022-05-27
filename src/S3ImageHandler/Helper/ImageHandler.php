<?php
/**
 * @author Ronak Chauhan (ronak.chauhan@y1.de)
 * @package Y1_S3ImageHandler
 */

namespace Y1\S3ImageHandler\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Y1\S3ImageHandler\Model\MediaStorage\File\Storage;

/**
 * Class Data
 *
 * @package Y1\S3ImageHandler\Helper
 */
class ImageHandler extends AbstractHelper
{
    /**
     * @var \Magento\Framework\Encryption\EncryptorInterface
     */
    private $encryptor;
    /**
     * @var \Y1\S3ImageHandler\Model\MediaStorage\File\Storage\S3ImageHandler
     */
    private $imageHandler;
    /**
     * @var \Magento\Framework\App\Helper\Context
     */
    private $context;

    /**
     * Data constructor.
     *
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Y1\S3ImageHandler\Model\MediaStorage\File\Storage\S3ImageHandler $imageHandler
     */
    public function __construct(Context $context, Storage\S3ImageHandler $imageHandler)
    {
        parent::__construct($context);
        $this->imageHandler = $imageHandler;
        $this->context = $context;
    }

    /**
     * Check whether we are allowed to use S3 as our file storage backend.
     *
     * @return bool
     */
    public function resizeObject($params)
    {
        $object = $this->imageHandler->resizeObject($params);

        return $object;
    }
}
