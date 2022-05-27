<?php

namespace Y1\S3ImageHandler\Cron;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Driver\File;

class CleanPubMedia
{
    const MEDIA_DIRS = [
        'catalog',
        'customer',
        'theme',
        'wysiwyg',
        'downloadable',
        'captcha',
        'import',
        'theme_customization'
    ];

    /**
     * @var \Magento\Framework\Filesystem
     */
    private $filesystem;
    /**
     * @var \Magento\Framework\Filesystem\Directory\WriteInterface
     */
    private $directory;

    /**
     * CleanPubMedia constructor.
     *
     * @param \Magento\Framework\Filesystem $filesystem
     */
    public function __construct(Filesystem $filesystem, File $file)
    {
        $this->filesystem = $filesystem;
        $this->directory = $filesystem->getDirectoryWrite(DirectoryList::MEDIA);
    }

    /**
     *
     */
    public function execute()
    {
        $mediaRootDir = $this->directory->getAbsolutePath();

        foreach (self::MEDIA_DIRS as $dir) {
            $path = $mediaRootDir . $dir;
            if ($this->directory->isExist($path)) {
                $this->directory->delete($path);
            }
        }
    }
}