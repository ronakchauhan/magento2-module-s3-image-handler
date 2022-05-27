<?php
/**
 * @author Ronak Chauhan (ronak.chauhan@y1.de)
 * @package Y1_S3ImageHandler
 */
namespace Y1\S3ImageHandler\Model\ResourceModel\MediaStorage\File\Storage;

use Magento\Framework\Filesystem;
use Magento\MediaStorage\Helper\File\Storage\Database;
use Psr\Log\LoggerInterface;

/**
 * Class File
 *
 * @package Y1\S3ImageHandler\Model\ResourceModel\MediaStorage\File\Storage
 */
class File extends \Magento\MediaStorage\Model\ResourceModel\File\Storage\File
{
    /**
     * @var Database
     */
    protected $fileStorageDb;

    /**
     * @param Filesystem $filesystem
     * @param LoggerInterface $log
     * @param Database $fileStorageDb
     */
    public function __construct(
        Filesystem $filesystem,
        LoggerInterface $log,
        Database $fileStorageDb
    ) {
        parent::__construct($filesystem, $log);

        $this->fileStorageDb = $fileStorageDb;
    }

    /**
     * Extend the original functionality of this method by also uploading the
     * requested file to S3.
     *
     * @param string $filePath
     * @param string $content
     * @param bool $overwrite
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function saveFile($filePath, $content, $overwrite = false)
    {
        $result = parent::saveFile($filePath, $content, $overwrite);

        if ($result) {
            $this->fileStorageDb->saveFile($filePath);
        }

        return $result;
    }
}