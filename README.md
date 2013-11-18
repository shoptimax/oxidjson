# OXID|Json #

## What is it? ##
OXID|Json is a JSON CRUD (Create, Read, Update, Delete) interface for the [OXID eShop](http://www.oxid-esales.com)
that comes with a fancy [AngularJS](http://angularjs.org) frontend for playing around with the JSON data. It uses the [Tonic PHP framework](http://www.peej.co.uk/tonic/).

## Installation ##

PHP >= 5.3 required. You need an installed OXID eShop and an admin account (or a user assigned to a special group) for login.
The OXID module also creates two new user groups "OXJSON Full" and "OXJSON Read-only", so you can assign
users to these groups which then can use the JSON interface with full (CRUD with POST, PUT, DELETE) access or read-only access (only GET allowed).

Copy "app/", "oxrest/" and the other files to your shop root directory.

In the root directory of the shop, execute
`INSTALL-TONIC.sh`
after changing the php executable path in it.

This will execute composer.phar with it's configuration file composer.json.

Composer will then create a "vendor" subdirectory, where it downloads and installs TONIC.

The autoloader created in that vendor directory will then be used by the
"/oxrest/oxrest.php" file.

In your OXID shop's main ".htaccess" file, add this:

If you use CGI-PHP, allow auth header forwarding:
```
RewriteCond %{HTTP:Authorization} ^(.*)
RewriteRule ^(.*) - [E=HTTP_AUTHORIZATION:%1]
```

Add the rewrite rule for the JSON interface:
```
RewriteCond %{REQUEST_URI} .*oxrest.*
RewriteCond %{REQUEST_URI} !oxrest\.php$
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule .* oxrest/oxrest.php [L,QSA]
```

just before the line
```
RewriteCond %{REQUEST_URI} oxseo\.php$
```

## Using the JSON interface

The JSON interface can be reached through http://SHOP.URL/oxrest/SERVICE/WITH/PARAMETERS.
Available services and parameters are explained below.

### Authorization

The JSON interface expects a HTTP Authorization header in the following form:

> Authorization: Ox base64_encode(username:password)
which means you have to concatenate username and password with ":", 
Base64-encode that string and prepend the string "Ox ".

For JavaScript requests, you can use CryptJS to encode your username/password combination like this:

var secStr = CryptoJS.enc.Utf8.parse(user.username + ":" + user.password);
var base64 = CryptoJS.enc.Base64.stringify(secStr);

### Available services

The following URL formats are currently supported:

> /list/:class

> /list/:class/:page

> /list/:class/:page/:pageSize

> /list/:class/:page/:pageSize/:orderBy

> /list/:class/:propertyName/:comparator/:propertyValue/:page

> /list/:class/:propertyName/:comparator/:propertyValue/:page/:pageSize

> /list/:class/:propertyName/:comparator/:propertyValue/:page/:pageSize/:orderBy

for lists and
> /oxobject/:class/:oxid
for single objects. All these URIs are callable via GET.

Important: if you use "/list/" URLs, you will get the "raw" data from the database, e.g. from the table "oxarticles".
This is the fast version. If you need pre-computed OXID object data in your lists, e.g. fully loaded oxarticle object data,
you can use "/oxlist/" URLs instead - this will load OXID objects and modifiy them with installed modules etc. So
if you need "calculated" article prices e.g., you should use the "/oxlist/" URL mappings to retrieve data (GET), e.g.


> /oxlist/:class/:page/:pageSize 

Keep in mind that these requests will take more time to load and you are restricted to oxlist based objects.
Of course you can also use your own, custom (oxList based) classes and any database tables.
                        

:class
can be any existing oxList object, e.g. oxuserlist, oxarticleslist ...

:page
per default, ten items are returned per request. If a list contains more than ten items,
paging is supported by this parameter.
Page numbering starts at 0

:propertyName, :propertyValue and :comparator
To filter list queries, two comparators are currently supported:
"eq"   transformes to MySQL ":propertyName = ':propertyValue'"
"like" transformes to MySQL ":propertyName LIKE '%:propertyValue%'"

:propertyName can be any existing MySQL column for the list base object (oxarticlelist => oxarticle...)

:propertyValue can be any arbitrary string

### Example service calls

http://SHOP.URL/oxrest/list/oxuserlist/oxid/eq/f7f62133d58418f398aedde169cde3d4

http://SHOP.URL/oxrest/list/oxarticlelist

http://SHOP.URL/oxrest/list/oxarticlelist/5

http://SHOP.URL/oxrest/list/oxarticlelist/0/15

http://SHOP.URL/oxrest/list/oxarticlelist/oxid/eq/0655a336d18e47df9565641eaa15e2ca/0

http://SHOP.URL/oxrest/list/oxarticlelist/oxartnum/like/1234

http://SHOP.URL/oxrest/list/oxuserlist/oxlname/like/King

http://SHOP.URL/oxrest/list/my_own_list/oxuserid/eq/f83ccf9ef669e33b5caafa4dc1c7fd4f/0

http://SHOP.URL/oxrest/oxlist/oxarticlelist/0/15

http://SHOP.URL/oxrest/oxlist/oxarticlelist/oxtitle/like/Kite/0

http://SHOP.URL/oxrest/oxlist/oxarticlelist/oxtitle/like/Kite/0/10/oxtitle%20ASC


For saving lists and objects, you can use the corresponding PUT methods:

> /list/:class
> /oxobject/:class/:oxid

and send the JSON data as content.

Of course POST and DELETE methods for creating and deleting objects are also supported.
							
