<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en" >
    <head>
        <meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
        <title>Get a Newsletter API Demo</title>

    </head>
    <body>

        <?php
        require_once("./GAPI.class.php");

/*
 * Exempelfil för att visa hur APIt för Get a Newsletter kan användas.
 *
 */

/*
 * Används för att demonstrera användningen av APIt.
 */

        $username = 'svetoslav';     # Ditt användarnamn på Get a Newsletter

        $password = '123123';     # Ditt lösenord på Get a Newsletter


	$email    = 'svetoslav.mishkov@gmail.com';     # Fyll i e-postadress.

        $list_id  = 'L6EvjhPRajh';     # Fyll i list_id, fås via listningen av nyhetsbrev i funktionen nedan.

        if ($username && $password) {
            $gan = new GAPI($username, $password);
        } else {
            print "<h1>Du måste byta ut lösenord och användarnamn i filen innan du kan testa.</h1>";
            print "</body></html>";
            die();
        }

/*
 * Test av inloggning
 */

        echo "<h1>Testning av inloggning</h1>";
        if ($gan->check_login()) {
            print "<p>Inloggningen lyckades</p>";
        } else {
            print "Errors: ";
            print $gan->show_errors();
        }
/*
 * Lista samtliga nyhetsbrev.
*/
        echo "<h1>Listar samtliga nyhetsbrev</h1>";
        if ($gan->newsletters_show()) {
            print "<pre>";
            print_r( $gan->result );
            print "</pre>";
        } else {
            print $gan->show_errors();
        }

        if ($email && $list_id) {

/*
 *  Listar samtliga attribut
 */
            echo "<h1>Listar samtliga attribut för kontot</h1>";
            if ($gan->attribute_listing()) {
                print "<pre>";
                print_r( $gan->result );
                print "</pre>";
            } else {
                print $gan->show_errors();
            }

/*
 *  Lägger till ett attribut
 */
            echo "<h1>Lägger till attributet test_stad och test_land.</h1>";
            if ($gan->attribute_create('test_stad')) {
                print "<p>Attribut test_stad tillagt</p>";
            } else {
                print $gan->show_errors();
            }

            if ($gan->attribute_create('test_land')) {
                print "<p>Attribut test_land tillagt</p>";
            } else {
                print $gan->show_errors();
            }

 /*
   * Visa en specifik kontakt från ditt Get a Newsletter kontot.
   */
            echo "<h1>Visar information om en specifik kontakt</h1>";
            if ($gan->contact_show($email,True)) {
                print "<pre>";
                print_r( $gan->result );
                print "</pre>";
            } else {
                print $gan->show_errors();
            }

/*
  * Lägger till eller uppdaterar en kontakt.
  */
            echo "<h1>Lägger till en kontakt</h1>";
            $attributes = Array("test_stad" => "Oslo","test_land" => "Norge");

            if
                ($gan->contact_create($email,'Anna' ,'Andersson',$attributes)) {
                print "<p>Kontakten tillagd</p>";
            } else {
                print $gan->show_errors();
            }

/*
   * Visa en specifik kontakt från ditt Get a Newsletter kontot.
   */
            echo "<h1>Visar information om en specifik kontakt</h1>";
            if ($gan->contact_show($email,True)) {
                print "<pre>";
                print_r( $gan->result );
                print "</pre>";
            } else {
                print $gan->show_errors();
            }

/*
  * Lägger till en kontakt (om den ej redan existerar)  och en prenumeration på nyhetsbrev.
  */
            echo "<h1>Lägger till en prenumeration på nyhetsbrev.</h1>";
            $attributes = Array("test_stad" => "Karlskrona" ,"test_land" => "Sverige");

            if ($gan->subscription_add($email, $list_id,'Förnamn','Efternamn',False,NULL,True,$attributes)) {
                print "<p>Prenumerationen tillagd</p>";
            } else {
                print $gan->show_errors();
            }
 /*
   * Visa en specifik kontakt från ditt Get a Newsletter kontot.
   */
            echo "<h1>Visar information om en specifik kontakt</h1>";
            if ($gan->contact_show($email,True)) {
                print "<pre>";
                print_r( $gan->result );
                print "</pre>";
            } else {
                print $gan->show_errors();
            }

  /*
   * Listar prenumerationerna för aktuellt nyhetsbrev
   */

            echo "<h1>Listar 100 första prenumerationerna</h1>";
            if ($gan->subscriptions_listing($list_id, 0,100)) {
                print "<pre>";
                print_r( $gan->result );
                print "</pre>";
            } else {
                print $gan->show_errors();
            }
 /*
   * Tar bort en prenumeration på nyhetsbrev.
   */

            echo "<h1>Tar bort en prenumeration på nyhetsbrev.</h1>";
            if ($gan->subscription_delete($email, $list_id)) {
                print "<p>Prenumerationen borttagen</p>";
            } else {
                print $gan->show_errors();
            }
 /*
  * Raderar en kontakt från ditt Get a Newsletter kontot.
   */

            echo "<h1>Raderar en kontakt</h1>";
            if ($gan->contact_delete($email)) {
                print "<p>Kontakten raderad.</p>";
            } else {
                print $gan->show_errors();
            }
           echo "<h1>Tar bort attributet test_stad och test_land.</h1>";
           if ($gan->attribute_delete('test_stad')) {
              print "<p>Attribut test_stad borttaget</p>";
           } else {
                print $gan->show_errors();
           }
           if ($gan->attribute_delete('test_land')) {
             print "<p>Attribut test_land borttaget</p>";
           } else {
             print $gan->show_errors();
           }

 /*
   * Visar senaste rapporten på ditt Get a Newsletter konto
   */

            echo "<h1>Visar senaste rapporten på ditt Get a Newsletter konto.</h1>";
            if ($gan->reports_listing()) {
                print "<pre>";
                print_r($gan->result);
                print "</pre>";
            } else {
                print $gan->show_errors();
            }
            print "<br/><br/>Tack för att du testade vårt exempel. Har
            du några frågor så skicka ett mail till oss på <a href='mailto:support@getanewsletter.com'>support@getanewsletter.com</a><br/><br/>";

      } else {
            print "<h1>Du måste fylla i e-postadress och list_id  för att kunna testa resterande funktioner.</h1>";
            print "<p>list_id kan du se i listan över samtliga nyhetsbrev. Kopiera det från en av dem.</p>";
        }
      ?>

    </body>
</html>
