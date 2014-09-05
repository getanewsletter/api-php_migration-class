<?php
require_once ("./lib/httpful.phar");

use \Httpful\Http;
use \Httpful\Request;

/*
 * For more information on Get a Newsletter's API visit http://admin.getanewsletter.com/api/.
 *
 * If you have any questions, please send us an email at support@getanewsletter.com.
 */
class GAPI
{

    /*
     * The current version of the interface.
     */
    var $version = 'v3.0';

    /*
     * The address of the API server.
     */
    var $address = 'https://api.getanewsletter.com';

    /*
     * The version of the API.
     */
    var $api_version = 'v3';

    /*
     * Contains the last error code.
     */
    var $errorCode;

    /*
     * Contains the last error message.
     */
    var $errorMessage;

    /*
     * Contains the username. Unused in API version 3.
     */
    var $username;

    /*
     * Contains the API token.
     */
    var $password;

    /*
     * Holds the result of the last successful API call.
     */
    var $result;

    /*
     * Holds the response object of the last API call.
     */
    private $response;

    /*
     * GAPI()
     *
     * A constructor. Prepares the interface for work with the API.
     *
     * Arguments
     * =========
     * $username  = Not used in the API version 3.
     * $password  = The API token.
     *
     */
    function GAPI($username, $password)
    {
        $this->username = $username;
        $this->password = $password;

        $json_handler = new Httpful\Handlers\JsonHandler(array('decode_as_array' => true));
        Httpful\Httpful::register('application/json', $json_handler);

        $template = Request::init()
            ->addHeader('Accept', 'application/json;')
            ->addHeader('Authorization', 'Token ' . $this->password)
            ->expects('application/json')
            ->sendsJson();

        Request::ini($template);
    }

    /*
     * show_errors()
     *
     * Method that returns a formatted error string.
     *
     * Return value
     * ============
     * String with the last error code and message.
     *
     */

    function show_errors()
    {
        return $this->errorCode . ": " . $this->errorMessage;
    }

    /*
     * login()
     *
     * DEPRECATED. Method used to log the user into the API.
     * Now has the same function as check_login().
     *
     * Return value
     * ============
     * True on success. False on error.
     *
     * In case of an error $errorCode and $errorMessage will be updated.
     */
    function login()
    {
        return $this->check_login();
    }

    /*
     * check_login()
     *
     * Method that checks if the token is correct.
     *
     * Return value
     * ============
     * True on success. False on error or when the token is incorrect.
     *
     * In case of an error $errorCode and $errorMessage will be updated.
     */
    function check_login()
    {
        return $this->call_api(Http::GET, 'user/');
    }

    /*
     * parse_errors($body)
     *
     * Internal method that extracts the error messages from
     * a failed API call.
     *
     * Arguments
     * =========
     * $body = The raw response body.
     *
     * Return value
     * ============
     * String with the error messages.
     */
    private static function parse_errors($body)
    {
        $errors = '';
        if (is_array($body)) {
            foreach ($body as $field => $error) {
                if (is_array($error)) {
                    foreach ($error as $error_string) {
                        $errors .= $error_string . ' ';
                    }
                } else {
                    $errors .= $error . ' ';
                }
            }
            $errors = trim($errors);
        } else {
            $errors = 'Error.';
        }
        return $errors;
    }

    /*
     * call_api($method, $endpoint, $args=null)
     *
     * Internal method that makes the actual REST calls to the API.
     *
     * Arguments
     * =========
     * $method      = Method to use for the call. One of the following:
                      Http::GET,
     *                Http::POST,
     *                Http::PUT,
     *                Http::PATCH;
     * $endpoint    = The API endpoint, e.g. 'contacts/test@example.com/'.
     * $args        = Associative array of arguments when the method is Http::POST,
     *                Http::PUT or Http::PATCH. For example: array('foo' => 'bar')
     *
     * $method and $endpoint are mandatory arguments.
     *
     * Return value
     * ============
     * True on success. False on error.
     *
     * In case of an error $errorCode and $errorMessage will be updated.
     */
    protected function call_api($method, $endpoint, $args=null)
    {
        $uri = $this->address . '/' . $this->api_version . '/' . $endpoint;

        $request = Request::init($method)->uri($uri);

        if ($args) {
            $request->body($args);
        }

        // TODO: Handle ConnectionErrorException.
        // TODO: Handle Exception: Unable to parse response as JSON and other exceptions.
        $this->response = $request->send();

        if (floor($this->response->code / 100) == 2) {
            $this->result = true;

        } else {
            $this->errorCode = $this->response->code;
            $this->errorMessage = self::parse_errors($this->response->body);

            $this->result = false;
        }

        return $this->result;
    }

