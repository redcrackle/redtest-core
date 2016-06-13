<?php
/**
 * Created by PhpStorm.
 * User: Anil
 * Date: 3/9/14
 * Time: 4:00 PM
 */

namespace RedTest\core\entities\Commerce\ProfileType;

use RedTest\core\entities\Commerce\CommerceCustomerProfile;
use RedTest\core\entities\Commerce\CommerceLineItem;
use RedTest\core\Response;
use RedTest\core\entities\Entity;
use RedTest\core\Utils;
use RedTest\tests\MPUtils;

/**
 * Class Shipping
 *
 * @package RedTest\core\entities\Commerce\ProfileType
 */
class Shipping extends CommerceCustomerProfile {


  /**
   * Default constructor for the Commerce Shipping Line Item object.
   *
   * @param int $line_item_id
   *   Profile id if an existing profile is to be loaded.
   */
  public function __construct($profile_id = NULL) {

    parent::__construct($profile_id);
  }

  /**
   * This function will create shipping lineitem
   *
   * @param object $order
   *   Order object
   */
  public function createShippingProfileProgrammatically($order) {
    $entity = $this->getEntity();
    global $user;

    $wrapper = entity_metadata_wrapper('commerce_customer_profile', $entity);
    $wrapper->uid = $user->uid;
    $wrapper->commerce_customer_address->country = 'US';
    $wrapper->commerce_customer_address->name_line = Utils::getRandomString();
    $wrapper->commerce_customer_address->organisation_name = Utils::getRandomString();
    $wrapper->commerce_customer_address->administrative_area = 'CA';
    $wrapper->commerce_customer_address->locality = 'Sunnyvale';
    $wrapper->commerce_customer_address->dependent_locality = '';
    $wrapper->commerce_customer_address->postal_code = 94087;
    $wrapper->commerce_customer_address->thoroughfare = "929 E. El Camino Real";
    $wrapper->commerce_customer_address->premise = "Apt. 424";
    $wrapper->commerce_customer_address->phone_number = '(973) 328-6490';

    commerce_customer_profile_save($entity);

    return new Response(TRUE, $entity, "");
  }

}
