<?php
namespace TNW\Salesforce\Ui\Component\Listing\Columns;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class Raw extends Column
{
    /**
     * @var \Magento\Framework\Escaper
     */
    protected $escaper;

    /**
     * Raw constructor.
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param \Magento\Framework\Escaper $escaper
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        \Magento\Framework\Escaper $escaper,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->escaper = $escaper;
    }


    /**
     * @param string $message
     * @return string
     */
    public function prepareMessage(string $message): string
    {
        $startFrom = strpos($message, "<?xml");
        if ($startFrom === false) {
            return $message;
        }
        try {
            $message = substr($message, $startFrom);
            $message = str_ireplace(['SOAP-ENV:', 'SOAP:','ns1:','soapenv:'], '', $message);
            $domXmlElement = simplexml_load_string($message);
            $data = json_encode($domXmlElement);
            $arrLog = json_decode($data, true);
            if ($arrLog) {
                $messageString = "";
                array_walk_recursive($arrLog, function (&$item, $key) use (&$messageString) {
                    $element = "{$key} : {$item}" . PHP_EOL;
                    $messageString .= $element;
                });
                $message = $messageString;
            }
        } catch (\Exception $exception) {
            return (string)$message;
        }
        return (string)$message;
    }
    /**
     * @param array $dataSource
     *
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (!isset($dataSource['data']['items'])) {
            return $dataSource;
        }

        $name = $this->getData('name');
        foreach ($dataSource['data']['items'] as &$item) {
            if (!isset($item[$this->getData('name')])) {
                continue;
            }
            $item['message'] = $this->prepareMessage($item['message']);

            $item["{$name}_html"] = sprintf('<div style="white-space: pre-wrap">%s</div>', $this->escaper->escapeHtml($item[$name]));
        }

        return $dataSource;
    }
}