    /*
     * contact_create($email, $first_name=null, $last_name=null, $attributes=null, $mode=null)
     *
     * Creates or updates a contact.
     *
     * Arguments
     * =========
     * $email        = E-mail address of the contact.
     * $first_name   = Contact's first name.
     * $last_name    = Contact's last name.
     * $attributes   = Associative array of the contact's attributes.
     * $mode         = Specifies how to handle the existing contacts:
     *
     *     (default)  1 = Return the error message if the contact exists.
     *                2 = If the contact exists return true without updating anything.
     *                3 = Update the contact. Just the non-null arguments will be used,
     *                    e.g. passing null to $fist_name means that
     *                    the first name of the contact won't be changed.
     *                4 = Overwrite the contact. All arguments will be used.
     *
     * Only $email is a mandatory argument.
     *
     * Return value
     * ============
     * True on success. False on error.
     *
     * In case of an error $errorCode and $errorMessage will be updated.
     */
    function contact_create($email, $first_name = null, $last_name = null, $attributes = array(), $mode = 1)
    {
        $data = array(
            'email' => $email,
            'first_name' => $first_name,
            'last_name' => $last_name,
        );

        if (!empty($attributes)) {
            $data['attributes'] = $attributes;
        }

        if ($mode == 1) {
            return $this->call_api(Http::POST, 'contacts/', $data);
        } else if ($mode == 2) {
            $this->call_api(Http::POST, 'contacts/', $data);
            return true;
        } else if ($mode == 3) {
            foreach ($data as $field => $value) {
                if (!$value) {
                    unset($data[$field]);
                }
            }
            return $this->call_api(Http::PATCH, 'contacts/' . $email . '/', $data);
        } else {
            return $this->call_api(Http::PUT, 'contacts/' . $email . '/', $data);
        }
    }

    /*
     * subscription_add(
     *     $email, $list_id, $first_name = null, $last_name = null, $confirmation = false, $api_key = null,
     *     $autoresponder = true, $attributes = array()
     * )
     *
     * Adds a subscriber to a list. If the contact doesn't exist it will create it.
     * If it does exist, subscription_add() will update the contact's data.
     *
     * Arguments
     * =========
     * $email         = E-mail address of the contact.
     * $list_id       = The newsletter's id hash. Can be obtained with newsletter_show().
     * $first_name    = Contact's first name.
     * $last_name     = Contact's last name.
     * $confirmation  = Not used in the version 3 of the API.
     * $api_key       = Not used in the version 3 of the API.
     * $autoresponder = Not used in the version 3 of the API.
     * $attributes    = Associative array of the contact's attributes.
     *
     * Only $email and $list_id are mandatory arguments.
     *
     * Return value
     * ============
     * True on success. False on error.
     *
     * In case of an error $errorCode and $errorMessage will be updated.
     */
    function subscription_add(
        $email, $list_id, $first_name = null, $last_name = null, $confirmation = false, $api_key = null,
        $autoresponder = true, $attributes = array()
    ) {
        $lists = array();

        if ($this->contact_show($email)) {
            foreach ($this->result[0]['newsletters'] as $list) {
                $lists[] = array('hash' => $list['list_id'], 'confirmed' =>  true);
                if ($list['list_id'] == $list_id) {
                    $this->errorCode = 405;
                    $this->errorMessage = 'The subscription already exists.';
                    return false;
                }
            }
        }

        $lists[] = array('hash' => $list_id, 'confirmed' =>  true);

        $data = array(
            'email' => $email,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'lists' => $lists
        );

        if (!empty($attributes)) {
            $data['attributes'] = $attributes;
        }

        return $this->call_api(Http::PUT, 'contacts/' . $email . '/', $data);
    }

    /*
     * contact_delete()
     *
     * Deletes a contact.
     *
     * Arguments
     * =========
     * $email        = E-mail address of the contact. Mandatory argument.
     *
     * Return value
     * ============
     * True on success. False on error.
     *
     * In case of an error $errorCode and $errorMessage will be updated.
     */
    function contact_delete($email)
    {
        return $this->call_api(Http::DELETE, 'contacts/' . $email . '/');
    }

