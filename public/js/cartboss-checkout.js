document.addEventListener("DOMContentLoaded", function () {
    try {
        // thanks to woocommerce booster file, this is required
        // what happens here? booster's JS updates each field's label, and since WOO renders input within label element, input gets lost during the process
        var cartbossCustomFields = document.querySelectorAll('[id^="cartboss_"]');
        if (cartbossCustomFields) {
            for (var i = 0; i < cartbossCustomFields.length; i++) {
                if (cartbossCustomFields[i].tagName === 'P') {
                    cartbossCustomFields[i].id = cartbossCustomFields[i].id + "_cb_fixed";
                }
            }
        }
    } catch (e) {
    }

    console.log("🔥", "Powered by CartBoss", " : ", "www.cartboss.io");
});

(function ($) {
    var cbEndpointUrl = cb_checkout_data.ajaxurl;
    var cbNonce = cb_checkout_data.nonce;
    var cbDebug = cb_checkout_data.debug;
    var cbAction = cb_checkout_data.action;
    var cbPresetFields = cb_checkout_data.preset_fields;

    var cbInitialized = false,
        cbSendDelay = 1500;

    cbDebug && console.log("CartBoss", "Script loaded");

    var cbDataSender = (function () {
        return {
            stateData: null,

            addState: function (data) {
                if (!data) {
                    cbDebug && console.log("CartBoss", "State data is empty, skipping");
                    return;
                }

                clearTimeout(this.tid);

                this.stateData = data;
                this._send(cbSendDelay);
            },

            _send: function (delay) {
                var self = this;

                if (!this.stateData) {
                    cbDebug && console.log("CartBoss", "🚨 Nothing to send");
                }

                if (!self.isSendingInProgress) {
                    cbDebug && console.log("CartBoss", "⏳ Change detected, sending in", delay, "ms");

                    self.tid = setTimeout(function () {
                        self.stateData["action"] = cbAction;
                        self.stateData["nonce"] = cbNonce;
                        self.stateData["current_url"] = window.location.href;

                        $.ajax({
                            url: cbEndpointUrl,
                            type: "POST",
                            data: self.stateData,
                            dataType: "json",
                            cache: false,
                            timeout: 10000,
                            beforeSend: function (xhr) {
                                self.isSendingInProgress = true;

                                cbDebug && console.log(
                                    "CartBoss",
                                    "✈ Sending started with data"
                                );

                                self.stateData = null;
                            },
                            complete: function (a, b) {
                                self.isSendingInProgress = false;

                                // cbDebug && console.log("CartBoss", "✅ Sending completed with response:", b);

                                /*if (self.stateData) {
                                    cbDebug && console.log(
                                        "CartBoss",
                                        "🙊 State changed while previous sending, send again..."
                                    );

                                    self._send(cbSendDelay);
                                }*/
                            },
                            success: function (response) {
                                cbDebug && console.log("CartBoss", "✅ Sending completed with response:", response);

                                if (!response.success) {
                                    if (response.data.redirect) {
                                        window.location.href= response.data.redirect;
                                    }
                                }
                            }
                        });
                    }, delay);
                }
            },
        };
    })();

    var cbInputFields = {
            billing_phone: '#billing_phone, #billing-phone, #shipping_phone, #shipping-phone, #phone',
            billing_email: '#billing_email, #email',

            billing_first_name: '#billing_first_name, #billing-first_name',
            billing_last_name: '#billing_last_name, #billing-last_name',
            billing_company: '#billing_company, #billing-company',
            billing_address_1: '#billing_address_1, #billing-address_1',
            billing_address_2: '#billing_address_2, #billing-address_2, #billing_houseno',
            billing_city: '#billing_city, #billing-city',
            billing_postcode: '#billing_postcode, #billing_zip, #billing-postcode',
            billing_country: '#billing_country, div[class*="country-input"] > input[type=text]:not([id]):last',
            billing_state: '#billing_state',

            shipping_first_name: '#shipping_first_name, #shipping-first_name',
            shipping_last_name: '#shipping_last_name, #shipping-last_name',
            shipping_company: '#shipping_company, #shipping-company',
            shipping_address_1: '#shipping_address_1, #shipping-address_1',
            shipping_address_2: '#shipping_address_2, #shipping-address_2, #shipping_houseno',
            shipping_city: '#shipping_city, #shipping-city',
            shipping_postcode: '#shipping_postcode, #shipping_zip, #shipping-postcode',
            shipping_country: '#shipping_country, div[class*="country-input"] > input[type=text]:not([id]):last',
            shipping_state: '#shipping_state',

            order_comments: '#order_comments, .wc-block-checkout__order-notes > textarea',
            // ship_to_different_address: '#ship-to-different-address-checkbox, .wc-block-checkout__use-address-for-billing > input[type=checkout]',
            cartboss_accepts_marketing: '#cartboss_accepts_marketing'
        },
        cbIgnoredInputFields = [],
        cbElements = {};

    var cbListener = function () {
        var data = {};
        $.each(cbElements, function (name, el) {
            if (el.is(':checkbox')) {
                data[name] = !!el.is(':checked');
            } else {
                data[name] = el.val();
            }
        });

        if (data['billing_phone'] !== undefined) {
            if (data['billing_phone'].length > 5) {
                cbDataSender.addState(data);
            } else {
                cbDebug && console.log(
                    "CartBoss",
                    "🔥 Phone too short");
            }
        } else {
            cbDebug && console.log(
                "CartBoss",
                "🔥 Phone not found");
        }
    };

    var cbInit = function () {
        if (cbInitialized) {
            return;
        }
        cbInitialized = true;
        cbDebug && console.log("CartBoss", "Script initialized");

        $.each(cbInputFields, function (cbFieldName, cbFieldSelector) {
            try {
                var el = $(cbFieldSelector);

                if (el && el.length > 0) {
                    // clever fix, we so smart ;)
                    if (cbFieldName === 'billing_phone') {
                        try {
                            el.prop('type', 'tel');
                            el.attr('autocomplete', 'billing tel');
                            el.attr('autocorrect', 'off');
                        } catch (e) {
                            // pass
                        }
                    }

                    if (typeof cbPresetFields[cbFieldName] !== "undefined") {
                        if (String(cbPresetFields[cbFieldName]).length > 0) {
                            if (el.is(':checkbox')) {
                                if (el.is(':checked') !== cbPresetFields[cbFieldName]) {
                                    el.trigger("click");
                                }
                            } else {
                                el.val(cbPresetFields[cbFieldName] || '');
                            }

                            cbDebug && console.log(
                                "CartBoss",
                                "🔥 Field value set via JS",
                                el.attr('id'), '=', cbPresetFields[cbFieldName]
                            );
                        }
                    }

                    if ($.inArray(cbFieldName, cbIgnoredInputFields) === -1) {
                        el.on('input change', cbListener);

                        cbElements[cbFieldName] = el;

                        cbDebug && console.log("CartBoss", "Field listener attached to field", cbFieldName);
                    }
                } else {
                    cbDebug && console.log("CartBoss", "Field not found", cbFieldName);
                }
            } catch (e) {
                // pass
            }
        });

        // call bcs form might be prefilled
        cbListener();
    };

    // run after all other scripts are loaded + wait a bit so our code wins
    $(document).ready(function () {
        setTimeout(cbInit, 1500);
    });

    setTimeout(cbInit, 3000);
})(jQuery);
