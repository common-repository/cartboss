<?php


use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

abstract class Cartboss_Event_Model extends Cartboss_Base_Model {
	/**
	 * @var string
	 */
	var $platform = 'WORDPRESS';
	/**
	 * @var string
	 */
	var $version;
	/**
	 * @var string
	 */
	var $event;
	/**
	 * @var int
	 */
	var $timestamp;
	/**
	 * @var Cartboss_Contact_Model|null
	 */
	public $contact;
	/**
	 * @var Cartboss_Order_Model|null
	 */
	public $order;
	/**
	 * @var string|null
	 */
	public $attribution;
	/**
	 * @var bool
	 */
	public $debug = false;
	/**
	 * @var Serializer
	 */
	private $serializer;

	public function __construct( $version, $debug = false ) {
		$this->version = $version;
		$this->debug = $debug;
		$this->serializer = new Serializer( [ new ObjectNormalizer() ], [ new JsonEncoder() ] );
	}

	public final function serialize(): string {
		$this->timestamp = time();

		return $this->serializer->serialize( $this, 'json', ['json_encode_options' => JSON_UNESCAPED_SLASHES]);
	}
}

class Cartboss_AddToCart_Event_Model extends Cartboss_Event_Model {
	var $event = "AddToCart";
}

class Cartboss_Purchase_Event_Model extends Cartboss_Event_Model {
	var $event = "Purchase";
}