    /*
     * contact_show($email, $show_attributes = false)
     *
     * Used to retrieve detailed information for a contact.
     *
     * Arguments
     * =========
     * $email           = E-mail address of the contact. Mandatory argument.
     * $show_attributes = If true, the result will have a field 'attributes',
     *                    an associative array of all contact attributes in
     *                    the form ['attribute_name' => 'attribute_value'].
     *                    The default is false.
     *
     * Return value
     * ============
     * True on success. False on error.
     *
     * In case of an error $errorCode and $errorMessage will be updated.
     * On success, the contacts information will be stored in $result.
     */
    function contact_show($email, $show_attributes = false)
    {
        $attributes = array();

        if ($show_attributes) {
            $ok = $this->attribute_listing();
            if (!$ok) {
                return false;
            }

            foreach ($this->result as $attr) {
                $attributes[$attr['name']] = '<nil/>';
            }
        }

        $status = $this->call_api(Http::GET, 'contacts/' . $email . '/');
        if ($status) {
            $data = $this->response->body;

            if (!$data['first_name']) {
                $data['first_name'] = '<nil/>';
            }
            if (!$data['last_name']) {
                $data['last_name'] = '<nil/>';
            }

            $contact_attributes = $data['attributes'];

            if (!$show_attributes) {
                unset($data['attributes']);
            } else {
                $data['attributes'] = $attributes;
                foreach ($contact_attributes as $name => $value) {
                    $data['attributes'][$name] = $value;
                }
            }

            $data['newsletters'] = array();
            foreach ($data['lists'] as $list) {
                $data['newsletters'][] = array(
                    'created' => $list['subscription_created'],
                    'list_id' => $list['hash'],
                    'cancelled' => $list['subscription_cancelled'] ? $list['subscription_cancelled'] : '<nil/>',
                    'newsletter' => $list['name']
                );
            }
            unset($data['lists']);

            $this->result = array($data);
        }
        return $status;
    }

    /*
     * subscription_delete($email, list_id)
     *
     * Removes a subscription.
     *
     * Arguments
     * =========
     * $email     = The e-mail address of the contact.
     * $list_id   = The newsletter's id hash. Can be obtained with newsletter_show().
     *
     * Return value
     * ============
     * True on success. False on error.
     *
     * In case of an error $errorCode and $errorMessage will be updated.
     */
    function subscription_delete($email, $list_id)
    {
        return $this->call_api(Http::DELETE, 'lists/'. $list_id . '/subscribers/' . $email . '/');
    }

    /*
     * newsletter_show()
     *
     * Used to retrieve information for a newsletter.
     *
     * Return value
     * ============
     * True on success. False on error.
     *
     * In case of an error $errorCode and $errorMessage will be updated.
     * On success, the newsletter's data will be stored in $result.
     */
    function newsletters_show()
    {
        $ok = $this->call_api(Http::GET, 'lists/');
        if ($ok) {
            $this->result = array();

            foreach ($this->response->body['results'] as $list) {
                $this->result[] = array(
                    'newsletter' => $list['name'],
                    'sender' => $list['sender'].' '.$list['email'],
                    'description' => $list['description'] ? $list['description'] : '<nil/>',
                    'subscribers' => $list['active_subscribers_count'],
                    'list_id' => $list['hash'],
                );
            }
        }
        return $ok;
    }

    /*
     * subscriptions_listing($list_id, $start=null, $end=null)
     *
     * Retrieves the list of subscriptions to a newsletter. Returns maximum of 100 at a time.
     *
     * Arguments
     * =========
     * $list_id   = The id of the newsletter.
     * $start	  = The index of the subscription to start the listing from.
     * $end 	  = The index of the subscription to stop the listing to.
     *
     * Warning: $start and $end will be ignored in this version of the interface
     * and may be subjected to a change in future versions.
     *
     * Return value
     * ============
     * True on success. False on error.
     *
     * In case of an error $errorCode and $errorMessage will be updated.
     * On success will store the list of subscriptions in $result.
     */
    function subscriptions_listing($list_id, $start = null, $end = null)
    {
        $ok = $this->call_api(Http::GET, 'lists/' . $list_id . '/subscribers/?paginate_by=100');
        if ($ok) {
            $this->result = array();
            foreach ($this->response->body['results'] as $subs) {
                $this->result[] = array(
                    'created' => $subs['created'],
                    'cancelled' => $subs['cancelled'] ? $subs['cancelled'] : '<nil/>',
                    'email' => $subs['contact']
                );
            }
        }
        return $ok;
    }

