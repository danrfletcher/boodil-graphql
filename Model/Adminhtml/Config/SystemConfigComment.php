<?php

namespace Boodil\Payment\Model\Adminhtml\Config;

use Magento\Config\Model\Config\CommentInterface;
use Magento\Framework\Locale\Resolver;
use Magento\Framework\View\Asset\Repository;

class SystemConfigComment implements CommentInterface
{
    /**
     * @var Repository
     */
    protected $_assetRepo;

    /**
     * @var Resolver
     */
    protected $locale;

    /**
     * SystemConfigComment constructor.
     * @param Repository $assetRepo
     * @param Resolver $locale
     */
    public function __construct(
        Repository $assetRepo,
        Resolver $locale
    ) {
        $this->_assetRepo = $assetRepo;
        $this->locale = $locale;
    }

    /**
     * @param string $elementValue
     * @return string
     */
    public function getCommentText($elementValue)
    {
        $haystack = $this->locale->getLocale();
        $lang = strtolower(strstr($haystack, '_', false));
        $darkLogo = $this->_assetRepo->getUrl("Boodil_Payment::images/dark_logo.svg");
        $lightLogo = $this->_assetRepo->getUrl("Boodil_Payment::images/light_logo.svg");
        $html = <<<HTML
            <span id="dark" class="no-display"><img src="$darkLogo" title="boodil logo"  alt="boodil logo"/></span>
            <span id="light" class="no-display"><img src="$lightLogo" title="boodil logo"  alt="boodil logo"/></span>
HTML;

        $html .= "<script type='text/javascript'>
                    require(['jquery', 'jquery/ui'], function($) {
                        jQuery(document).ready( function() {
                            jQuery(\"#$elementValue\").show()
                            jQuery(\"#payment" .$lang. "_boodil_logo\").change(function() {
                                if (jQuery(this).val() === 'dark') {
                                   jQuery(\"#dark\").show()
                                   jQuery(\"#light\").hide()
                                } else {
                                    jQuery(\"#light\").show()
                                    jQuery(\"#dark\").hide()
                                }
                            })
                        })
                     })
                </script>";

        return $html;
    }
}
