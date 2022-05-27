<?php
/**
 * @author Ronak Chauhan (ronak.chauhan@y1.de)
 * @package Y1_S3ImageHandler
 */
namespace Y1\S3ImageHandler\Plugin\Model\MediaStorage\File\Storage;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem;
use Magento\MediaStorage\Model\File\Storage\Synchronization;
use Y1\S3ImageHandler\Model\MediaStorage\File\Storage\S3ImageHandlerFactory;

/**
 * Class Synchronisation
 *
 * @package Y1\S3ImageHandler\Plugin\Model\MediaStorage\File\Storage
 */
class Synchronisation
{
    /**
     * @var S3ImageHandlerFactory
     */
    private $storageFactory;

    /**
     * @var Filesystem\Directory\WriteInterface
     */
    private $mediaDirectory;

    /**
     * @param S3ImageHandlerFactory $storageFactory
     * @param Filesystem $filesystem
     * @throws FileSystemException
     */
    public function __construct(
        S3ImageHandlerFactory $storageFactory,
        Filesystem $filesystem
    ) {
        $this->storageFactory = $storageFactory;
        $this->mediaDirectory = $filesystem->getDirectoryWrite(DirectoryList::MEDIA);
    }

    /**
     * @param Synchronization $subject
     * @param string $relativeFileName
     * @return array
     */
    public function beforeSynchronize(\Magento\MediaStorage\Model\File\Storage\Synchronization $subject, $relativeFileName)
    {
        $storage = $this->storageFactory->create();
        try {
            $storage->loadByFilename($relativeFileName);
        } catch (\Exception $e) {
        }

        if ($storage->getId()) {
            $file = $this->mediaDirectory->openFile($relativeFileName, 'w');
            try {
                $file->lock();
                $file->write($storage->getContent());
                $file->unlock();
                $file->close();
            } catch (FileSystemException $e) {
                $file->close();
            }
        }

        return [
            $relativeFileName,
        ];
    }
}
