define([
    'Magento_Ui/js/form/element/abstract'
], function (Abstract) {
    'use strict';

    return Abstract.extend({
        defaults: {
            elementTmpl: 'TNW_Salesforce/form/element/sync-status'
        },

        getHtml: function () {
            var type = '-warning error';
            var title = 'Out of Sync';
            switch (this.value()) {
                case 1:
                    type = '-success success';
                    title ='In Sync';
                    break;
                case 10:
                case 11:
                    type = '-pending pending';
                    title = 'Pending';
                    break;
            }

            return '<div class="message message' + type + ' sync-status-salesforce" title="' + title + '"></div>';
        }
    });
});
