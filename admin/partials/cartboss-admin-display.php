<div class="wrap">
    <div class="cartboss-wrapper">
        <form action="admin-post.php" method="post">
            <input name='action' type="hidden" value='cartboss_form_save'>

            <div class="container">
                <div class="row mb-5">
                    <div class="col">
                        <div class="wp-header-end"></div>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-lg-6">
                        <a href="https://www.cartboss.io" target="_blank"><img src="<?php echo plugins_url('../assets/logo.png', __FILE__); ?>" class="cartboss-image" style="height: 30px;"></a>
                    </div>
                    <div class="col-lg-6 text-right">
                        <?php if (Cartboss_Options::get_has_balance()): ?>
                            <a href="https://wordpress.org/support/plugin/cartboss/reviews/#new-post" class="cartboss-button cartboss-button--prominent py-2 px-5 me-2" target="_blank">Leave a Review</a>
                        <?php endif ?>
                        <a href="https://app.cartboss.io" class="cartboss-button py-2 px-5" target="_blank">Launch Dashboard</a>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-8">
                        <h2>Connect plugin</h2>

                        <input type="text" id="cb_api_key" name="cb_api_key" autocomplete="off" placeholder="Paste your api key here"
                               class="cartboss-full-width mt-2 <?php echo Cartboss_Options::get_is_valid_api_key() ? "" : "cartboss-error"; ?>"
                               value="<?php echo esc_html(Cartboss_Options::get_api_key()) ?>">

                        <?php if (!Cartboss_Options::get_is_valid_api_key()): ?>
                            <p class="description mt-2">💡 Register CartBoss API key for this domain <a href="https://app.cartboss.io/sites/" target="_blank" class="btn btn-link"><b>here &rarr;</b></a></p>
                        <?php else: ?>
                            <p class="description mt-2 ms-1">⚡ Do not share your API key with anyone!</p>
                        <?php endif; ?>

                        <hr class="my-4">

                        <h2>Phone number at top</h2>
                        <p class="description mb-2">Placing phone field to the top of your checkout form will enable you to recover 3x more abandoned carts and capture more contacts.</p>

                        <label for="cb_phone_at_top">
                            <input type="checkbox" name="cb_phone_at_top" id="cb_phone_at_top" value="true" <?php echo Cartboss_Options::get_is_phone_field_on_top() ? "checked" : ""; ?> >
                            Move phone to the top
                        </label>

                        <hr class="my-4">

                        <h2>Marketing consent</h2>

                        <label for="cb_marketing_checkbox_enabled">
                            <input type="checkbox" name="cb_marketing_checkbox_enabled"
                                   id="cb_marketing_checkbox_enabled"
                                   value="true" <?php echo Cartboss_Options::get_is_marketing_checkbox_visible() ? "checked" : ""; ?>>
                            Show marketing consent checkbox
                        </label>

                        <div id="cb_marketing_checkbox_label_wrapper" class="pt-3 mb-3" style="display: none;">
                            <input type="text" id="cb_marketing_checkbox_label" name="cb_marketing_checkbox_label"
                                   autocomplete="off" class="cartboss-full-width"
                                   value="<?php echo esc_html(Cartboss_Options::get_marketing_checkbox_label()) ?>"
                                   placeholder="Example: <?php echo esc_html(Cartboss_Options::get_default_marketing_checkbox_label()) ?>">
                            <p class="description mt-2">💡 WPML translatable text</p>
                        </div>

                        <hr class="my-4">

                        <h2>Disable CartBoss for checked user roles</h2>
                        <p class="description mb-3">
                            When disabled, placing an order or abandoning a cart won't trigger any CartBoss automation.
                        </p>

                        <p class="description mb-3">
                            <b>Important:</b> Make sure you are <b>not</b> logged into your site when testing CartBoss
                        </p>

                        <div class="d-flex flex-wrap">
                            <?php foreach (Cartboss_Utils::get_editable_roles() as $role => $data): ?>
                                <label for="cb_role_<?php echo $role ?>" class="me-2 mb-2">
                                    <input type="checkbox"
                                           name="cb_roles[]"
                                           id="cb_role_<?php echo $role ?>"
                                           class="flex-nowrap"
                                           value="<?php echo $role ?>" <?php echo Cartboss_Options::is_ignored_role($role) ? "checked" : ""; ?>>
                                    <?php echo $data['name'] ?>
                                </label>
                            <?php endforeach; ?>
                        </div>

                        <hr class="my-4">

                        <button type="submit" class="cartboss-button py-2 px-5">Save Changes</button>

                    </div>

                    <div class="col-lg-3 ms-auto text-right">
                        <h4>STATUS</h4>
                        <ul>
                            <li>Installed version: <?php echo CARTBOSS_VERSION ?></li>
                            <li>Latest version: <?php echo Cartboss_Options::get_latest_version() ?></li>
                            <li>API key valid: <?php echo Cartboss_Options::get_is_valid_api_key() ? "✅️" : "❌"; ?></li>
                            <li>Website active: <?php echo Cartboss_Options::get_is_website_active() ? "✅️" : "❌"; ?></li>
                            <li>Account balance: <?php echo Cartboss_Options::get_balance() ?></li>
                            <li>Last ping: <?php echo Cartboss_Utils::timeago(Cartboss_Options::get_last_ping_at()) ?></li>
                            <li>Last sync: <?php echo Cartboss_Utils::timeago(Cartboss_Options::get_last_sync_at()) ?></li>
                        </ul>
                    </div>
                </div>

                <div class="row mt-5 pt-5">
                    <div class="col-12">
                        <p class="description">Please note, that under Applicable Legislation and our Terms of Service,
                            you are required to set-up the necessary legal means of obtaining End User Consent next to the
                            input form where End Users will be asked to consensually offer their Personal Data (namely their
                            Phone Number, Delivery address, Name and Surname) to you in connection with your use of the
                            CartBoss Service.</p>
                        <p class="description">Please consult a legal professional in order to fully comply with any and all
                            local legal requirements prior to using the service and make sure that you have observed and
                            implemented the steps from point 4.2 of our Terms of Service (namely updating your privacy
                            policy with a short explanation of how and on which legal grounds you will carry out the
                            processing in connection with the CartBoss Service).</p>
                    </div>
                </div>


            </div>
        </form>
    </div>
</div>