<?php
/**
 * @author Ronak Chauhan (ronak.chauhan@y1.de)
 * @package Y1_S3ImageHandler
 */
namespace Y1\S3ImageHandler\Plugin\Model\Captcha;

use Magento\MediaStorage\Helper\File\Storage\Database;
use Y1\S3ImageHandler\Helper\Data;

/**
 * Class DefaultModel
 *
 * @package Y1\S3ImageHandler\Plugin\Model\Captcha
 */
class DefaultModel
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
    ) {
        $this->helper = $helper;
        $this->database = $database;
    }

    /**
     * @param DefaultModel $subject
     * @param \Closure $result
     * @return mixed
     */
    public function afterGenerate(\Magento\Captcha\Model\DefaultModel $subject, $result)
    {
        if ($this->helper->checkS3Usage()) {
            $imgFile = $subject->getImgDir() . $result . $subject->getSuffix();
            $relativeImgFile = $this->database->getMediaRelativePath($imgFile);
            $this->database->getStorageDatabaseModel()->saveFile($relativeImgFile);
        }

        return $result;
    }
}
