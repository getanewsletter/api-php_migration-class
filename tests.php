<?php

require_once("./GAPI.class.php");

class Test_PHP_API extends PHPUnit_Framework_TestCase {

    protected $ok_user = 'tester';
    protected $ok_pass = '123456789';
    protected $bad_user = 'wrong';
    protected $bad_pass = 'creds';
    protected $list_hash = 'HuYM5MtGCrEKHWEE';
    protected $bad_list_hash = 'no list';

    protected function setUp() {
        $this->api = new GAPI($this->ok_user, $this->ok_pass);

        $this->api->attribute_create('foo');

        // These contacts shouldn't exist:
        $this->api->contact_delete('non-existing@example.com');
        $this->api->contact_delete('created@example.com');
        // These should:
        $this->api->contact_create('existing@example.com', 'firstname', 'lastname', Array('foo'=>'bar'), 4);
        $this->api->contact_create('existing_del@example.com', 'firstname', 'lastname', Array('foo'=>'bar'), 4);
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
        // Try retrieving a non-existing contact:
        $this->assertFalse($this->api->contact_show('non-existing@example.com'));

        // Get existing contact with the attributes:
        $this->assertTrue($this->api->contact_show('existing@example.com', True));
        $this->assertEquals($this->api->result[0]['email'], 'existing@example.com');
        $this->assertEquals($this->api->result[0]['first_name'], 'firstname');
        $this->assertEquals($this->api->result[0]['last_name'], 'lastname');
        $this->assertEquals($this->api->result[0]['attributes'], Array('foo'=>'bar'));

        // Get it without the attributes:
        $this->assertTrue($this->api->contact_show('existing@example.com', False));
        $this->assertFalse(array_key_exists('attributes', $this->api->result[0]));
    }

    public function test__contact_create() {
        // Create contact:
        $this->assertTrue($this->api->contact_create('created@example.com', 'name'));

        // Try to create already existing account:
        $this->assertFalse($this->api->contact_create('created@example.com', 'name'));

        // Try to create already existing contact in 'quiet' mode:
        $this->assertTrue($this->api->contact_create('created@example.com', 'new name', null, Array('foo'=>'bar'), 2));
        // The contact should not be changed:
        $this->api->contact_show('created@example.com');
        $this->assertEquals($this->api->result[0]['first_name'], 'name');

        // Try to create already existing contact in 'update' mode:
        $this->assertTrue($this->api->contact_create('created@example.com', null, 'last name',
            Array('foo'=>'fighter'), 3));
        $this->api->contact_show('created@example.com', True);
        $this->assertEquals($this->api->result[0]['first_name'], 'name');
        $this->assertEquals($this->api->result[0]['last_name'], 'last name');
        $this->assertEquals($this->api->result[0]['attributes'], Array('foo'=>'fighter'));

        // Try to create already existing contact in 'overwrite' mode:
        $this->assertTrue($this->api->contact_create('created@example.com', null, 'new name',
            Array('foo'=>'bar'), 4));
        $this->api->contact_show('created@example.com', True);
        $this->assertEquals($this->api->result[0]['first_name'], '<nil/>');
        $this->assertEquals($this->api->result[0]['last_name'], 'new name');
        $this->assertEquals($this->api->result[0]['attributes'], Array('foo'=>'bar'));
    }

    public function test__contact_delete() {
        // Try deleting a non-existing account:
        $this->assertFalse($this->api->contact_delete('non-existing@example.com'));
        // Delete existing account:
        $this->assertTrue($this->api->contact_delete('existing_del@example.com'));
        $this->assertFalse($this->api->contact_show('existing_del@example.com'));
    }

    public function test__subscriptions_listing() {
        $this->assertFalse($this->api->subscriptions_listing($this->bad_list_hash));
        // TODO ...
    }
}

?>