    /*
     * attribute_get_code($name)
     *
     * Internal method that is used to get the code of an attribute by given name.
     *
     * Arguments
     * =========
     * $name    = The name of the attribute.
     *
     * Return value
     * ============
     * The attribute's code on success, null on error.
     *
     * In case of an error $errorCode and $errorMessage will be updated.
     */
    protected function attribute_get_code($name)
    {
        $ok = $this->attribute_listing();
        if(!$ok) {
            return null;
        }

        $attribute_list = array();
        foreach ($this->result as $value) {
            if ($value['name'] == $name) {
                return $value['code'];
            }
        }

        $this->errorCode = 404;
        $this->errorMessage = 'Attribute not found.';
        return null;
    }

    /*
     * attribute_create($name)
     *
     * Used to create an attribute.
     *
     * Arguments
     * =========
     * $name    = Attribute's name.
     *
     * Return value
     * ============
     * True on success. False on error.
     *
     * In case of an error $errorCode and $errorMessage will be updated.
     */
    function attribute_create($name)
    {
        return $this->call_api(Http::POST, 'attributes/', array('name' => $name));
    }

    /*
     * attribute_delete($name)
     *
     * Used to delete an attribute.
     *
     * Arguments
     * =========
     * $name    = Attribute's name.
     *
     * Return value
     * ============
     * True on success. False on error.
     *
     * In case of an error $errorCode and $errorMessage will be updated.
     */
    function attribute_delete($name)
    {
        $code = $this->attribute_get_code($name);
        if ($code) {
            return $this->call_api(Http::DELETE, 'attributes/' . $code . '/');
        } else {
            return false;
        }
    }

    /*
     * attribute_listing($name)
     *
     * Retrieves the list of all attributes.
     *
     * Return value
     * ============
     * True on success. False on error.
     *
     * In case of an error $errorCode and $errorMessage will be updated.
     * On success the list of attributes will be stored in $result.
     */
    function attribute_listing()
    {
        $ok = $this->call_api(Http::GET, 'attributes/');
        if ($ok) {
            $this->result = $this->response->body['results'];

            foreach ($this->result as $id => $value) {
                $this->result[$id]['usage'] = $value['usage_count'];
                unset($this->result[$id]['usage_count']);
            }
        }
        return $ok;
    }

    /*
     * reports_bounces($id, $filter, $start=null, $end=null)
     *
     * Retrieves the list of the reported bounces. Returns maximum of 100 at a time.
     *
     * Arguments
     * =========
     * $id        = The id of the report.
     * $filter    = Not implemented yet.
     * $start	  = The index of the bounce to begin the listing from.
     * $end 	  = The index of the bounce to end the listing to.
     *
     * Warning: $start and $end will be ignored in this version of the interface
     * and may be subjected to a change in future versions.
     *
     * Return value
     * ============
     * True on success. False on error.
     *
     * In case of an error $errorCode and $errorMessage will be updated.
     *
     * On success, the list of bounces will be stored in $result.
     * The result will be an array of dictionaries with the following fields:
     *
     * email       = The e-mail address of the recipient.
     * status      = The status of the bounce that will be given in the format X.X.X, where
     *               X are numbers. If the first one is 5 then this is a hard (permanent) bounce,
     *               if it's 4 - the bounce is a soft (temporary) bounce. More information
     *               about the bounce status codes can be found in the knowledge base:
     *               http://help.getanewsletter.com/
     */
    function reports_bounces($id, $filter = null, $start = null, $end = null)
    {
        $ok = $this->call_api(Http::GET, 'reports/' . $id . '/bounces/?paginate_by=100');
        if ($ok) {
            $this->result = array();
            foreach ($this->response->body['results'] as $bounce) {
                $this->result[] = array(
                    'status' => $bounce['status'],
                    'email' => $bounce['contact']
                );
            }
        }
        return $ok;
    }

    /*
     * reports_link_clicks($id, $filter, $start=null, $end=null)
     *
     * This method is obsolete. Use reports_clicks_per_link() instead.
     *
     */
    function reports_link_clicks($id, $filter = null, $start = null, $end = null)
    {
        throw new Exception('This method is obsolete. Use reports_clicks_per_link().');
    }

