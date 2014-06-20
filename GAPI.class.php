<?php
    ini_set( 'display_errors', 'On' );
    error_reporting( E_ALL );
require_once("./lib/xmlrpc.inc");

/*
 * För mer information om APIt till Get a Newsletter besök
 * http://admin.getanewsletter.com/api/
 *
 * Har du några frågor så skicka ett mejl till oss på
 * support@getanewsletter.com
 *
 */

class GAPI {

  /*
   * Aktuell version av gränssnittet
   */
  var $version = 'v0.1';

  /*
   * Adress som används för uppkoppling mot servern.
   */
  var $address = 'admin.getanewsletter.com';

  /*
   * Port som används för uppkoppling mot servern
   */
  var $port = '80';

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

  function GAPI ($username, $password) {
    $this->xmlrpc = new xmlrpc_client("/api/" . $this->version ."/", $this->address, $this->port);
    $this->username = $username;
    $this->password = $password;
    $this->login();
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

  function show_errors() {
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

  function login() {
    if ($this->callServer('nonces.challenge')) {
      $v = $this->result;
      $encrypted_password = sha1($v["salt"] . $this->password);
      $this->encrypted_password = md5( $encrypted_password . $v["nonce"] );
      return true;
    } else {
      return false;
    }
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
  function check_login() {
    return $this->callServer('nonces.verify');
  }

  /*
   * Funktion för att anropa XML-RPC servern, ska aldrig
   * behöva anropas direkt
   *
   * Returvärden
   * =========
   * Sant/Falskt
   *
   * Eventuella fel finns i $errorCode
   * och $errorMessage
   *
   */

  function callServer ($method, $arg=NULL) {

    $xmlrpc_defencoding = "UTF8";
    $params = Array();
    $params[] = $this->username;

    if (isset($this->encrypted_password) && ($method != 'nonces.challenge'))
      $params[] = $this->encrypted_password;

    if(isset($arg))
       $params = array_merge($params, $arg);

    $new_params = Array();

    if (isset($params)) {
      foreach ($params as $key => $value) {
	if(is_array($value))  {
	  $new_array = Array();
	  foreach ($value as $key_arr => $value_arr) {
	    $new_array[$key_arr] = new xmlrpcval($value_arr, "string");
	  }
	  $new_params[] = new xmlrpcval($new_array, "struct");
	} else {
	  $new_params[] = new xmlrpcval($value, "string");
	}
      }
    }

    $m = new xmlrpcmsg($method, $new_params);
    $r = $this->xmlrpc->send($m);

    if (!$r->faultCode()) {
      $this->result = php_xmlrpc_decode($r->value());
      return true;
    } else {
      $this->errorCode = $r->faultCode();
      $this->errorMessage = $r->faultString();
      return false;
    }
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
  function contact_create($email, $first_name=NULL, $last_name=NULL, $attributes=Array(), $mode=1) {
    $params = Array();
    $params[] = $email;
    $params[] = $first_name;
    $params[] = $last_name;
    $params[] = $attributes;
    $params[] = $mode;
    return $this->callServer('contacts.create', $params);
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
  function subscription_add($email, $list_id, $first_name=NULL, $last_name=NULL, $confirmation=False, $api_key=NULL, $autoresponder=True, $attributes=Array()) {
    $params = Array();
    $params[] = $email;
    $params[] = $list_id;
    $params[] = $first_name;
    $params[] = $last_name;
    $params[] = $confirmation;
    $params[] = $api_key;
    $params[] = $autoresponder;
    $params[] = $attributes;
    return $this->callServer('subscriptions.add', $params);
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
  function contact_delete($email) {
    $params = Array();
    $params[] = $email;
    return $this->callServer('contacts.delete', $params);
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
  function contact_show($email, $show_attributes=NULL) {
    $params = Array();
    $params[] = $email;
    $params[] = $show_attributes;
    return $this->callServer('contacts.show', $params);
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
  function subscription_delete($email, $list_id) {
    $params = Array();
    $params[] = $email;
    $params[] = $list_id;
    return $this->callServer('subscriptions.delete', $params);
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
  function newsletters_show() {
    return $this->callServer('newsletter.listing');
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

  function subscriptions_listing($list_id, $start=NULL, $end=NULL) {
    $params = Array();
    $params[] = $list_id;
    $params[] = $start;
    $params[] = $end;

    return $this->callServer('subscriptions.listing', $params);
  }

  function attribute_create($name) {
    $params = Array();
    $params[] = $name;

    return $this->callServer('attributes.create', $params);
  }

  function attribute_delete($name) {
    $params = Array();
    $params[] = $name;

    return $this->callServer('attributes.delete', $params);
  }

  function attribute_listing() {
    $params = Array();
    return $this->callServer('attributes.listing', $params);
  }

  /*
   * reports_bounces($id, $filter, $start=NULL, $end=NULL)
   *
   * Listar prenumerationer för ett nyhetsbrev, max 100 st i taget
   *
   * Argument
   * =========
   * $id        = Id för rapporten du vill visa studsar för
   * $filter    = Används om du vill filtrera resultatet. Ange "hard" för att
   *              endast returnera adresser med hård studs (permanent fel) och
   *              "soft" för att endast returnera adresser med temporärt fel. (Valbart)
   * $start	= Post som listningen ska börja på
   * $end 	= Post som listningen ska sluta på
   *
   * Returvärden
   * =========
   * Returnerar en array med följande information för varje rapport:
   *
   * email       E-post som har studsat
   *
   * status      Status för aktuell studs, ges som en sifferkombination med följande
   *             format X.X.X. Om statusen börjar på en 5:a är det en hård studs
   *             (felet är permanent) och om den börjar på 4:a är det en mjuk studs
   *             (temporärt fel). Vill du ha mer information om statuskodernas
   *             betydelse så kolla i manualen.
   *
   * Eventuella fel finns i $errorCode och $errorMessage
   *
   */

  function reports_bounces($id, $filter=NULL, $start=NULL, $end=NULL) {
    $params = Array();
    $params[] = $id;
    $params[] = $filter;
    $params[] = $start;
    $params[] = $end;
    return $this->callServer('reports.bounces', $params);
  }

  /*
   * reports_link_clicks($id, $filter, $start=NULL, $end=NULL)
   *
   * Dokumentation: http://admin.getanewsletter.com/api/v0.1/reports.link_clicks/
   *
   */

  function reports_link_clicks($id, $filter=NULL, $start=NULL, $end=NULL) {
    $params = Array();
    $params[] = $id;
    $params[] = $filter;
    $params[] = $start;
    $params[] = $end;
    return $this->callServer('reports.link_clicks', $params);
  }

  /*
   * reports_links($id)
   *
   * Dokumentation: http://admin.getanewsletter.com/api/v0.1/reports.links/
   *
   */

  function reports_links($id) {
    $params = Array();
    $params[] = $id;
    return $this->callServer('reports.links', $params);
  }


  /*
   * reports_listing($latest)
   *
   * Dokumentation: http://admin.getanewsletter.com/api/v0.1/reports.listing
   *
   */

  function reports_listing($latest = True) {
    $params = Array();
    $params[] = $latest;
    return $this->callServer('reports.listing', $params);
  }

  /*
   * reports_opens($id)
   *
   * Dokumentation: http://admin.getanewsletter.com/api/v0.1/reports.opens/
   *
   */

  function reports_opens($id) {
    $params = Array();
    $params[] = $id;
    return $this->callServer('reports.opens', $params);
  }

  /*
   * reports_unsubscribes($id, $filter, $start=NULL, $end=NULL)
   *
   * Dokumentation: http://admin.getanewsletter.com/api/v0.1/reports.unsubscribes/
   *
   */

  function reports_unsubscribes($id, $start=NULL, $end=NULL) {
    $params = Array();
    $params[] = $id;
    $params[] = $start;
    $params[] = $end;
    return $this->callServer('reports.unsubscribes', $params);
  }
}

