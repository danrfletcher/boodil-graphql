<?php
namespace Boodil\Payment\Logger;

use DateTimeZone;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Stringable;

class Logger extends \Monolog\Logger
{
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    const XML_PATH_BOODIL_DEBUG = "payment/boodil/debug";

    /**
     * Logger constructor.
     * @param ScopeConfigInterface $scopeConfig
     * @param string $name
     * @param array $handlers
     * @param array $processors
     * @param DateTimeZone|null $timezone
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        string $name,
        array $handlers = [],
        array $processors = [],
        ?DateTimeZone $timezone = null
    ) {
        $this->scopeConfig = $scopeConfig;
        parent::__construct($name, $handlers, $processors, $timezone);
    }

    /**
     * Adds a log record at the DEBUG level.
     *
     * This method allows for compatibility with common interfaces.
     *
     * @param string|Stringable $message The log message
     * @param mixed[]           $context The log context
     */
    public function debug($message, array $context = []): void
    {
        if ($this->getDebugEnabled()) {
            $this->addRecord(static::DEBUG, (string)$message, $context);
        }
    }

    /**
     * @return mixed
     */
    public function getDebugEnabled()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        return $this->scopeConfig->getValue(self::XML_PATH_BOODIL_DEBUG, $storeScope);
    }
}
