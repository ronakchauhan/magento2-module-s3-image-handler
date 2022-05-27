<?php

namespace Y1\S3ImageHandler\Controller\Adminhtml\Product\Gallery;

use Magento\Framework\App\Action\HttpPostActionInterface as HttpPostActionInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Y1\S3ImageHandler\Helper\Data;
use Y1\S3ImageHandler\Plugin\Framework\File\Uploader;

class Upload extends \Magento\Catalog\Controller\Adminhtml\Product\Gallery\Upload implements HttpPostActionInterface
{

    /**
     * @var \Magento\Framework\Image\AdapterFactory|null
     */
    private $adapterFactory;
    /**
     * @var \Magento\Framework\Filesystem|null
     */
    private $filesystem;
    /**
     * @var \Magento\Catalog\Model\Product\Media\Config|null
     */
    private $productMediaConfig;
    /**
     * @var \Y1\S3ImageHandler\Helper\Data
     */
    private $helper;

    /**
     * @var array
     */
    private $allowedMimeTypes = [
        'jpg' => 'image/jpg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/png',
        'png' => 'image/gif'
    ];

    /**
     * Upload constructor.
     *
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Controller\Result\RawFactory $resultRawFactory
     * @param \Magento\Framework\Image\AdapterFactory|null $adapterFactory
     * @param \Magento\Framework\Filesystem|null $filesystem
     * @param \Magento\Catalog\Model\Product\Media\Config|null $productMediaConfig
     * @param \Y1\S3ImageHandler\Helper\Data $helper
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Controller\Result\RawFactory $resultRawFactory,
        \Magento\Framework\Image\AdapterFactory $adapterFactory = null,
        \Magento\Framework\Filesystem $filesystem = null,
        \Magento\Catalog\Model\Product\Media\Config $productMediaConfig = null,
        Data $helper
    ) {
        parent::__construct($context, $resultRawFactory, $adapterFactory, $filesystem, $productMediaConfig);
        $this->resultRawFactory = $resultRawFactory;
        $this->adapterFactory = $adapterFactory;
        $this->filesystem = $filesystem;
        $this->productMediaConfig = $productMediaConfig;
        $this->helper = $helper;
    }

    /**
     * Upload image(s) to the product gallery.
     *
     * @return \Magento\Framework\Controller\Result\Raw
     */
    public function execute()
    {
        try {
            if ($this->helper->checkS3Usage()) {
                $uploader = $this->_objectManager->create(
                    \Y1\S3ImageHandler\Model\MediaStorage\File\Uploader::class,
                    ['fileId' => 'image']
                );

                $uploader->setAllowedExtensions($this->getAllowedExtensions());
                $imageAdapter = $this->adapterFactory->create();
                $uploader->addValidateCallback('catalog_product_image', $imageAdapter, 'validateUploadFile');
                $uploader->setAllowRenameFiles(true);
                $uploader->setFilesDispersion(true);
                $result = $uploader->save(
                    $this->productMediaConfig->getBaseTmpMediaPath()
                );

            } else {
                $uploader = $this->_objectManager->create(
                    \Magento\MediaStorage\Model\File\Uploader::class,
                    ['fileId' => 'image']
                );
                $uploader->setAllowedExtensions($this->getAllowedExtensions());
                $imageAdapter = $this->adapterFactory->create();
                $uploader->addValidateCallback('catalog_product_image', $imageAdapter, 'validateUploadFile');
                $uploader->setAllowRenameFiles(true);
                $uploader->setFilesDispersion(true);
                $mediaDirectory = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA);
                $result = $uploader->save(
                    $mediaDirectory->getAbsolutePath($this->productMediaConfig->getBaseTmpMediaPath())
                );
            }


            $this->_eventManager->dispatch(
                'catalog_product_gallery_upload_image_after',
                ['result' => $result, 'action' => $this]
            );

            unset($result['tmp_name']);
            unset($result['path']);

            $result['url'] = $this->productMediaConfig->getTmpMediaUrl($result['file']);
            $result['file'] = $result['file'] . '.tmp';
        } catch (\Exception $e) {
            $result = ['error' => $e->getMessage(), 'errorcode' => $e->getCode()];
        }

        /** @var \Magento\Framework\Controller\Result\Raw $response */
        $response = $this->resultRawFactory->create();
        $response->setHeader('Content-type', 'text/plain');
        $response->setContents(json_encode($result));
        return $response;
    }

    /**
     * Get the set of allowed file extensions.
     *
     * @return array
     */
    private function getAllowedExtensions()
    {
        return array_keys($this->allowedMimeTypes);
    }
}