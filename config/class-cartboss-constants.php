<?php


class Cartboss_Constants {
    const TEXT_DOMAIN = 'CartBoss';
    const TEXT_KEY_CONSENT_LABEL = 'Consent Label';

    const CB_BUFFER_COOKIE_NAME_ATTRIBUTION_TOKEN = 'wp_cartboss_buffer_attribution';
    const CB_BUFFER_COOKIE_NAME_DISCOUNT = 'wp_cartboss_buffer_discount';
    const CB_BUFFER_COOKIE_NAME_SESSION = 'wp_cartboss_buffer_session';

    const CB_METADATA_CHECKOUT_REDIRECT_URL = 'checkout_redirect_url';
    const CB_METADATA_LOCAL_SESSION_ID = 'local_session';
    const CB_METADATA_ORDER_COMMENTS = 'order_comments';
//    const CB_METADATA_SHIP_TO_DIFFERENT_ADDRESS = 'ship_elsewhere';
    const CB_METADATA_ACCEPTS_MARKETING = 'accepts_marketing';
    const CB_METADATA_EXTRA_FIELDS = 'extra_fields';
    const CB_METADATA_PHONE = 'phone';

    const CB_FIELD_ACCEPTS_MARKETING = 'cartboss_accepts_marketing';

    const CB_SESSION_ORDER_METADATA = 'cb_order_metadata';

    const CB_PRIORITY_MAX = 0;
    const CB_PRIORITY_MIN = 20;

//    const STATUS_PENDING = 'pending';
//    const STATUS_FAILED = 'failed';
//    const STATUS_CANCELLED = 'cancelled';
//    const STATUS_REFUNDED = 'refunded';

    const STATUS_ON_HOLD = 'on-hold';
    const STATUS_WC_ON_HOLD = 'wc-on-hold';
    const STATUS_PROCESSING = 'processing';
    const STATUS_WC_PROCESSING = 'wc-processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_WC_COMPLETED = 'wc-completed';

    // https://docs.woocommerce.com/wp-content/uploads/2013/05/woocommerce-order-process-diagram.png
    const VALID_PURCHASE_STATUSES = array(
        Cartboss_Constants::STATUS_WC_ON_HOLD,
        Cartboss_Constants::STATUS_WC_PROCESSING,
        Cartboss_Constants::STATUS_WC_COMPLETED,
        Cartboss_Constants::STATUS_ON_HOLD,
        Cartboss_Constants::STATUS_PROCESSING,
        Cartboss_Constants::STATUS_COMPLETED
    );
}
