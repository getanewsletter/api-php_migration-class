<?php

require_once('./GAPI.class.php');
require_once('./local_settings.php');

class Test_PHP_API extends PHPUnit_Framework_TestCase {

    protected $ok_user;
    protected $ok_pass;

    function __construct()
    {
        $this->ok_user = $GLOBALS['OK_USER_REPORTS'];
        $this->ok_pass = $GLOBALS['OK_PASS_REPORTS'];
    }

    protected function setUp()
    {
        $this->api = new GAPI($this->ok_user, $this->ok_pass);

        // A report is set up on the tester2 user by running the report generator
        // with default options.
        $this->api->reports_listing();
        $this->report_id = $this->api->result[0]['id'];
        $this->bad_report_id = 'xxx';
    }

    public function test__reports_listing()
    {
        // WARNING: The old API returns error if there are no reports:
        // 500: <class 'getanewsletter.reports.models.DoesNotExist'>:Report matching query does not exist.
        // This behaviour will not be tested (nor simulated) in the new API.

        $result = $this->api->reports_listing();
        $this->assertTrue($result);
        $this->assertCount(1, $this->api->result);

        // TODO: See if we should fix the typo and inform the customers.
        // WARNING: The old tool returns a key with typo: 'unsubsribe' instead of 'unsubscribe':
        $keys = array('lists', 'date' , 'sent_to', 'unsubsribe', 'unique_opens', 'url', 'bounces', 'id', 'link_click',
                      'subject', 'opens');

        foreach ($keys as $key) {
            $this->assertArrayHasKey($key, $this->api->result[0]);
        }
    }

    public function test__reports_bounces()
    {
        $result = $this->api->reports_bounces($this->bad_report_id);
        $this->assertFalse($result);

        // List all bounces:
        $result = $this->api->reports_bounces($this->report_id);
        $this->assertTrue($result);
        $this->assertCount(30, $this->api->result);
        foreach (array('status', 'email') as $key) {
            $this->assertArrayHasKey($key, $this->api->result[0]);
        }
        $result = $this->api->reports_bounces($this->report_id, null);
        $this->assertTrue($result);
        $this->assertCount(30, $this->api->result);

        // // TODO: See how to handle pagination, $start and $end.
        // // List some bounces:
        // $result = $this->api->reports_bounces($this->report_id, null, 10, 20);
        // $this->assertTrue($result);
        // $this->assertCount(10, $this->api->result);

        // // TODO: Add filtering in the API.
        // // Filter bounces by type. Soft:
        // $result = $this->api->reports_bounces($this->report_id, 'soft');
        // $this->assertTrue($result);
        // $this->assertCount(24, $this->api->result);

        // // Filter bounces by type. Hard:
        // $result = $this->api->reports_bounces($this->report_id, 'hard');
        // $this->assertTrue($result);
        // $this->assertCount(6, $this->api->result);

        // // Combine filter with limit:
        // $result = $this->api->reports_bounces($this->report_id, 'soft', 10, 20);
        // $this->assertTrue($result);
        // $this->assertCount(10, $this->api->result);

        // // Giving other that 'soft' or 'hard' filter returns all the results:
        // $result = $this->api->reports_bounces($this->report_id, 'something else');
        // $this->assertTrue($result);
        // $this->assertCount(30, $this->api->result);
    }

    public function test__reports_link_clicks()
    {
        // // TODO: See how to get all unique clicks.
        // $result = $this->api->reports_link_clicks($this->bad_report_id);
        // $this->assertFalse($result);
        //
        // $result = $this->api->reports_link_clicks($this->report_id);
        // $this->assertTrue($result);
        // var_export($this->api->result);
        // $this->assertCount(18, $this->api->result);
        // foreach (array('count', 'url', 'first_click', 'email', 'last_click') as $key) {
            // $this->assertArrayHasKey($key, $this->api->result[0]);
        // }

        // // TODO: Filtering by url in the unique clicks.
        // // Filter by link URL:
        // $this->api->reports_links($this->report_id);
        // $url = $this->api->result[0]['link'];
        //
        // $result = $this->api->reports_link_clicks($this->report_id, $url);
        // $this->assertTrue($result);
        // $this->assertCount(3, $this->api->result);
        // foreach ($this->api->result as $link) {
            // $this->assertEquals($url, $link['url']);
        // }

        // // TODO: See how to handle pagination, $start and $end.
        // //Test limits:
        // $result = $this->api->reports_link_clicks($this->report_id, null, 10, 15);
        // $this->assertTrue($result);
        // $this->assertCount(5, $this->api->result);
    }

    public function test__reports_links()
    {
        $result = $this->api->reports_links($this->report_id);
        $this->assertTrue($result);
        $this->assertCount(4, $this->api->result);

        foreach (array('count', 'link') as $key) {
            $this->assertArrayHasKey($key, $this->api->result[0]);
        }

        $this->assertEquals(3, $this->api->result[0]['count']);
        $this->assertEquals(6, $this->api->result[1]['count']);
        $this->assertEquals(9, $this->api->result[2]['count']);
        $this->assertEquals(0, $this->api->result[3]['count']);
    }

    public function test__reports_opens()
    {
        $result = $this->api->reports_opens($this->report_id);
        $this->assertTrue($result);
        // TODO: This test fails, because there is a page limit of 100 in the API.
        // $this->assertCount(120, $this->api->result);

        foreach (array('count', 'first_view', 'email', 'last_view') as $key) {
            $this->assertArrayHasKey($key, $this->api->result[0]);
        }
    }

    public function test__reports_unsubscribes()
    {
        $result = $this->api->reports_unsubscribes($this->report_id);
        $this->assertTrue($result);
        $this->assertCount(10, $this->api->result);

        foreach (array('email', 'date') as $key) {
            $this->assertArrayHasKey($key, $this->api->result[0]);
        }

        // // TODO: See how to handle pagination, $start and $end.
        // // Test with limits:
        // $result = $this->api->reports_unsubscribes($this->report_id, 2, 6);
        // $this->assertTrue($result);
        // $this->assertCount(4, $this->api->result);
    }
}
