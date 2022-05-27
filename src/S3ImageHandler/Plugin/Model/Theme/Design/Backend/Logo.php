<?php
/**
 * @author Ronak Chauhan (ronak.chauhan@y1.de)
 * @package Y1_S3ImageHandler
 */
namespace Y1\S3ImageHandler\Plugin\Model\Theme\Design\Backend;

use Magento\MediaStorage\Helper\File\Storage\Database;
use Y1\S3ImageHandler\Helper\Data;

/**
 * Class Logo
 *
 * @package Y1\S3ImageHandler\Plugin\Model\Theme\Design\Backend
 */
class Logo
{
    /**
     * @var Data
     */
    private $helper;

    /**
     * @var Database
     */
    private $database;

    /**
     * @param Data $helper
     * @param Database $database
     */
    public function __construct(
        Data $helper,
        Database $database
    )
    {
        $this->helper = $helper;
        $this->database = $database;
    }

    /**
     * @param Logo $subject
     * @param Logo $result
     * @return Logo
     */
    public function afterBeforeSave(\Magento\Theme\Model\Design\Backend\Logo $subject, Logo $result)
    {
        if ($this->helper->checkS3Usage()) {
            $imgFile = $subject::UPLOAD_DIR . '/' . $subject->getValue();
            $relativeImgFile = $this->database->getMediaRelativePath($imgFile);
            $this->database->getStorageDatabaseModel()->saveFile($relativeImgFile);
        }

        return $result;
    }
}
