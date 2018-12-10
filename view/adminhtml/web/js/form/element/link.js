define([
    'Magento_Ui/js/form/element/abstract'
], function (Abstract) {
    'use strict';

    return Abstract.extend({
        defaults: {
            href: '',
            elementTmpl: 'TNW_Salesforce/form/element/link'
        },

        prefix: function(value) {
            var explode = value.split(':');

            if (explode.length === 1) {
                return '';
            }

            return explode[0];
        },

        text:  function(value) {
            var explode = value.split(':');

            if (explode.length === 1) {
                return explode[0];
            }

            return explode[1];
        }
    });
});
