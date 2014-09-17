IMPORTANT! This a demo PHP interface class is intended to be used as a drop-in
replacement of the older version to ease the migration to the API version 3.
We strongly recommend that you directly implement our new REST API with a PHP
REST client of your choice.

You can find the documentation for the API at https://api.getanewsletter.com/

The REST client used in this demo interface is Httpful. You can learn more
about it and get the latest version from http://phphttpclient.com/

In order to update your old version of the php interface you will have to
paste (and overwrite) the following files in your project:

GAPI.class.php      The class used to connect to Get a Newsletter.
lib/httpful.phar    The library used as REST client.

There are several changes in the new API that are not backwards compatible
with the older version of the class:

*   The $start and $end arguments of some of the methods will be ignored in
    this version of the interface and will not work as expected. This
    functionality may be subjected to a change in future versions.

*   The method subscriptions_listing() will no longer list the fields
    'api-key', 'active' and 'confirmed' as they are not supported in the new
    version of the API.

*   The method reports_listing() will not list the fields 'unsubscribe',
    'unique_opens', 'bounces' and 'link_click'.

*   The method reports_bounces() will ignore the $filter argument. This
    functionality will be implemented in a future version of the class.

*   The method reports_link_clicks() is obsolete. Use the new method
    reports_clicks_per_link() instead.

Send us a message at support@getanewsletter.com if you need any assistance.
