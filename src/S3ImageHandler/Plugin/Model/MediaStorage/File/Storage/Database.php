<?php
/**
 * @author Ronak Chauhan (ronak.chauhan@y1.de)
 * @package Y1_S3ImageHandler
 */
namespace Y1\S3ImageHandler\Plugin\Model\MediaStorage\File\Storage;

use Y1\S3ImageHandler\Helper\Data;
use Y1\S3ImageHandler\Model\MediaStorage\File\Storage\S3ImageHandler;

/**
 * Class Database
 *
 * @package Y1\S3ImageHandler\Plugin\Model\MediaStorage\File\Storage
 */
class Database
{
    /**
     * @var \Y1\S3ImageHandler\Helper\Data
     */
    private $helper;

    /**
     * @var \Y1\S3ImageHandler\Model\MediaStorage\File\Storage\S3ImageHandler
     */
    private $storageModel;

    /**
     * Database constructor.
     *
     * @param \Y1\S3ImageHandler\Helper\Data $helper
     * @param \Y1\S3ImageHandler\Model\MediaStorage\File\Storage\S3ImageHandler $storageModel
     */
    public function __construct(
        Data $helper,
        S3ImageHandler $storageModel
    ) {
        $this->helper = $helper;
        $this->storageModel = $storageModel;
    }

    /**
     * @param Database $subject
     * @param \Closure $proceed
     * @param string $directory
     * @return array
     */
    public function aroundGetDirectoryFiles($subject, $proceed, $directory)
    {
        if ($this->helper->checkS3Usage()) {
            return $this->storageModel->getDirectoryFiles($directory);
        }

        return $proceed($directory);
    }
}
