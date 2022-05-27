<?php
/**
 * @author Ronak Chauhan (ronak.chauhan@y1.de)
 * @package Y1_S3ImageHandler
 */

namespace Y1\S3ImageHandler\Model\MediaStorage\File\Storage;

use Aws\S3\BatchDelete;
use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;
use Exception;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\DataObject;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\MediaStorage\Helper\File\Media;
use Magento\MediaStorage\Helper\File\Storage\Database;
use Psr\Log\LoggerInterface;
use Y1\S3ImageHandler\Helper\Data as DataHelper;
use Zend\Serializer\Serializer;
use function GuzzleHttp\Psr7\mimetype_from_filename;

/**
 * Class S3ImageHandler
 *
 * @package Y1\S3ImageHandler\Model\MediaStorage\File\Storage
 */
class S3ImageHandler extends DataObject
{
    /**
     * Store media base directory path
     *
     * @var string
     */
    protected $mediaBaseDirectory = null;

    /**
     * @var S3Client
     */
    private $client;

    /**
     * @var \Y1\S3ImageHandler\Helper\Data
     */
    private $helper;

    /**
     * Core file storage database
     *
     * @var \Magento\MediaStorage\Helper\File\Storage\Database
     */
    private $storageHelper;

    /**
     * @var \Magento\MediaStorage\Helper\File\Media
     */
    private $mediaHelper;

    /**
     * Collect errors during sync process
     *
     * @var string[]
     */
    private $errors = [];

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var SerializerInterface|\Zend_Serializer
     */
    private $serializer;

    /**
     * @var array
     */
    private $objects = [];

    /**
     * @var \Magento\Framework\Filesystem\Driver\File
     */
    private $fileDriver;
    /**
     * @var \Magento\Framework\Filesystem
     */
    private $filesystem;

    /**
     * @param DataHelper $helper
     * @param \Magento\MediaStorage\Helper\File\Media $mediaHelper
     * @param \Magento\MediaStorage\Helper\File\Storage\Database $storageHelper
     * @param \Magento\Framework\Filesystem\Driver\File $fileDriver
     * @param \Magento\Framework\Filesystem $filesystem
     * @param LoggerInterface $logger
     */
    public function __construct(
        DataHelper $helper,
        Media $mediaHelper,
        Database $storageHelper,
        File $fileDriver,
        Filesystem $filesystem,
        LoggerInterface $logger
    ) {
        parent::__construct();

        $this->helper = $helper;
        $this->mediaHelper = $mediaHelper;
        $this->storageHelper = $storageHelper;
        $this->logger = $logger;
        $this->serializer = $this->getSerializer();

        $options = [
            'version'     => 'latest',
            'region'      => $this->helper->getConfigValueRegion(),
            'credentials' => [
                'key'    => $this->helper->getConfigValueAccessKey(),
                'secret' => $this->helper->getConfigValueSecretKey(),
            ],
        ];

        if ($this->helper->getConfigValueCustomEndpointEnabled()) {
            if ($this->helper->getConfigValueCustomEndpoint()) {
                $options['endpoint'] = $this->helper->getConfigValueCustomEndpoint();
            }
        }

        $this->client = new S3Client($options);
        $this->fileDriver = $fileDriver;
        $this->filesystem = $filesystem;
    }

    /**
     * Retrieve Serializer depending the Magento Version
     *
     * @return bool|\Zend_Serializer|\Magento\Framework\Serialize\Serializer\Json
     */
    protected function getSerializer()
    {
        // Magento Version 2.1.*
        $serializer = Serializer::factory(\Zend\Serializer\Adapter\Json::class);

        // Magento Version 2.2.*
        if (class_exists(Json::class)) {
            return ObjectManager::getInstance()->get(
                Json::class
            );
        }

        return $serializer;
    }

    /**
     * Initialisation
     *
     * @return $this
     */
    public function init()
    {
        return $this;
    }

