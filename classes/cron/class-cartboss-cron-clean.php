<?php


if (!defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

if (!class_exists('Cartboss_Cron_Clean')) :

    class Cartboss_Cron_Clean extends Cartboss_Cron
    {
        var $label = 'CartBoss Cleaner';
        var $interval = 60 * 60 * 6;

        public function do_handle()
        {
			// remove events
//	        Cartboss_Event_Database_Manager::instance()->purge();
	        Cartboss_Token_Database_Manager::instance()->purge();
            Cartboss_Order_Database_Manager::instance()->purge();
//	        Cartboss_Cart_Database_Manager::instance()->purge();

			// remove coupons
            $args = array(
                'posts_per_page' => -1,
                'post_type' => 'shop_coupon',
                'post_status' => 'publish',
                'meta_query' => array(
                    'relation' => 'AND',
                    array(
                        'key' => 'cb_delete_at',
                        'value' => time(),
                        'compare' => '<'
                    ),
                    array(
                        'key' => 'cb_source',
                        'value' => 'cartboss',
                        'compare' => '=='
                    )
                )
            );

            $coupons = get_posts($args);
            if (!empty($coupons)) {
                foreach ($coupons as $coupon) {
                    $delete_at = intval(get_post_meta($coupon->ID, 'cb_delete_at', true));
                    if (time() > $delete_at) {
                        wp_delete_post($coupon->ID);
                    }
                }
            }

        }
    }

endif; // class_exists check

?>