<?php
/**
 * @author Ronak Chauhan (ronak.chauhan@y1.de)
 * @package Y1_S3ImageHandler
 */
namespace Y1\S3ImageHandler\Block\Adminhtml\System\Config\Form\Field;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Framework\Data\Form\Element\Factory;

/**
 * Class Headers
 *
 * @package Y1\S3ImageHandler\Block\Adminhtml\System\Config\Form\Field
 */
class Headers extends AbstractFieldArray
{
    /**
     * @var \Magento\Framework\Data\Form\Element\Factory
     */
    protected $_elementFactory;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Data\Form\Element\Factory $elementFactory
     * @param array $data
     */
    public function __construct(
        Context $context,
        Factory $elementFactory,
        array $data = []
    ) {
        $this->_elementFactory = $elementFactory;
        parent::__construct($context, $data);
    }

    /**
     *
     */
    protected function _construct()
    {
        $this->addColumn(
            'header',
            [
                'label' => 'Header',
                'style' => 'width:150px',
            ]
        );
        $this->addColumn(
            'value',
            [
                'label' => 'Value',
                'style' => 'width:100px',
            ]
        );
        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add Custom Header');
        parent::_construct();
    }
}