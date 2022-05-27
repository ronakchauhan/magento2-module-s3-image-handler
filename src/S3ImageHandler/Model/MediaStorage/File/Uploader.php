<?php

namespace Y1\S3ImageHandler\Model\MediaStorage\File;

use Exception;
use Magento\MediaStorage\Helper\File\Storage\Database;
use Magento\MediaStorage\Model\File\Validator\NotProtectedExtension;
use Y1\S3ImageHandler\Model\MediaStorage\File\Storage\S3ImageHandler;

class Uploader extends \Magento\Framework\File\Uploader
{
    /**
     * @var string
     * @access protected
     */
    protected $_dispretionPath = null;

    /**
     * @var bool
     */
    protected $_fileExists = false;

    /**
     * @var null|string[]
     */
    protected $_allowedExtensions = null;

    /**
     * Flag, that defines should DB processing be skipped
     *
     * @var bool
     */
    protected $_skipDbProcessing = false;

    /**
     * Core file storage
     *
     * @var \Magento\MediaStorage\Helper\File\Storage
     */
    protected $_coreFileStorage = null;

    /**
     * Core file storage database
     *
     * @var \Magento\MediaStorage\Helper\File\Storage\Database
     */
    protected $_coreFileStorageDb = null;

    /**
     * @var \Magento\MediaStorage\Model\File\Validator\NotProtectedExtension
     */
    protected $_validator;
    /**
     * @var \Y1\S3ImageHandler\Model\MediaStorage\File\Storage\S3ImageHandler
     */
    private $s3ImageHandler;

    /**
     * @param string $fileId
     * @param \Magento\MediaStorage\Helper\File\Storage\Database $coreFileStorageDb
     * @param \Magento\MediaStorage\Helper\File\Storage $coreFileStorage
     * @param \Magento\MediaStorage\Model\File\Validator\NotProtectedExtension $validator
     * @param \Y1\S3ImageHandler\Model\MediaStorage\File\Storage\S3ImageHandler $s3ImageHandler
     */
    public function __construct(
        $fileId,
        Database $coreFileStorageDb,
        \Magento\MediaStorage\Helper\File\Storage $coreFileStorage,
        NotProtectedExtension $validator,
        S3ImageHandler $s3ImageHandler
    ) {
        $this->_coreFileStorageDb = $coreFileStorageDb;
        $this->_coreFileStorage = $coreFileStorage;
        $this->_validator = $validator;
        parent::__construct($fileId);
        $this->s3ImageHandler = $s3ImageHandler;
    }

    public function save($destinationFolder, $newFileName = null)
    {
        $this->_result = false;
        $destinationFile = $destinationFolder;
        $fileName = isset($newFileName) ? $newFileName : $this->_file['name'];
        $fileName = \Magento\Framework\File\Uploader::getCorrectFileName($fileName);

        $this->_dispretionPath = static::getDispersionPath($fileName);
        $destinationFile .= $this->_dispretionPath;
        $destinationFile = static::_addDirSeparator($destinationFile) . $fileName;

        if ($this->_allowRenameFiles) {
            $fileName = static::getNewFileName(
                static::_addDirSeparator($destinationFile) . $fileName
            );
        }

        try {
            $this->_result = $this->s3ImageHandler->saveTempFile($this->_file, $destinationFile);
        } catch (Exception $e) {
            // if the file exists and we had an exception continue anyway
            if (file_exists($destinationFile)) {
                $this->_result = true;
            } else {
                throw $e;
            }
        }

        if ($this->_result) {
            if ($this->_enableFilesDispersion) {
                $fileName = str_replace('\\', '/', self::_addDirSeparator($this->_dispretionPath)) . $fileName;
            }
            $this->_uploadedFileName = $fileName;
            $this->_uploadedFileDir = $destinationFolder;
            $this->_result = $this->_file;
            $this->_result['path'] = $destinationFolder;
            $this->_result['file'] = $fileName;
        }

        return $this->_result;
    }

    /**
     * Save file to storage
     *
     * @param array $result
     *
     * @return \Y1\S3ImageHandler\Model\MediaStorage\File\Uploader
     */
    protected function _afterSave($result)
    {
        if (empty($result['path']) || empty($result['file'])) {
            return $this;
        }

        if ($this->_coreFileStorage->isInternalStorage() || $this->skipDbProcessing()) {
            return $this;
        }

        $this->_result['file'] = $this->_coreFileStorageDb->saveUploadedFile($result);

        return $this;
    }

    /**
     * Getter/Setter for _skipDbProcessing flag
     *
     * @param null|bool $flag
     *
     * @return bool|\Y1\S3ImageHandler\Model\MediaStorage\File\Uploader
     */
    public function skipDbProcessing($flag = null)
    {
        if ($flag === null) {
            return $this->_skipDbProcessing;
        }
        $this->_skipDbProcessing = (bool) $flag;

        return $this;
    }

    /**
     * Check protected/allowed extension
     *
     * @param string $extension
     *
     * @return boolean
     */
    public function checkAllowedExtension($extension)
    {
        //validate with protected file types
        if (!$this->_validator->isValid($extension)) {
            return false;
        }

        return parent::checkAllowedExtension($extension);
    }

    /**
     * Get file size
     *
     * @return int
     */
    public function getFileSize()
    {
        return $this->_file['size'];
    }

    /**
     * Validate file
     *
     * @return array
     */
    public function validateFile()
    {
        $this->_validateFile();

        return $this->_file;
    }
}