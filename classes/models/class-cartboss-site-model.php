<?php


class Cartboss_Site_Model extends Cartboss_Base_Model {
    /**
     * @var bool
     */
    public $active;
    /**
     * @var string
     */
    public $wp_version = '';
	/**
	 * @var Cartboss_Money_Model
	 */
	public $balance;

	public function get_balance_view() {
		if ( ! $this->balance ) {
			return null;
		}

		return "{$this->balance->amount} {$this->balance->currency}";
	}
}

