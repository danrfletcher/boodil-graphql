<?php

namespace Boodil\Payment\Model\Adminhtml\Source;

use Magento\Framework\Option\ArrayInterface;

class Logo implements ArrayInterface
{
    public const LOGO_LIGHT = 'light';
    public const LOGO_DARK = 'dark';

    /**
     * Possible Logo types
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            [
                'value' => self::LOGO_LIGHT,
                'label' => 'Light',
            ],
            [
                'value' => self::LOGO_DARK,
                'label' => 'Dark'
            ]
        ];
    }
}
