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
});

(function ($) {
    var cbAbandonUrl = cb_checkout_data.endpoint_abandon;
    var cbPopulateUrl = cb_checkout_data.endpoint_populate;
    var cbNonce = cb_checkout_data.nonce;
    var cbDebug = cb_checkout_data.debug;
    var cbPresetFields = cb_checkout_data.preset_fields;

    var cbInitialized = false,
        cbElements = {},
        // cbMetaElements = {},
        cbSendDelay = 3000,
        cbInputFields = {
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

            cartboss_accepts_marketing: '#cartboss_accepts_marketing'
        },
        cbIgnoredInputFields = ['payment_method', 'payment_method[0]', 'shipping_method', 'shipping_method[0]', 'createaccount', '_wp_http_referer', 'woocommerce-process-checkout-nonce'];

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
                        self.stateData["nonce"] = cbNonce;
                        self.stateData["checkout_redirect_url"] = window.location.href;

                        // if (self.stateData["meta"]) {
                        //     self.stateData["extra_fields"] = JSON.stringify(self.stateData["meta"]);
                        //     delete self.stateData['meta'];
                        // }

                        $.ajax({
                            url: cbAbandonUrl,
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
                            },
                            success: function (response) {
                                cbDebug && console.log("CartBoss", "✅ Sending completed with response:", response);

                                if (!response.success) {
                                    //if (response.data.redirect) {
                                    //    window.location.href = response.data.redirect;
                                    //} else {
                                        console.log("CartBoss", "Error", response.data)
                                        //location.reload();
                                    //}
                                }
                            }
                        });
                    }, delay);
                }
            },
        };
    })();

    var cbFieldChangeListener = function () {
        var data = {
            // meta: {}
        };
        $.each(cbElements, function (name, el) {
            if (el.is(':checkbox')) {
                data[name] = !!el.is(':checked');
            } else if (el.is(':radio')) {
                data[name] = $("input[name='" + name + "']:checked").val();
            } else {
                data[name] = el.val();
            }
        });

        // $.each(cbMetaElements, function (name, el) {
        //     if (el.is(':checkbox')) {
        //         data['meta'][name] = !!el.is(':checked');
        //     } else if (el.is(':radio')) {
        //         data['meta'][name] = $("input[name='" + name + "']:checked").val();
        //     } else {
        //         data['meta'][name] = el.val();
        //     }
        // });

        if (data['billing_phone'] !== undefined) {
            if (data['billing_phone'].length >= 9) {
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

    function cbSetFieldValue(selector, value) {
        if (!value)
            return;

        var els = $(selector);
        if (els && els.length > 0) {
            $.each(els, function (i, x) {
                var el = $(x);
                if (el.is(':checkbox')) {
                    if (el.is(':checked') !== value) {
                        el.trigger("click");
                    }
                } else if (el.is(':radio')) {
                    if (el.is(':checked') !== value) {
                        el.trigger("click");
                    }
                } else {
                    el.val(value);
                }

                cbDebug && console.log(
                    "CartBoss",
                    "🔥 Field value set via JS",
                    el.attr('id'), '=', value
                );
            });
        }
    }

    var cbInit = function () {
        cbDebug && console.log("CartBoss", "Script initialized");

        //populate all standard fields from presets
        $.each(cbInputFields, function (cbFieldName, cbFieldSelector) {
            cbSetFieldValue(cbFieldSelector, cbPresetFields[cbFieldName]);
        });

        //populate all extra fields from presets
        // if (cbPresetFields['extra_fields']) {
        //     $.each(JSON.parse(cbPresetFields['extra_fields']), function (cbFieldName, cbFieldValue) {
        //         cbSetFieldValue('*[name="' + cbFieldName + '"]', cbFieldValue);
        //     });
        // }

        // listen to changes of standard fields
        $.each(cbInputFields, function (cbFieldName, cbFieldSelector) {
            var el = $(cbFieldSelector);
            if (el && el.length > 0) {
                if (cbFieldName === 'billing_phone') {
                    try {
                        el.prop('type', 'tel');
                        el.attr('autocomplete', 'billing tel');
                        el.attr('autocorrect', 'off');
                    } catch (e) {
                    }
                }
                if ($.inArray(cbFieldName, cbIgnoredInputFields) === -1) {
                    el.on('input change', cbFieldChangeListener);
                    cbElements[cbFieldName] = el;
                }

                cbDebug && console.log("CartBoss", "👂 Field listener attached to STANDARD field", cbFieldName);
            }
        });

        // listen to changes on all other extra fields
        var cbStandardFieldNames = [];
        for (var key in cbElements) {
            if (cbElements.hasOwnProperty(key)) {
                cbStandardFieldNames.push(key);
            }
        }

        // $("form[name='checkout'] :input").each(function () {
        //     var el = $(this);
        //     var name = el.attr('name');

        //     if (!el.is(':button')) {
        //         if (name) {
        //             if ($.inArray(name, cbStandardFieldNames) === -1) {
        //                 if ($.inArray(name, cbIgnoredInputFields) === -1) {
        //                     el.on('input change', cbFieldChangeListener);
        //                     cbMetaElements[name] = el;
        //                     cbDebug && console.log("CartBoss", "👂 Field listener attached to EXTRA field", name);
        //                 }
        //             }
        //         }
        //     }
        // });
    };

    var cbController = function () {
        if (cbInitialized) {
            return;
        }
        cbInitialized = true;

        var fieldsFound = 0;
        $.each(cbInputFields, function (cbFieldName, cbFieldSelector) {
            var el = $(cbFieldSelector);

            if (el && el.length > 0) {
                fieldsFound++;
            }

            if (fieldsFound > 2) {
                $.ajax({
                    url: cbPopulateUrl,
                    type: "POST",
                    dataType: "json",
                    cache: false,
                    timeout: 10000,
                    data: {
                        'nonce': cbNonce
                    },

                    success: function (response) {
                        if (response.success && response.data !== null) {
                            cbPresetFields = response.data;
                        }
                    },
                    complete: function (a, b) {
                        cbInit();
                    }
                });
                return false;
            }
        });
    };

    $(document).ready(function () {
        console.log("🔥", "Recover Abandoned Carts with SMS", "Powered by CartBoss", " -> ", "https://www.cartboss.io", "🔥");
        cbDebug && console.log("CartBoss", "Script loaded");
        setInterval(function () {
            cbController()
        }, 1000);

    });
})(jQuery);
