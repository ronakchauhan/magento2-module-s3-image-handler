<?php
/**
 * @author Ronak Chauhan (ronak.chauhan@y1.de)
 * @package Y1_S3ImageHandler
 */
namespace Y1\S3ImageHandler\Model\Catalog\Product\Image;

use Magento\Catalog\Helper\Image;
use Magento\Catalog\Model\Product\Image\ParamsBuilder;
use Magento\Catalog\Model\View\Asset\ImageFactory;
use Magento\Catalog\Model\View\Asset\PlaceholderFactory;
use Magento\Framework\View\ConfigInterface;
use Y1\S3ImageHandler\Helper\ImageHandler;

/**
 * Class UrlBuilder
 *
 * @package Y1\S3ImageHandler\Model\Catalog\Product\Image
 */
class UrlBuilder extends \Magento\Catalog\Model\Product\Image\UrlBuilder
{
    /**
     * @var \Magento\Framework\View\ConfigInterface
     */
    private $presentationConfig;
    /**
     * @var \Magento\Catalog\Model\Product\Image\ParamsBuilder
     */
    private $imageParamsBuilder;
    /**
     * @var \Magento\Catalog\Model\View\Asset\ImageFactory
     */
    private $viewAssetImageFactory;
    /**
     * @var \Magento\Catalog\Model\View\Asset\PlaceholderFactory
     */
    private $placeholderFactory;
    /**
     * @var \Y1\S3ImageHandler\Helper\ImageHandler
     */
    private $imageHandlerHelper;

    public function __construct(
        ConfigInterface $presentationConfig,
        ParamsBuilder $imageParamsBuilder,
        ImageFactory $viewAssetImageFactory,
        PlaceholderFactory $placeholderFactory,
        ImageHandler $imageHandlerHelper
    ) {
        parent::__construct(
            $presentationConfig,
            $imageParamsBuilder,
            $viewAssetImageFactory,
            $placeholderFactory
        );
        $this->presentationConfig = $presentationConfig;
        $this->imageParamsBuilder = $imageParamsBuilder;
        $this->viewAssetImageFactory = $viewAssetImageFactory;
        $this->placeholderFactory = $placeholderFactory;
        $this->imageHandlerHelper = $imageHandlerHelper;
    }

    /**
     * Build image url using base path and params
     *
     * @param string $baseFilePath
     * @param string $imageDisplayArea
     *
     * @return string
     */
    public function getUrl(string $baseFilePath, string $imageDisplayArea): string
    {
        $imageArguments = $this->presentationConfig->getViewConfig()->getMediaAttributes(
            'Magento_Catalog',
            Image::MEDIA_TYPE_CONFIG_NODE,
            $imageDisplayArea
        );

        $imageMiscParams = $this->imageParamsBuilder->build($imageArguments);

        if ($baseFilePath === null || $baseFilePath === 'no_selection') {
            $asset = $this->placeholderFactory->create(
                [
                    'type' => $imageMiscParams['image_type']
                ]
            );
        } else {
            $asset = $this->viewAssetImageFactory->create(
                [
                    'miscParams' => $imageMiscParams,
                    'filePath'   => $baseFilePath,
                ]
            );
        }

        $requestParams['image'] = $baseFilePath;
        $requestParams['edits']['resize']['width'] = $imageMiscParams['image_width'];
        $requestParams['edits']['resize']['height'] = $imageMiscParams['image_height'];
        $requestParams['edits']['resize']['fit'] = 'cover';
        if (is_array($imageMiscParams['background'])) {
            $colorRgbIndex = [];
            $colorRgbIndex['r'] = $imageMiscParams['background'][0];
            $colorRgbIndex['g'] = $imageMiscParams['background'][1];
            $colorRgbIndex['b'] = $imageMiscParams['background'][2];
            $colorRgbIndex['alpha'] = null;

            $requestParams['edits']['flatten']['background'] = $colorRgbIndex;
        }
        $res = $this->imageHandlerHelper->resizeObject($requestParams);

        return ($res->getUrl() ?? $asset->getUrl());
    }
}