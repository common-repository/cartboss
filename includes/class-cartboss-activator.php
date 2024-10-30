<?php


class Cartboss_Activator {
	public static function activate() {
        // init default options
        Cartboss_Options::init();

		// WPML
		do_action( 'wpml_register_single_string', Cartboss_Constants::TEXT_DOMAIN, Cartboss_Constants::TEXT_KEY_CONSENT_LABEL, Cartboss_Options::get_marketing_checkbox_label() );

		// init db
		Cartboss_Token_Database_Manager::instance()->create_table();
        Cartboss_Order_Database_Manager::instance()->create_table();
//		Cartboss_Event_Database_Manager::instance()->create_table();
//		Cartboss_Cart_Database_Manager::instance()->create_table();

		// enable coupons
		Cartboss_Utils::enable_coupons();
	}
}
