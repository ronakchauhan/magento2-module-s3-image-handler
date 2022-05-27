<?php
/**
 * @author Ronak Chauhan (ronak.chauhan@y1.de)
 * @package Y1_S3ImageHandler
 */

namespace Y1\S3ImageHandler\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Encryption\EncryptorInterface;
use Y1\S3ImageHandler\Model\MediaStorage\File\Storage;

/**
 * Class Data
 *
 * @package Y1\S3ImageHandler\Helper
 */
class Data extends AbstractHelper
{
    /**
     * @var \Magento\Framework\Encryption\EncryptorInterface
     */
    private $encryptor;

    /**
     * Data constructor.
     *
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Framework\Encryption\EncryptorInterface $encryptor
     */
    public function __construct(Context $context, EncryptorInterface $encryptor)
    {
        parent::__construct($context);
        $this->encryptor = $encryptor;
    }

    /**
     * @var bool
     */
    private $useS3;

    /**
     * Check whether we are allowed to use S3 as our file storage backend.
     *
     * @return bool
     */
    public function checkS3Usage()
    {
        if (null === $this->useS3) {
            $currentStorage = (int)$this->scopeConfig->getValue(Storage::XML_PATH_STORAGE_MEDIA);
            $this->useS3 = $currentStorage === Storage::STORAGE_MEDIA_S3;
        }

        return $this->useS3;
    }

    /**
     * Get the key used to reference the file in S3.
     *
     * @param string $filePath
     * @return string
     */
    public function getObjectKey($filePath)
    {
        $prefix = $this->getConfigValuePrefix();
        if ($prefix) {
            $filePath = ltrim($prefix, '/') . '/' . $filePath;
        }
        return $this->getConfigValueBucket() . '/' . $filePath;
    }

    /**
     * Returns the AWS access key.
     *
     * @return string
     */
    public function getConfigValueAccessKey()
    {
        return $this->encryptor->decrypt($this->scopeConfig->getValue('s3_image_handler/general/access_key'));
    }

    /**
     * Returns the AWS secret key.
     *
     * @return string
     */
    public function getConfigValueSecretKey()
    {
        return $this->encryptor->decrypt($this->scopeConfig->getValue('s3_image_handler/general/secret_key'));
    }

    /**
     * Returns the AWS region that we're using, e.g. ap-southeast-2.
     *
     * @return string
     */
    public function getConfigValueRegion()
    {
        return $this->scopeConfig->getValue('s3_image_handler/general/region');
    }

    /**
     * Returns the S3 bucket where we want to store all our images.
     *
     * @return string
     */
    public function getConfigValueBucket()
    {
        return $this->scopeConfig->getValue('s3_image_handler/general/bucket');
    }

    /**
     * Returns the string that we want to prepend to all of our S3 object keys.
     *
     * @return string
     */
    public function getConfigValuePrefix()
    {
        return $this->scopeConfig->getValue('s3_image_handler/general/prefix');
    }

    /**
     * @return bool
     */
    public function getConfigValueCustomEndpointEnabled()
    {
        return (bool)$this->scopeConfig->getValue('s3_image_handler/custom_endpoint/enabled');
    }

    /**
     * Returns the S3 bucket where we want to store all our images.
     *
     * @return string
     */
    public function getServerlessUrlEnabled()
    {
        return $this->scopeConfig->getValue('s3_image_handler/serveless/enabled');
    }

    /**
     * @return string
     */
    public function getServerlessUrlEndpoint()
    {
        return $this->scopeConfig->getValue('s3_image_handler/serveless/endpoint');
    }

    /**
     * @return string
     */
    public function getServerlessUrlCustomEndpoint()
    {
        return $this->scopeConfig->getValue('s3_image_handler/serveless/custom_endpoint');

    }

    /**
     * @return string
     */
    public function getConfigValueCustomEndpoint()
    {
        return $this->scopeConfig->getValue('s3_image_handler/custom_endpoint/endpoint');
    }

    /**
     * @return string
     */
    public function getConfigValueHeaderCacheControl()
    {
        return $this->scopeConfig->getValue('s3_image_handler/headers/cache_control');
    }

    /**
     * @return string
     */
    public function getConfigValueHeaderExpires()
    {
        return $this->scopeConfig->getValue('s3_image_handler/headers/expires');
    }

    /**
     * @return string
     */
    public function getConfigValueCustomHeaders()
    {
        return $this->scopeConfig->getValue('s3_image_handler/headers/custom_headers');
    }

    /**
     * @param $regionInQuestion
     *
     * @return bool
     */
    public function isValidRegion($regionInQuestion)
    {
        foreach (is_array($this->getConfigValueRegion()) as $currentRegion) {
            if ($currentRegion['value'] == $regionInQuestion) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return \string[][]
     */
    public function getRegions()
    {
        return [
            [
                'value' => 'us-east-1',
                'label' => 'US East (N. Virginia)'
            ],
            [
                'value' => 'us-west-2',
                'label' => 'US West (Oregon)'
            ],
            [
                'value' => 'us-west-1',
                'label' => 'US West (N. California)'
            ],
            [
                'value' => 'eu-west-1',
                'label' => 'EU (Ireland)'
            ],
            [
                'value' => 'eu-central-1',
                'label' => 'EU (Frankfurt)'
            ],
            [
                'value' => 'ap-southeast-1',
                'label' => 'Asia Pacific (Singapore)'
            ],
            [
                'value' => 'ap-northeast-1',
                'label' => 'Asia Pacific (Tokyo)'
            ],
            [
                'value' => 'ap-southeast-2',
                'label' => 'Asia Pacific (Sydney)'
            ],
            [
                'value' => 'ap-northeast-2',
                'label' => 'Asia Pacific (Seoul)'
            ],
            [
                'value' => 'sa-east-1',
                'label' => 'South America (Sao Paulo)'
            ]
        ];
    }
}
