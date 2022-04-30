/*
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
define([
    'Magento_Ui/js/form/components/button',
    'jquery',
    'uiRegistry',
    'underscore'
], function (Button, $, register, _) {
    'use strict';

    return Button.extend({
        // defaults: {
        //     currentRecordNamespace: 'bundle_current_record',
        //     listingDataProvider: '',
        //     value: [],
        //     imports: {
        //         currentRecordName: '${ $.provider }:${ $.currentRecordNamespace }',
        //         listingData: '${ $.provider }:${ $.listingDataProvider }'
        //     },
        //     links: {
        //         value: '${ $.provider }:${ $.dataScope }'
        //     },
        //     listens: {
        //         listingData: 'setListingData'
        //     }
        // },

        /**
         * Call parent "action" method
         * and set new data to record and listing.
         *
         * @returns {Object} Chainable.
         */

        action: function () {
            // this._super();
            console.log('start');
            let $page = $();
            let page = 1;
            new Ajax.Updater($('pre[name="content"]'), '/tnw_salesforce/logfile_file/view', {
                parameters: { page: page },
                insertion: function(receiver, responseText) {
                    var insertion = {};
                    // insertion[config.get('insertion')] = responseText
                    //     .replace(/&/g, "&amp;")
                    //     .replace(/</g, "&lt;")
                    //     .replace(/>/g, "&gt;")
                    //     .replace(/"/g, "&quot;")
                    //     .replace(/'/g, "&#039;");

                    receiver.insert(responseText);
                },
                onComplete: function (response, json) {
                    if (response.responseText.empty()) {
                        alert('End of file');
                    }
                }
            });
            // this.source.set(this.currentRecordNamespace, this.name);
            // this.source.set(this.listingDataProvider, this.value());

            return this;
        }
    });
});
