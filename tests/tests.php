<?php

require_once("./GAPI.class.php");
require_once('./local_settings.php');

class Test_PHP_API extends PHPUnit_Framework_TestCase {

    protected $ok_user;
    protected $ok_pass;
    protected $bad_user;
    protected $bad_pass;
    protected $empty_list;
    protected $good_list;
    protected $bad_list;

    function __construct() {
        $this->ok_user = $GLOBALS['OK_USER'];
        $this->ok_user = $GLOBALS['OK_USER'];
        $this->ok_pass = $GLOBALS['OK_PASS'];
        $this->bad_user = $GLOBALS['BAD_USER'];
        $this->bad_pass = $GLOBALS['BAD_PASS'];
        $this->empty_list = $GLOBALS['EMPTY_LIST'];
        $this->good_list = $GLOBALS['GOOD_LIST'];
        $this->bad_list = $GLOBALS['BAD_LIST'];
    }

    protected function setUp() {
        $this->api = new GAPI($this->ok_user, $this->ok_pass);

        $this->api->attribute_create('foo');
        $this->api->attribute_delete('spam');

        // These contacts shouldn't exist:
        $this->api->contact_delete('non-existent@example.com');
        $this->api->contact_delete('created@example.com');
        $this->api->contact_delete('created2@example.com');
        // These should:
        $this->api->contact_create('existing@example.com', 'firstname', 'lastname', Array('foo'=>'bar'), 4);
        $this->api->contact_create('existing_del@example.com', 'firstname', 'lastname', Array('foo'=>'bar'), 4);

        // Clean up subscriptions:
        if ($this->api->subscriptions_listing($this->good_list)) {
            foreach ($this->api->result as $subscription) {
                $this->api->subscription_delete($subscription['email'], $this->good_list);
            }
        };
    }

    protected function set_up_subscriptions() {
        for ($i = 0; $i < 5; $i++) {
            $this->api->subscription_add('subscriber' . $i . '@example.com', $this->good_list);
        }
    }

    public function test__login() {
        $api = new GAPI($this->bad_user, $this->bad_pass);
        $this->assertFalse($api->login());

        $api = new GAPI($this->ok_user, $this->ok_pass);
        $this->assertTrue($api->login());
    }

    public function test__check_login() {
        $api = new GAPI($this->bad_user, $this->bad_pass);
        $this->assertFalse($api->check_login());

        $api = new GAPI($this->ok_user, $this->ok_pass);
        $this->assertTrue($api->check_login());
    }

    public function test__contact_show() {
        // Try retrieving a non-existent contact:
        $result = $this->api->contact_show('non-existent@example.com');
        $this->assertFalse($result);

        // Get existing contact with the attributes:
        $result = $this->api->contact_show('existing@example.com', True);
        $this->assertTrue($result);

        $this->assertEquals('existing@example.com', $this->api->result[0]['email']);
        $this->assertEquals('firstname', $this->api->result[0]['first_name']);
        $this->assertEquals('lastname', $this->api->result[0]['last_name']);
        $this->assertArrayHasKey('foo', $this->api->result[0]['attributes']);
        $this->assertEquals('bar', $this->api->result[0]['attributes']['foo']);
        $this->assertEquals(Array(), $this->api->result[0]['newsletters']);

        // Get it without the attributes:
        $result = $this->api->contact_show('existing@example.com', False);
        $this->assertTrue($result);

        $this->assertArrayNotHasKey('attributes', $this->api->result[0]);
    }

    public function test__contact_create() {
        // Create contact:
        $result = $this->api->contact_create('created@example.com', 'name');
        $this->assertTrue($result);

        // Try to create already existing account:
        $result = $this->api->contact_create('created@example.com', 'name');
        $this->assertFalse($result);

        // Try to create already existing contact in 'quiet' mode:
        $result = $this->api->contact_create('created@example.com', 'new name', null, Array('foo'=>'bar'), 2);
        $this->assertTrue($result);
        // The contact should not be changed:
        $this->api->contact_show('created@example.com');
        $this->assertEquals('name', $this->api->result[0]['first_name']);

        // Try to create already existing contact in 'update' mode:
        $result = $this->api->contact_create('created@example.com', null, 'last name', Array('foo'=>'fighter'), 3);
        $this->assertTrue($result);

        $this->api->contact_show('created@example.com', True);
        $this->assertEquals('name', $this->api->result[0]['first_name']);
        $this->assertEquals('last name', $this->api->result[0]['last_name']);
        $this->assertEquals('fighter', $this->api->result[0]['attributes']['foo']);

        // Try to create already existing contact in 'overwrite' mode:
        $result = $this->api->contact_create('created@example.com', null, 'new name', Array('foo'=>'bar'), 4);
        $this->assertTrue($result);
        $this->api->contact_show('created@example.com', True);
        $this->assertEquals('<nil/>', $this->api->result[0]['first_name']);
        $this->assertEquals('new name', $this->api->result[0]['last_name']);
        $this->assertEquals(Array('foo'=>'bar'), $this->api->result[0]['attributes']);
    }

