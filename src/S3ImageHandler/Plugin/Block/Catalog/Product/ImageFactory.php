<?php
/**
 * @author Ronak Chauhan (ronak.chauhan@y1.de)
 * @package Y1_S3ImageHandler
 */
namespace Y1\S3ImageHandler\Plugin\Block\Catalog\Product;

use Magento\Catalog\Helper\Image as ImageHelper;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Image\ParamsBuilder;
use Magento\Catalog\Model\View\Asset\ImageFactory as AssetImageFactory;
use Magento\Catalog\Model\View\Asset\PlaceholderFactory;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\View\ConfigInterface;
use Y1\S3ImageHandler\Helper\Data;
use Y1\S3ImageHandler\Helper\ImageHandler;
use Magento\Catalog\Block\Product\Image as ImageBlock;

/**
 * Class ImageFactory
 *
 * @package Y1\S3ImageHandler\Plugin\Block\Catalog\Product
 */
class ImageFactory
{

    /**
     * @var ConfigInterface
     */
    private $presentationConfig;

    /**
     * @var AssetImageFactory
     */
    private $viewAssetImageFactory;

    /**
     * @var ParamsBuilder
     */
    private $imageParamsBuilder;

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var PlaceholderFactory
     */
    private $viewAssetPlaceholderFactory;

    /**
     * @var Data
     */
    private $helper;
    /**
     * @var \Y1\S3ImageHandler\Helper\ImageHandler
     */
    private $imageHandlerHelper;

    /**
     * @param ObjectManagerInterface $objectManager
     * @param ConfigInterface $presentationConfig
     * @param AssetImageFactory $viewAssetImageFactory
     * @param PlaceholderFactory $viewAssetPlaceholderFactory
     * @param ParamsBuilder $imageParamsBuilder
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        ConfigInterface $presentationConfig,
        AssetImageFactory $viewAssetImageFactory,
        PlaceholderFactory $viewAssetPlaceholderFactory,
        ParamsBuilder $imageParamsBuilder,
        Data $helper,
        ImageHandler $imageHandlerHelper
    )
    {
        $this->objectManager = $objectManager;
        $this->presentationConfig = $presentationConfig;
        $this->viewAssetPlaceholderFactory = $viewAssetPlaceholderFactory;
        $this->viewAssetImageFactory = $viewAssetImageFactory;
        $this->imageParamsBuilder = $imageParamsBuilder;
        $this->helper = $helper;
        $this->imageHandlerHelper = $imageHandlerHelper;
    }

    /**
     * @param \Magento\Catalog\Block\Product\ImageFactory $subject
     * @param callable $proceed
     * @param Product $product
     * @param string $imageId
     * @param array|null $attributes
     *
     * @return mixed
     */
    public function aroundCreate(
        \Magento\Catalog\Block\Product\ImageFactory $subject,
        callable $proceed,
        Product $product,
        string $imageId,
        array $attributes = null
    )
    {
            $requestParams = [];
            $viewImageConfig = $this->presentationConfig->getViewConfig()->getMediaAttributes(
                'Magento_Catalog',
                ImageHelper::MEDIA_TYPE_CONFIG_NODE,
                $imageId
            );

            $imageMiscParams = $this->imageParamsBuilder->build($viewImageConfig);
            $originalFilePath = $product->getData($imageMiscParams['image_type']);

            if ($originalFilePath === null || $originalFilePath === 'no_selection') {
                $imageAsset = $this->viewAssetPlaceholderFactory->create(
                    [
                        'type' => $imageMiscParams['image_type']
                    ]
                );
            } else {
                $imageAsset = $this->viewAssetImageFactory->create(
                    [
                        'miscParams' => $imageMiscParams,
                        'filePath' => $originalFilePath,
                    ]
                );
            }

            $attributes = $attributes === null ? [] : $attributes;

            $requestParams['image'] = $product->getImage();
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
            $data = [
                'data' => [
                    'template' => 'Magento_Catalog::product/image_with_borders.phtml',
                    'image_url' => ($res->getUrl() ?? $imageAsset->getUrl()),
                    'width' => $imageMiscParams['image_width'],
                    'height' => $imageMiscParams['image_height'],
                    'label' => $this->getLabel($product, $imageMiscParams['image_type']),
                    'ratio' => $this->getRatio($imageMiscParams['image_width'], $imageMiscParams['image_height']),
                    'custom_attributes' => $this->getStringCustomAttributes($attributes),
                    'class' => $this->getClass($attributes),
                    'product_id' => $product->getId()
                ],
            ];
            return $this->objectManager->create(ImageBlock::class, $data);
    }

    /**
     * Get image label
     *
     * @param Product $product
     * @param string $imageType
     * @return string
     */
    private function getLabel(Product $product, string $imageType): string
    {
        $label = $product->getData($imageType . '_' . 'label');
        if (empty($label)) {
            $label = $product->getName();
        }
        return (string)$label;
    }

    /**
     * Calculate image ratio
     *
     * @param int $width
     * @param int $height
     * @return float
     */
    private function getRatio(int $width, int $height): float
    {
        if ($width && $height) {
            return $height / $width;
        }
        return 1.0;
    }

    /**
     * Retrieve image custom attributes for HTML element
     *
     * @param array $attributes
     * @return string
     */
    private function getStringCustomAttributes(array $attributes): string
    {
        $result = [];
        foreach ($attributes as $name => $value) {
            if ($name !== 'class') {
                $result[] = $name . '="' . $value . '"';
            }
        }
        return !empty($result) ? implode(' ', $result) : '';
    }

    /**
     * Retrieve image class for HTML element
     *
     * @param array $attributes
     * @return string
     */
    private function getClass(array $attributes): string
    {
        return $attributes['class'] ?? 'product-image-photo';
    }
}