    /**
     * Return storage name
     *
     * @return \Magento\Framework\Phrase
     */
    public function getStorageName()
    {
        return __('Amazon S3');
    }

    /**
     * @param string $filename
     *
     * @return $this
     */
    public function loadByFilename($filename)
    {
        $fail = false;
        try {
            $object = $this->client->getObject(
                [
                    'Bucket' => $this->getBucket(),
                    'Key'    => $filename,
                ]
            );

            if ($object['Body']) {
                $this->setData('id', $filename);
                $this->setData('filename', $filename);
                $this->setData('content', (string) $object['Body']);
            } else {
                $fail = true;
            }
        } catch (S3Exception $e) {
            $fail = true;
            $this->logger->critical($e->getMessage());
        }

        if ($fail) {
            $this->unsetData();
        }

        return $this;
    }

    /**
     * @return string
     */
    protected function getBucket()
    {
        return $this->helper->getConfigValueBucket();
    }

    public function resizeObject($params)
    {
        $fail = false;
        try {
            $data = $params;
            $data['Bucket'] = $this->getBucket();
            $data['Key'] = "catalog/product" . $data['image'];
            $parseData['bucket'] = $data['Bucket'];
            $parseData['key'] = $data['Key'];
            $parseData['edits'] = $data['edits'];
            unset($data['image']);
            $str = json_encode($parseData, JSON_UNESCAPED_SLASHES);
            $object = $this->client->getObject(
                $data
            );
            if ($object['Body']) {
                $this->setData('filename', $params['image']);
                $this->setData('content', base64_encode((string) $object['Body']));
                $encodedUrl = base64_encode((string) $str);
                $serverlessEndpoint = ($this->getServerlessUrlEnabled()) ? $this->getServerlessUrlEndpoint()
                    : $this->getServerlessUrlCustomEndpoint();
                $this->setData('url', $serverlessEndpoint . $encodedUrl);
            } else {
                $fail = true;
            }
        } catch (S3Exception $e) {
            $fail = true;
            $this->logger->critical($e->getMessage());
        }

        if ($fail) {
            $this->unsetData();
        }

        return $this;
    }

    /**
     * @return string
     */
    protected function getServerlessUrlEnabled()
    {
        return $this->helper->getServerlessUrlEnabled();
    }

    /**
     * @return string
     */
    protected function getServerlessUrlEndpoint()
    {
        return $this->helper->getServerlessUrlEndpoint();
    }

    /**
     * @return string
     */
    protected function getServerlessUrlCustomEndpoint()
    {
        return $this->helper->getServerlessUrlCustomEndpoint();
    }

    /**
     * @return bool
     */
    public function hasErrors()
    {
        return !empty($this->errors);
    }

    /**
     * @return $this
     * @throws \Aws\S3\Exception\DeleteMultipleObjectsException
     */
    public function clear()
    {
        $batch = BatchDelete::fromListObjects(
            $this->client,
            [
                'Bucket' => $this->getBucket(),
            ]
        );
        $batch->delete();

        return $this;
    }

    /**
     * @param int $offset
     * @param int $count
     *
     * @return bool
     */
    public function exportDirectories($offset = 0, $count = 100)
    {
        return false;
    }

    /**
     * @param array $dirs
     *
     * @return $this
     */
    public function importDirectories(array $dirs = [])
    {
        return $this;
    }

    /**
     * Retrieve connection name
     *
     * @return null
     */
    public function getConnectionName()
    {
        return null;
    }