    /*
     * reports_clicks_per_link($id, $link_id)
     *
     * Retrieves the list of clicks for given link.
     *
     * Attributes
     * ==========
     * $id      = The id of the report.
     * $link_id = The is of the link. Can be obtained from
     */
    function reports_clicks_per_link($id, $link_id)
    {
        $ok = $this->call_api(Http::GET, 'reports/' . $id . '/links/' . $link_id . '/clicks/?paginate_by=100');
        if ($ok) {
            $this->result = array();
            foreach ($this->response->body['results'] as $link) {
                $this->result[] = array(
                    'count' => $link['total_clicks'],
                    'url' => $link['url'],
                    'first_click'=> $link['first_click'],
                    'email' => $link['contact'],
                    'last_click' => $link['last_click']
                );
            }
        }
        return $ok;
    }

    /*
     * reports_links($id)
     *
     * Retrieves the list of links in a newsletter and information about them.
     *
     * Arguments
     * =========
     * $id      = The id of the report.
     *
     * Return value
     * ============
     * True on success. False on error.
     *
     * In case of an error $errorCode and $errorMessage will be updated.
     *
     * On success, the list of links will be stored in $result.
     * The result will be an array of dictionaries with the following fields:
     *
     * count    = The number of unique clicks on that link.
     * link     = The URL of the link.
     */
    function reports_links($id)
    {
        $ok = $this->call_api(Http::GET, 'reports/' . $id . '/links/?ordering=id');
        if ($ok) {
            $this->result = array();
            foreach ($this->response->body['results'] as $link) {
                $this->result[] = array(
                    'count' => $link['unique_clicks'],
                    'link' => $link['link'],
                    'id' => $link['id']
                );
            }
        }
        return $ok;
    }

    /*
     * reports_listing($latest = true)
     *
     * Retrieves the list of reports.
     *
     * Arguments
     * =========
     * $latest  = If true will list the latest reports first. Default is true.
     *
     * Return value
     * ============
     * True on success. False on error.
     *
     * In case of an error $errorCode and $errorMessage will be updated.
     * On success, the list of reports will be stored in $result.
     */
    function reports_listing($latest = true)
    {
        $ordering = $latest ? '-sent' : 'sent';
        $ok = $this->call_api(Http::GET, 'reports/?orderging=' . $ordering);
        if ($ok) {
            $this->result = array();
            foreach ($this->response->body['results'] as $report) {
                $this->result[] = array(
                  'lists' => join(', ', $report['sent_to_lists']),
                  'date' => $report['sent'],
                  'sent_to' => $report['sent_to'],
                  'url' => $report['url'],
                  'id' => $report['id'],
                  'subject' => $report['mail_subject'],
                  'opens' => $report['total_html_opened']
                );
            }
        }
        return $ok;
    }

    /*
     * reports_opens($id)
     *
     * Retrieves the list of the reported opens. Returns maximum of 100 at a time.
     *
     * Arguments
     * =========
     * $id        = The id of the report.
     *
     * Return value
     * ============
     * True on success. False on error.
     *
     * In case of an error $errorCode and $errorMessage will be updated.
     * On success, the list of opens will be stored in $result.
     */
    function reports_opens($id)
    {
        $ok = $this->call_api(Http::GET, 'reports/' . $id . '/opens/?paginate_by=100');
        if ($ok) {
            $this->result = array();
            foreach ($this->response->body['results'] as $open) {
                $this->result[] = array(
                    'count' => $open['count'],
                    'first_view' => $open['first_view'],
                    'email' => $open['contact'],
                    'last_view' => $open['last_view']
                );
            }
        }
        return $ok;
    }

    /*
     * reports_unsubscribes($id, $start = null, $end = null)
     *
     * Retrieves the list of the reported unsubscribes. Returns maximum of 100 at a time.
     *
     * Arguments
     * =========
     * $id        = The id of the report.
     * $start     = The index of the bounce to begin the listing from.
     * $end       = The index of the bounce to end the listing to.
     *
     * Warning: $start and $end will be ignored in this version of the interface
     * and may be subjected to a change in future versions.
     *
     * Return value
     * ============
     * True on success. False on error.
     *
     * In case of an error $errorCode and $errorMessage will be updated.
     * On success, the list of unsubscribes will be stored in $result.
     */
    function reports_unsubscribes($id, $start = null, $end = null)
    {
        $ok = $this->call_api(Http::GET, 'reports/' . $id . '/unsubscribed/?paginate_by=100');
        if ($ok) {
            $this->result = array();
            foreach ($this->response->body['results'] as $unsub) {
                $this->result[] = array(
                    'email' => $unsub['contact'],
                    'date' => $unsub['created']
                );
            }
        }
        return $ok;
    }
}
