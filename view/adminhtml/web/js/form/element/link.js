define([
    'underscore',
    'Magento_Ui/js/form/element/abstract'
], function (_, Abstract) {
    'use strict';

    return Abstract.extend({
        defaults: {
            href: '',
            elementTmpl: 'TNW_Salesforce/form/element/link'
        },

        prefix: function(value) {
            if (!_.isString(value)) {
                return '';
            }

            var explode = value.split(':');
            if (explode.length === 1) {
                return '';
            }

            return explode[0];
        },

        text: function(value) {
            if (!_.isString(value)) {
                return '';
            }

            var explode = value.split(':');

            if (explode.length === 1) {
                return explode[0];
            }

            return explode[1];
        },

        isEmpty: function (value) {
            return _.isEmpty(value);
        },

        values: function () {
            return _.isArray(this.value()) ? this.value() : [];
        }
    });
});