    public function test__contact_delete() {
        // Try deleting a non-existing account:
        $result = $this->api->contact_delete('non-existent@example.com');
        $this->assertFalse($result);

        // Delete existing account:
        $result = $this->api->contact_delete('existing_del@example.com');
        $this->assertTrue($result);
        $this->assertFalse($this->api->contact_show('existing_del@example.com'));
    }

    public function test__subscriptions_listing() {
        $this->set_up_subscriptions();

        // Try listing a non-existent subscription list:
        $result = $this->api->subscriptions_listing($this->bad_list, 0, 2);
        $this->assertFalse($result);

        // Try listsing an empty subscription list:
        $result = $this->api->subscriptions_listing($this->empty_list, 0, 2);
        $this->assertFalse($result);

        // Listing non-empty subscription list:
        $result = $this->api->subscriptions_listing($this->good_list, 0, 2);
        $this->assertTrue($result);
        $this->assertCount(2, $this->api->result);

        $result = $this->api->subscriptions_listing($this->good_list);
        $this->assertTrue($result);
        $this->assertCount(5, $this->api->result);

        $keys = Array('confirmed', 'created', 'api-key', 'active', 'cancelled', 'email');
        foreach ($keys as $key) {
            $this->assertArrayHasKey($key, $this->api->result[0]);
        }

        // The empty fields must return the string '<nil/>':
        $this->assertEquals('<nil/>', $this->api->result[0]['api-key']);
    }

    public function test__subscription_delete() {
        $this->set_up_subscriptions();

        // Deleting non-existent subscription or non-existent list:
        $result = $this->api->subscription_delete('non-existent@example.com', $this->good_list);
        $this->assertFalse($result);

        $result = $this->api->subscription_delete('subscriber1@example.com', $this->bad_list);
        $this->assertFalse($result);

        // Deleting a subscription:
        $result = $this->api->subscription_delete('subscriber1@example.com', $this->good_list);
        $this->assertTrue($result);

        $this->api->subscriptions_listing($this->good_list);
        foreach ($this->api->result as $subscription) {
            $this->assertNotEquals($subscription['email'], 'subscriber1@example.com');
        }
    }

    public function test__subscription_add() {
        // Adding a subscription:
        $result = $this->api->subscription_add('existing@exmple.com', $this->good_list);
        $this->assertTrue($result);

        // Adding a new contact with subscription:
        $result = $this->api->subscription_add('created@example.com', $this->good_list, 'firstname', 'lastname', true,
            null, true, Array('foo'=>'bar'));
        $this->assertTrue($result);

        $this->api->contact_show('created@example.com', true);
        $this->assertEquals('firstname', $this->api->result[0]['first_name']);
        $this->assertEquals('lastname', $this->api->result[0]['last_name']);
        $this->assertEquals($this->good_list, $this->api->result[0]['newsletters'][0]['list_id']);

        // Trying to create existing subscription again or in non-existent list:
        $result = $this->api->subscription_add('created@example.com', $this->good_list);
        $this->assertFalse($result);
        $result = $this->api->subscription_add('created_new@example.com', $this->bad_list);
        $this->assertFalse($result);
    }

    public function test__newsletters_show() {
        $this->set_up_subscriptions();

        $result = $this->api->newsletters_show();
        $this->assertTrue($result);

        $this->assertCount(3, $this->api->result);

        foreach(Array('newsletter', 'sender', 'description', 'subscribers', 'list_id') as $key) {
            $this->assertArrayHasKey($key, $this->api->result[0]);
        }
    }

    public function test__attribute_listing() {
        $result = $this->api->attribute_listing();
        $this->assertTrue($result);

        $this->assertCount(1, $this->api->result);

        foreach(Array('usage', 'code', 'name') as $key) {
            $this->assertArrayHasKey($key, $this->api->result[0]);
        }

        // When there are no attributes, the method returns boolean true:
        $result = $this->api->attribute_delete('foo');
        $this->api->attribute_listing();
        $this->assertInternalType('boolean', $this->api->result);
        $this->assertEquals(true, $this->api->result);
    }

    public function test__attribute_create() {
        $result = $this->api->attribute_create('spam');
        $this->assertTrue($result);

        $this->api->attribute_listing();
        $this->assertCount(2, $this->api->result);

        $attrs = array_filter($this->api->result, function ($o) { return $o['name'] == 'spam'; });
        $this->assertCount(1, $attrs);
    }

    public function test__attribute_delete() {
        $result = $this->api->attribute_delete('not existing');
        $this->assertFalse($result);

        $result = $this->api->attribute_delete('foo');
        $this->assertTrue($result);
        $this->api->attribute_listing();
        $this->assertInternalType('boolean', $this->api->result);
        $this->assertEquals(true, $this->api->result);
    }
}

?>