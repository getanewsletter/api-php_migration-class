<?php
require_once ("./lib/httpful.phar");

use \Httpful\Http;
use \Httpful\Request;

/*
 * För mer information om APIt till Get a Newsletter besök
 * http://admin.getanewsletter.com/api/
 *
 * Har du några frågor så skicka ett mejl till oss på
 * support@getanewsletter.com
 *
 */

class GAPI
{

    /*
     * Aktuell version av gränssnittet
     */
    var $version = 'v3.0';

    /*
     * Adress som används för uppkoppling mot servern.
     */
    var $address = 'https://api.getanewsletter.com';

    /*
     * Port som används för uppkoppling mot servern
     */
    var $port = '433';

    /**
     * The version of the API.
     */
    var $api_version = 'v3';

    /*
     * Innehåller felkod vid eventuella fel från XML-RPC
     * gränssnittet.
     */
    var $errorCode;

    /*
     * Innehåller felmeddelanden vid eventuella fel från
     * XML-RPC gränssnittet.
     */
    var $errorMessage;

    /*
     * Den skapade uppkopplingen till servern.
     */
    var $xmlrpc;

    /*
     * Innehåller det krypterade lösenordet som används för
     * inloggning på Get a Newsletter.
     */
    var $encrypted_password;

    /*
     * Användarnamn på Get a Newsletter
     */
    var $username;

    /*
     * Lösenord på Get a Newsletter.
     */
    var $password;

    /*
     * Resultatet från XML-RPC anropet.
     */

    var $result;

    /**
     * The response object of the last call.
     */
    private $response;

    /*
     * GAPI()
     *
     * Konstruktor som skapar en uppkoppling mot XML-RPC gränssnittet
     * och loggar in användaren.
     *
     * Argument
     * ==========
     * $username  = Användarnamnet på Get a Newsletter
     * $password  = Lösenordet på Get a Newsletter
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

        // $this -> login();
    }

    /*
     * show_errors()
     *
     * Funktion för att skriva ut felmeddelanden
     *
     * Returvärden
     * =========
     * Sträng med felkod och meddelande
     *
     */

    function show_errors()
    {
        return $this->errorCode . ": " . $this->errorMessage;
    }

    /*
     * login()
     *
     * Funktion för inloggning.
     *
     * Returvärden
     * =========
     * Sant/Falskt
     *
     * Eventuella fel finns i $errorCode
     * och $errorMessage
     *
     */

    function login()
    {
        return $this->check_login();
    }

    /*
     * check_login()
     *
     * Funktion som kontrollerar om det genererade
     * lösenordet är giltligt.
     *
     * Returvärden
     * =========
     * Sant/Falskt
     *
     * Eventuella fel finns i $errorCode
     * och $errorMessage
     */
    function check_login()
    {
        return $this->call_api(Http::GET, 'user');
    }

