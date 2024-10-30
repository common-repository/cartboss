<?php


use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class Cartboss_Order_Model extends Cartboss_Base_Model {
    /**
     * @var string|null
     */
    public $id;
    /**
     * @var string|null
     */
    public $number;
    /**
     * @var float
     */
    public $value;
    /**
     * @var string|null
     */
    public $currency;
    /**
     * @var string|null
     */
    public $checkout_url;
    /**
     * @var bool
     */
    public $is_cod = false;
    /**
     * @var array
     */
    public $items = array();
    /**
     * @var array
     */
    public $metadata = null;
    /**
     * @var Cartboss_Order_Address_Model|null
     */
    public $billing_address;
    /**
     * @var Cartboss_Order_Address_Model|null
     */
    public $shipping_address;

    /**
     * @var Serializer
     */
    private $serializer;

    public function __construct() {
        $this->serializer = new Serializer([new ObjectNormalizer()], [new JsonEncoder()]);
    }

    public function addCartItem(Cartboss_Order_Cart_Item_Model $cart_item) {
        array_push($this->items, $cart_item);
    }

    public final function serialize(): string {
        return $this->serializer->serialize($this, 'json', []);
    }
}

class Cartboss_OrderExtended_Model extends Cartboss_Order_Model {
    /**
     * @var string|null
     */
    public $state;
    /**
     * @var int
     */
    public $created_at;

    /**
     * @return bool
     */
    public function is_abandoned(): bool {
        return $this->state == 'abandoned';
    }

    /**
     * @return bool
     */
    public function is_paid(): bool {
        return $this->state == 'paid';
    }
}


class Cartboss_Order_Cart_Item_Model extends Cartboss_Base_Model {
    /**
     * @var string|null
     */
    public $id;
    /**
     * @var string|null
     */
    public $variation_id;
    /**
     * @var string|null
     */
    public $name;
    /**
     * @var int
     */
    public $quantity;
    /**
     * @var string|null
     */
    public $image_url;
    /**
     * @var float
     */
    public $price;
}

class Cartboss_Order_Address_Model extends Cartboss_Base_Model {
    /**
     * @var string|null
     */
    public $phone;
    /**
     * @var string|null
     */
    public $email;
    /**
     * @var string|null
     */
    public $first_name;
    /**
     * @var string|null
     */
    public $last_name;
    /**
     * @var string|null
     */
    public $company;
    /**
     * @var string|null
     */
    public $address_1;
    /**
     * @var string|null
     */
    public $address_2;
    /**
     * @var string|null
     */
    public $city;
    /**
     * @var string|null
     */
    public $state;
    /**
     * @var string|null
     */
    public $postal_code;
    /**
     * @var string|null
     */
    public $country;
}