<?php
declare(strict_types=1);

namespace TNW\Salesforce\Lib\Tnw\SoapClient;

use Tnw\SoapClient\Soap\SoapClientFactory;
use Tnw\SoapClient\Plugin\LogPlugin;

/**
 * Class to extend Salesforce SOAP client builder
 *
 * @author David de Boer <david@ddeboer.nl>
 */
class ClientBuilder extends \Tnw\SoapClient\ClientBuilder
{
    /**
     * Build the Salesforce SOAP client
     *
     * @return Client
     */
    public function build(): Client
    {
        $soapClientFactory = new SoapClientFactory();
        $soapClient = $soapClientFactory->factory($this->wsdl, $this->soapOptions);

        $client = new Client($soapClient, $this->username, $this->password, $this->token);

        if ($this->log) {
            $logPlugin = new LogPlugin($this->log);
            $client->getEventDispatcher()->addSubscriber($logPlugin);
        }

        return $client;
    }
}