    private static function parse_errors($body)
    {
        $errors = '';
        if (is_array($body)) {
            foreach($body as $field => $error) {
                if (is_array($error)) {
                    foreach($error as $error_string) {
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

    function call_api($method, $endpoint, $args=null)
    {
        $uri = $this->address . '/' . $this->api_version . '/' . $endpoint . '/';

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
     * contact_create($email, $first_name=NULL, $last_name=NULL, $attributes=NULL, $mode=NULL)
     *
     * Skapar eller uppdaterar en kontakt.
     *
     * Argument
     * ==========
     * $email        = E-postadress som ska läggas till
     * $first_name   = Kontaktens förnamn
     * $last_name    = Kontaktens efternamn
     * $attributes   = En array med attribut som ska läggas till/uppdateras för kontakten
     * $mode         = Anger hur existerande kontakt ska hanteras, följande val finns:
     *
     *    (standard)  1 = Returnera felmeddelande om kontakten existerar
     *                2 = Om kontakt existerar, uppdatera inget och returnera sant.
     *                3 = Uppdatera kontakten med de nya uppgifterna
     *                4 = Rensa befintliga uppgifter (namn och attribut) samt infoga nya
     *
     * Det är endast $email som är obligatoriskt.
     *
     * Returvärden
     * =========
     * Sant/Falskt
     *
     * Eventuella fel finns i $errorCode
     * och $errorMessage
     *
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
            return $this->call_api(Http::POST, 'contacts', $data);
        } else if ($mode == 2) {
            $this->call_api(Http::POST, 'contacts', $data);
            return true;
        } else if ($mode == 3) {
            foreach($data as $field => $value) {
                if (!$value) {
                    unset($data[$field]);
                }
            }
            return $this->call_api(Http::PATCH, 'contacts/' . $email, $data);
        } else {
            return $this->call_api(Http::PUT, 'contacts/' . $email, $data);
        }
    }

    /*
     * subscription_add($email, $list_id, $first_name=NULL, $last_name=NULL, $confirmation=False, $api_key=NULL)
     *
     * Lägger till en kontakt (om den ej redan existerar) och en prenumeration till ett nyhetsbrev.
     *
     * Argument
     * ==========
     * $email        = E-postadress som ska läggas till
     * $list_id      = Sträng som identifierar nyhetsbrevet, fås genom newsletter_show()
     * $first_name   = Kontaktens förnamn
     * $last_name    = Kontaktens efternamn
     * $confirmation = Ska bekräftelse skickas ut till prenumeranten? Falskt som standard.
     *                 Krävs om prenumeration är avslutad tidigare
     * $api_key      = Nyckel för att kunna spåra varifrån en prenumeration kommer, hittas
     *                 i menyn Kontakter->Listor & API när du är inloggad på Get a Newsletter
     *
     * Det är endast $email och $list_id som är obligatoriskt.
     *
     * Returvärden
     * =========
     * Sant/Falskt
     *
     * Eventuella fel finns i $errorCode
     * och $errorMessage
     *
     */
    function subscription_add(
        $email, $list_id, $first_name = null, $last_name = null, $confirmation = false, $api_key = null,
        $autoresponder = true, $attributes = array()
    ) {
        $lists = array();

        if ($this->contact_show($email)) {
            foreach($this->result[0]['newsletters'] as $list) {
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

        return $this->call_api(Http::PUT, 'contacts/' . $email, $data);
    }

    /*
     * contact_delete()
     *
     * Funktion för att ta bort en kontakt från kontot.
     *
     * Argument
     * ==========
     * $email        = E-postadress för kontakten som ska tas bort
     *
     * Returvärden
     * =========
     * Sant/Falskt
     *
     * Eventuella fel finns i $errorCode
     * och $errorMessage
     *
     */
    function contact_delete($email)
    {
        return $this->call_api(Http::DELETE, 'contacts/' . $email);
    }

    /*
     * contact_show()
     *
     * Argument
     * =========
     * $email    = Kontaktens e-postadress
     *
     * Returvärden
     * =========
     * Sant/Falskt
     *
     * Eventuella fel finns i $errorCode
     * och $errorMessage
     *
     */
    function contact_show($email, $show_attributes = False)
    {
        $attributes = array();

        if ($show_attributes) {
            $ok = $this->attribute_listing();
            if (!$ok) {
                return false;
            }

            foreach($this->result as $attr) {
                $attributes[$attr['name']] = '<nil/>';
            }
        }

        $status = $this->call_api(Http::GET, 'contacts/' . $email);
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
                foreach($contact_attributes as $name => $value) {
                    $data['attributes'][$name] = $value;
                }
            }

            $data['newsletters'] = array();
            foreach($data['lists'] as $list) {
                $data['newsletters'][] = array(
                    'confirmed' => $list['subscription_created'],   // In APIv3 all subscriptions are confirmed.
                    'created' => $list['subscription_created'],
                    'api-key' => '<nil/>',  // No api-keys in APIv3.
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
     * Tar bort en prenumeration från ett nyhetsbrev
     *
     * Argument
     * =========
     * $email     = Kontaktens e-postadress
     * $list_id   = Sträng som identifierar nyhetsbrevet, fås genom newsletter_show()
     *
     * Returvärden
     * =========
     * Sant/Falskt
     *
     * Eventuella fel finns i $errorCode
     * och $errorMessage
     *
     */
    function subscription_delete($email, $list_id)
    {
        return $this->call_api(Http::DELETE, 'lists/'. $list_id . '/subscribers/' . $email);
    }

    /*
     * newsletter_show()
     *
     * Listar befintliga nyhetsbrev
     *
     * Returvärden
     * =========
     * Sant/Falskt
     *
     * Eventuella fel finns i $errorCode
     * och $errorMessage
     *
     */
    function newsletters_show()
    {
        $ok = $this->call_api(Http::GET, 'lists');
        if ($ok) {
            $this->result = array();

            foreach($this->response->body['results'] as $list) {
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
     * subscription_listing($list_id, $start=NULL, $end=NULL)
     *
     * Listar prenumerationer för ett nyhetsbrev, max 100 st i taget
     *
     * Argument
     * =========
     * $list_id   = Sträng som identifierar nyhetsbrevet, fås genom newsletter_show()
     * $start	= Post som listningen ska börja på
     * $end 	= Post som listningen ska sluta på
     *
     * Returvärden
     * =========
     * Array med prenumerationer för ett nyhetsbrev
     *
     * Eventuella fel finns i $errorCode
     * och $errorMessage
     *
     */

    function subscriptions_listing($list_id, $start = null, $end = null)
    {
        $ok = $this->call_api(Http::GET, 'lists/' . $list_id . '/subscribers');
        if ($ok) {
            $this->result = array();
            foreach($this->response->body['results'] as $subs) {
                $this->result[] = array(
                    'confirmed' => $subs['created'],    // In APIv3 all subscriptions are confirmed.
                    'created' => $subs['created'],
                    'api-key' => '<nil/>',    // In APIv3 api-key is not used.
                    'active' => '???',    // TODO: See if you can add 'active' field into the API.
                    'cancelled' => $subs['cancelled'] ? $subs['cancelled'] : '<nil/>',
                    'email' => $subs['contact']
                );
            }
        }
        return $ok;
    }

    /**
     * Returns attribute code by given attribute name.
     */
    private function attribute_get_code($name)
    {
        $ok = $this->attribute_listing();
        if(!$ok) {
            return null;
        }

        $attribute_list = array();
        foreach($this->result as $value) {
            if ($value['name'] == $name) {
                return $value['code'];
            }
        }

        $this->errorCode = 404;
        $this->errorMessage = 'Attribute not found.';
        return null;
    }

    function attribute_create($name)
    {
        return $this->call_api(Http::POST, 'attributes', array('name' => $name));
    }

    function attribute_delete($name)
    {
        $code = $this->attribute_get_code($name);
        if ($code) {
            return $this->call_api(Http::DELETE, 'attributes/' . $code);
        } else {
            return false;
        }
    }

    function attribute_listing()
    {
        $ok = $this->call_api(Http::GET, 'attributes');
        if ($ok) {
            $this->result = $this->response->body['results'];

            if(empty($this->result)) {
                // A weird behaviour from the old API:
                // When the result should be empty it returns boolean true.
                // TODO: Check if we should be consisted with this... thing.
                $this->result = true;
            } else {
                foreach($this->result as $id => $value) {
                    $this->result[$id]['usage'] = $value['usage_count'];
                    unset($this->result[$id]['usage_count']);
                }
            }
        }
        return $ok;
    }

    // /*
     // * reports_bounces($id, $filter, $start=NULL, $end=NULL)
     // *
     // * Listar prenumerationer för ett nyhetsbrev, max 100 st i taget
     // *
     // * Argument
     // * =========
     // * $id        = Id för rapporten du vill visa studsar för
     // * $filter    = Används om du vill filtrera resultatet. Ange "hard" för att
     // *              endast returnera adresser med hård studs (permanent fel) och
     // *              "soft" för att endast returnera adresser med temporärt fel. (Valbart)
     // * $start	= Post som listningen ska börja på
     // * $end 	= Post som listningen ska sluta på
     // *
     // * Returvärden
     // * =========
     // * Returnerar en array med följande information för varje rapport:
     // *
     // * email       E-post som har studsat
     // *
     // * status      Status för aktuell studs, ges som en sifferkombination med följande
     // *             format X.X.X. Om statusen börjar på en 5:a är det en hård studs
     // *             (felet är permanent) och om den börjar på 4:a är det en mjuk studs
     // *             (temporärt fel). Vill du ha mer information om statuskodernas
     // *             betydelse så kolla i manualen.
     // *
     // * Eventuella fel finns i $errorCode och $errorMessage
     // *
     // */
//
    // function reports_bounces($id, $filter = NULL, $start = NULL, $end = NULL) {
        // $params = Array();
        // $params[] = $id;
        // $params[] = $filter;
        // $params[] = $start;
        // $params[] = $end;
        // return $this -> callServer('reports.bounces', $params);
    // }
//
    // /*
     // * reports_link_clicks($id, $filter, $start=NULL, $end=NULL)
     // *
     // * Dokumentation: http://admin.getanewsletter.com/api/v0.1/reports.link_clicks/
     // *
     // */
//
    // function reports_link_clicks($id, $filter = NULL, $start = NULL, $end = NULL) {
        // $params = Array();
        // $params[] = $id;
        // $params[] = $filter;
        // $params[] = $start;
        // $params[] = $end;
        // return $this -> callServer('reports.link_clicks', $params);
    // }
//
    // /*
     // * reports_links($id)
     // *
     // * Dokumentation: http://admin.getanewsletter.com/api/v0.1/reports.links/
     // *
     // */
//
    // function reports_links($id) {
        // $params = Array();
        // $params[] = $id;
        // return $this -> callServer('reports.links', $params);
    // }
//
    // /*
     // * reports_listing($latest)
     // *
     // * Dokumentation: http://admin.getanewsletter.com/api/v0.1/reports.listing
     // *
     // */
//
    // function reports_listing($latest = True) {
        // $params = Array();
        // $params[] = $latest;
        // return $this -> callServer('reports.listing', $params);
    // }
//
    // /*
     // * reports_opens($id)
     // *
     // * Dokumentation: http://admin.getanewsletter.com/api/v0.1/reports.opens/
     // *
     // */
//
    // function reports_opens($id) {
        // $params = Array();
        // $params[] = $id;
        // return $this -> callServer('reports.opens', $params);
    // }
//
    // /*
     // * reports_unsubscribes($id, $filter, $start=NULL, $end=NULL)
     // *
     // * Dokumentation: http://admin.getanewsletter.com/api/v0.1/reports.unsubscribes/
     // *
     // */
//
    // function reports_unsubscribes($id, $start = NULL, $end = NULL) {
        // $params = Array();
        // $params[] = $id;
        // $params[] = $start;
        // $params[] = $end;
        // return $this -> callServer('reports.unsubscribes', $params);
    // }

}
