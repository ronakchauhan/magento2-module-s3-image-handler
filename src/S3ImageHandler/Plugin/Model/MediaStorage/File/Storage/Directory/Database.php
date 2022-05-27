<?php
/**
 * @author Ronak Chauhan (ronak.chauhan@y1.de)
 * @package Y1_S3ImageHandler
 */
namespace Y1\S3ImageHandler\Plugin\Model\MediaStorage\File\Storage\Directory;

use Y1\S3ImageHandler\Helper\Data;
use Y1\S3ImageHandler\Model\MediaStorage\File\Storage\S3ImageHandler;

/**
 * Class Database
 *
 * @package Y1\S3ImageHandler\Plugin\Model\MediaStorage\File\Storage\Directory
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
     * @param string $path
     * @return $this
     */
    public function aroundCreateRecursive($subject, $proceed, $path)
    {
        if ($this->helper->checkS3Usage()) {
            return $this;
        }

        return $proceed($path);
    }

    /**
     * @param Database $subject
     * @param \Closure $proceed
     * @param string $directory
     * @return array
     */
    public function aroundGetSubdirectories($subject, $proceed, $directory)
    {
        if ($this->helper->checkS3Usage()) {
            return $this->storageModel->getSubdirectories($directory);
        }

        return $proceed($directory);

    }

    /**
     * @param $subject
     * @param $proceed
     * @param $path
     *
     * @return \Y1\S3ImageHandler\Model\MediaStorage\File\Storage\S3ImageHandler
     */
    public function aroundDeleteDirectory($subject, $proceed, $path)
    {
        if ($this->helper->checkS3Usage()) {
            return $this->storageModel->deleteDirectory($path);
        }

        return $proceed($path);
    }
}
