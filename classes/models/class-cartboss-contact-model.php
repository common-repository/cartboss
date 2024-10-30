<?php


class Cartboss_Contact_Model extends Cartboss_Base_Model {
	/**
	 * @var string|null
	 */
	public $phone = null;
	/**
	 * @var string|null
	 */
	public $email = null;
	/**
	 * @var string|null
	 */
	public $ip_address = null;
	/**
	 * @var string|null
	 */
	public $user_agent = null;
	/**
	 * @var string|null
	 */
	public $country = null;
	/**
	 * @var string|null
	 */
	public $first_name = null;
	/**
	 * @var string|null
	 */
	public $last_name = null;
	/**
	 * @var bool
	 */
	public $accepts_marketing = false;
}