    /**
     * @param int $offset
     * @param int $count
     *
     * @return array|bool
     */
    public function exportFiles($offset = 0, $count = 100)
    {
        $files = [];

        if (empty($this->objects)) {
            $this->objects = $this->client->listObjects(
                [
                    'Bucket'  => $this->getBucket(),
                    'MaxKeys' => $count,
                ]
            );
        } else {
            $this->objects = $this->client->listObjects(
                [
                    'Bucket'  => $this->getBucket(),
                    'MaxKeys' => $count,
                    'Marker'  => $this->objects[count($this->objects) - 1],
                ]
            );
        }

        if (empty($this->objects)) {
            return false;
        }

        foreach ($this->objects as $object) {
            if (isset($object['Contents']) && substr($object['Contents'], -1) != '/') {
                $content = $this->client->getObject(
                    [
                        'Bucket' => $this->getBucket(),
                        'Key'    => $object['Key'],
                    ]
                );
                if (isset($content['Body'])) {
                    $files[] = [
                        'filename' => $object['Key'],
                        'content'  => (string) $content['Body'],
                    ];
                }
            }
        }

        return $files;
    }

    /**
     * @param array $files
     *
     * @return $this
     */
    public function importFiles(array $files = [])
    {
        foreach ($files as $file) {
            try {
                $this->client->putObject(
                    $this->getAllParams(
                        [
                            'Body'        => $file['content'],
                            'Bucket'      => $this->getBucket(),
                            'ContentType' => mimetype_from_filename($file['filename']),
                            'Key'         => $file['directory'] . '/' . $file['filename'],
                        ]
                    )
                );
            } catch (Exception $e) {
                $this->errors[] = $e->getMessage();
                $this->logger->critical($e);
            }
        }

        return $this;
    }

    /**
     * @param array $headers
     *
     * @return array
     */
    public function getAllParams(array $headers = [])
    {
        $headers['ACL'] = 'public-read';

        $metadata = $this->getMetadata();
        if (count($metadata) > 0) {
            $headers['Metadata'] = $metadata;
        }

        if ($this->helper->getConfigValueHeaderExpires()) {
            $headers['Expires'] = $this->helper->getConfigValueHeaderExpires();
        }

        if ($this->helper->getConfigValueHeaderCacheControl()) {
            $headers['CacheControl'] = $this->helper->getConfigValueHeaderCacheControl();
        }

        return $headers;
    }

    /**
     * @return array
     */
    public function getMetadata()
    {
        $meta = [];
        $headers = $this->helper->getConfigValueCustomHeaders();

        if ($headers) {
            $unserializedHeaders = $this->serializer->unserialize($headers);
            foreach ($unserializedHeaders as $header) {
                $meta[$header['header']] = $header['value'];
            }
        }

        return $meta;
    }

    /**
     * @param array $files
     *
     * @return $this
     */
    public function saveTempFile($file, $destinationFile)
    {
        try {
            $this->client->putObject(
                $this->getAllParams(
                    [
                        'Bucket'      => $this->getBucket(),
                        'ContentType' => mimetype_from_filename($file['name']),
                        'Key'         => $destinationFile,
                        'SourceFile'  => $file['tmp_name']
                    ]
                )
            );
        } catch (Exception $e) {
            $this->errors[] = $e->getMessage();
            $this->logger->critical($e);
        }

        return $this;
    }

    /**
     * @param string $filename
     *
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function saveFile($filename)
    {
        $file = $this->mediaHelper->collectFileInfo($this->getMediaBaseDirectory(), $filename);
        $mediaDirectory = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA);
        $mediaRootDir = $mediaDirectory->getAbsolutePath();

        $params = $this->getAllParams(
            [
                'Body'        => $file['content'],
                'Bucket'      => $this->getBucket(),
                'ContentType' => mimetype_from_filename($file['filename']),
                'Key'         => $filename,
            ]
        );
        try {
            $this->client->putObject(
                $params
            );
        } catch (Exception $e) {
            $this->errors[] = $e->getMessage();
            $this->logger->critical($e);
        }

        if ($this->fileDriver->isExists($mediaRootDir . $filename))  {
            $this->fileDriver->deleteFile($mediaRootDir . $filename);
        }

        return $this;
    }

    /**
     * Retrieve media base directory path
     *
     * @return string
     */
    public function getMediaBaseDirectory()
    {
        if (null === $this->mediaBaseDirectory) {
            $this->mediaBaseDirectory = $this->storageHelper->getMediaBaseDir();
        }

        return $this->mediaBaseDirectory;
    }

