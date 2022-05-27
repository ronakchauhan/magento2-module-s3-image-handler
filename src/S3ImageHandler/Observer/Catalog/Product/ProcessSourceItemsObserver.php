<?php

namespace Y1\S3ImageHandler\Observer\Catalog\Product;

use Magento\Catalog\Controller\Adminhtml\Product\Save;
use Magento\Catalog\Model\Product\Gallery\CreateHandler;
use Magento\Catalog\Model\Product\Media\Config;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Driver\File;

class ProcessSourceItemsObserver implements ObserverInterface
{
    /**
     * @var \Magento\Catalog\Model\Product\Gallery\CreateHandler
     */
    private $catalogCreateHandler;

    /**
     * @var \Magento\Framework\Filesystem\Driver\File
     */
    private $fileDriver;
    /**
     * @var \Magento\Framework\Filesystem
     */
    private $filesystem;
    /**
     * @var \Magento\Catalog\Model\Product\Media\Config
     */
    private $mediaConfig;

    /**
     * ProcessSourceItemsObserver constructor.
     *
     * @param \Magento\Catalog\Model\Product\Gallery\CreateHandler $catalogCreateHandler
     * @param \Magento\Framework\Filesystem\Driver\File $fileDriver
     * @param \Magento\Catalog\Model\Product\Media\Config $mediaConfig
     * @param \Magento\Framework\Filesystem $filesystem
     */
    public function __construct(
        CreateHandler $catalogCreateHandler,
        File $fileDriver,
        Config $mediaConfig,
        Filesystem $filesystem
    ) {

        $this->catalogCreateHandler = $catalogCreateHandler;
        $this->fileDriver = $fileDriver;
        $this->filesystem = $filesystem;
        $this->mediaConfig = $mediaConfig;
    }

    /**
     * Process source items during product saving via controller
     *
     * @param EventObserver $observer
     *
     * @return void
     */
    public function execute(EventObserver $observer)
    {
        /** @var \Magento\Catalog\Api\Data\ProductInterface $product */
        $product = $observer->getEvent()->getProduct();

        /** @var Save $controller */
        $controller = $observer->getEvent()->getController();
        $productData = $controller->getRequest()->getParam('product', []);

        $attrCode = $this->catalogCreateHandler->getAttribute()->getAttributeCode();
        $imagesGallery = $product->getData($attrCode);
        $mediaDirectory = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA);
        $mediaRootDir = $mediaDirectory->getAbsolutePath();
        foreach ($imagesGallery['images'] as $image) {
            $mediastoragefilename = $this->mediaConfig->getMediaShortUrl($image['file']);
            if ($this->fileDriver->isExists($mediaRootDir . $mediastoragefilename)) {
                $this->fileDriver->deleteFile($mediaRootDir . $mediastoragefilename);
            }
        }
    }
}