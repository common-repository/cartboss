<?php

class Cartboss_Deactivator {
	public static function deactivate() {
		Cartboss_Token_Database_Manager::instance()->drop_table();
        Cartboss_Order_Database_Manager::instance()->drop_table();
//		Cartboss_Event_Database_Manager::instance()->drop_table();
//		Cartboss_Cart_Database_Manager::instance()->drop_table();

		Cartboss_Options::reset();

//		Cartboss_Cron_Reschedule::instance()->deactivate();
		Cartboss_Cron_Ping::instance()->deactivate();
//		Cartboss_Cron_Sync::instance()->deactivate();
		Cartboss_Cron_Clean::instance()->deactivate();

	}
}
