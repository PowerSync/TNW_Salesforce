/*jshint browser:true jquery:true*/
define([
    "jquery",
    "domReady!"
], function($){
    "use strict";

    $.widget('tnw.browse', {
        options: {
            uploadSelector: '.upload',
            inputSelector: '.input-text'
        },

        /**
         * Constructor method that prepares connection tester activities
         * @private
         */
        _create: function() {
            this.element.on('change', this.options.uploadSelector, $.proxy(this.change, this));
        },

        /**
         * @private
         */
        change: function(event) {
            this.element.find(this.options.inputSelector).val(event.target.value);
        }
    });

    return $.tnw.browse;
});