    /**
     * @param $filename
     *
     * @return bool
     */
    public function fileExists($filename)
    {
        return $this->client->doesObjectExist($this->getBucket(), $filename);
    }

    /**
     * @param string $oldFilePath
     * @param string $newFilePath
     *
     * @return $this
     */
    public function copyFile($oldFilePath, $newFilePath)
    {
        $this->client->copyObject(
            $this->getAllParams(
                [
                    'Bucket'     => $this->getBucket(),
                    'Key'        => $newFilePath,
                    'CopySource' => $this->getBucket() . '/' . $oldFilePath,
                ]
            )
        );

        return $this;
    }

    /**
     * @param string $oldFilePath
     * @param string $newFilePath
     *
     * @return $this
     */
    public function renameFile($oldFilePath, $newFilePath)
    {
        $this->client->copyObject(
            $this->getAllParams(
                [
                    'Bucket'     => $this->getBucket(),
                    'Key'        => $newFilePath,
                    'CopySource' => $this->getBucket() . '/' . $oldFilePath,
                ]
            )
        );

        $this->client->deleteObject(
            [
                'Bucket' => $this->getBucket(),
                'Key'    => $oldFilePath,
            ]
        );

        return $this;
    }

    /**
     * Delete file from Amazon S3
     *
     * @param string $path
     *
     * @return $this
     */
    public function deleteFile($path)
    {
        $this->client->deleteObject(
            [
                'Bucket' => $this->getBucket(),
                'Key'    => $path,
            ]
        );

        return $this;
    }

    /**
     * @param string $path
     *
     * @return array
     */
    public function getSubdirectories($path)
    {
        $subdirectories = [];

        $prefix = $this->storageHelper->getMediaRelativePath($path);
        $prefix = rtrim($prefix, '/') . '/';

        $objects = $this->client->listObjects(
            [
                'Bucket'    => $this->getBucket(),
                'Prefix'    => $prefix,
                'Delimiter' => '/',
            ]
        );

        if (isset($objects['CommonPrefixes'])) {
            foreach ($objects['CommonPrefixes'] as $object) {
                if (!isset($object['Prefix'])) {
                    continue;
                }

                $subdirectories[] = [
                    'name' => $object['Prefix'],
                ];
            }
        }

        return $subdirectories;
    }

    /**
     * @param string $path
     *
     * @return array
     */
    public function getDirectoryFiles($path)
    {
        $files = [];

        $prefix = $this->storageHelper->getMediaRelativePath($path);
        $prefix = rtrim($prefix, '/') . '/';

        $objects = $this->client->listObjects(
            [
                'Bucket'    => $this->getBucket(),
                'Prefix'    => $prefix,
                'Delimiter' => '/',
            ]
        );

        if (isset($objects['Contents'])) {
            foreach ($objects['Contents'] as $object) {
                if (isset($object['Key']) && $object['Key'] != $prefix) {
                    $content = $this->client->getObject(
                        [
                            'Bucket' => $this->getBucket(),
                            'Key'    => $object['Key'],
                        ]
                    );
                    if (isset($content['Body'])) {
                        $files[] = [
                            'filename' => $object['Key'],
                            'content'  => (string) $content['Body'],
                        ];
                    }
                }
            }
        }

        return $files;
    }

    /**
     * @param string $path
     *
     * @return $this
     */
    public function deleteDirectory($path)
    {
        $mediaRelativePath = $this->storageHelper->getMediaRelativePath($path);
        $prefix = rtrim($mediaRelativePath, '/') . '/';

        $this->client->deleteMatchingObjects($this->getBucket(), $prefix);

        return $this;
    }
}