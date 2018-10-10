# Magento 2 and Salesforce Integration (Starter Pack)
The simplest and most flexible way of integrating Magento entities with Salesforce objects.

_NOTE:_ we also do offer [integration between Magneto 1.x and Salesforce](https://powersync.biz/integrations-magento-salesforce/) and you can find more information on our website. 

#### Build Status
[![CircleCI](https://circleci.com/gh/PowerSync/TNW_Salesforce/tree/master.svg?style=svg&circle-token=e6b9857e0734f52fb1756cbdb92a68dc2dcf1bf0)](https://circleci.com/gh/PowerSync/TNW_Salesforce/tree/master)

## Requirements
* PHP >= 7.0
* SOAP PHP extension
* Magento >= 2.2

## How to install
#### via Magento Marketplace
You can get this extension from Magento Marketplace by visiting [Salesforce Integration (Basic Plan)](https://marketplace.magento.com/tnw-salesforce-basic.html) page. Then follow [Installation instructions](https://technweb.atlassian.net/wiki/spaces/IWS/pages/590839809/Starter+Package) to install the extension.

#### via Composer (skip Magento Marketplace)
1. Install our extension
```
composer require tnw/salesforce
```
2. Update Magento
```
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento setup:static-content:deploy
```
3. Re-index
```
bin/magento indexer:reindex
```
After the extension is installed [follow the installation starting at STEP 3](https://technweb.atlassian.net/wiki/spaces/IWS/pages/590839809/Starter+Package#StarterPackage-STEP3-InstallSalesforceManagedpackage).

## How to articles
* [Setting up PowerSync Salesforce Connector on a scaled environment](https://technweb.atlassian.net/wiki/spaces/IWS/pages/409600001/Setting+up+PowerSync+Salesforce+Connector+on+a+scaled+environment)
* [Real-Time and Scheduled Synchronization](https://technweb.atlassian.net/wiki/spaces/IWS/pages/272105486/Real-Time+and+Scheduled+Synchronization)
* [Additional troubleshooting articles](https://technweb.atlassian.net/wiki/spaces/IWS/pages/57671700/How+To+M2+SF) are available as well.

## Contribute to this module
Feel free to Fork and contrinute to this module and create a pull request so we will merge your changes to `develop` branch.

## Features
* Customer sync
* Customer Address sync
* Duplicate Elimination
* PersonAccount support

#### Paid Version
More information about the paid version is available on [PowerSync.biz - Magento 2 + Salesforce Integration](https://powersync.biz/integrations-magento2-salesforce/) website. View the [full list of features](https://technweb.atlassian.net/wiki/spaces/IWS/pages/251691015/Introduction).

You can [view the demo of our paid verion](https://www.youtube.com/watch?v=6Z38jwLMj2g&t=25s) on YouTube.

## License
[GNU General Public License v3.0](https://choosealicense.com/licenses/gpl-3.0/)
