/*
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

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

            let explode = value.split(':'),
                resultPrefix = '';
            switch (explode.length) {
                case 2:
                    resultPrefix = explode[0];
                    break;
                case 3:
                    resultPrefix = explode[0] + ':' + explode[1];
                    break;
                default:
                    resultPrefix = '';
                    break;
            }

            return resultPrefix;
        },

        text: function(value) {
            if (!_.isString(value)) {
                return '';
            }

            let explodedSForceId = value.split(':');

            return explodedSForceId[explodedSForceId.length - 1]
        },

        isEmpty: function (value) {
            return _.isEmpty(value);
        },

        values: function () {
            return _.isArray(this.value()) ? this.value() : [];
        }
    });
});
