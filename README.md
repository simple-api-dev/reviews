## Lumen PHP Reviews API using Swagger, Google Analytics, MailGun, ReCaptcha

[Install Swagger Lume with OpenAPi 3 support](https://github.com/DarkaOnLine/SwaggerLume)  
[How to write swagger documentation in php](https://ivankolodiy.medium.com/how-to-write-swagger-documentation-for-laravel-api-tips-examples-5510fb392a94)
[Getting started with Swagger in PHP](https://zircote.github.io/swagger-php/Getting-started.html)  
[Adding Swagger and Passport](https://codingdriver.com/laravel-api-documentation-with-swagger-open-api-and-passport.html)
[Basic CRUD Rest API with Lumen (ignore the oauth stuff)](https://auth0.com/blog/developing-restful-apis-with-lumen/)

### Basic Project Install

- composer create-project --prefer-dist laravel/lumen blog
- make sure that the pdo_mysql extension is enabled in the php.ini file
- create a case-insensitive utf8mb4 database `CREATE SCHEMA `todos` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ;`
- we can use the new php 8.1 annotation methods
- we are going to use php 8.1.1 so make sure to update composer
- there is no node as this has no UI
- change which version php cli uses in linux `sudo update-alternatives --config php`
- when install mysql server on linux you need at least 1gb of memory, 512mb fails

### Setting up Swagger
- we should install Swagger to give us documentation
- install this library `composer require darkaonline/swagger-lume`
- in app/Http/Controllers/Controller setup the basic swagger metadata tags:
```
    /**
     * @OA\Info(
     *   title="Todo API",
     *   version="1.0",
     *   description="This is an open and free to use API for developers to test their client projects",
     *   @OA\Contact(
     *     email="support@todoapi.net",
     *     name="Developer"
     *   )
     * )
     */
```
- in the above code you may also want to add support for bearer token authentication within the swagger UI
- so add this to the above code
```
* @OA\SecurityScheme(
     *     type="http",
     *     description="Login with email and password to get the authentication token",
     *     name="Token based Based",
     *     in="header",
     *     scheme="bearer",
     *     bearerFormat="JWT",
     *     securityScheme="apiAuth",
     * )
```
- and then any call that uses this security should have this added under description:
```
security={{"apiAuth":{}}},
```
- also you will need to define at least one path, otherwise generator fails, i just did this in `Controller.php`
```
    /**
     * @OA\Get(
     *     path="/",
     *     @OA\Response(response="200", description="An example resource")
     * )
     */
    public function index(){
        $data = [];
        return $data;
    }
```
- excellent resource: [Install Swagger Lume with OpenAPi 3 support](https://medium.com/remote-worker-indonesia/create-api-covid-19-data-using-laravel-lumen-and-swagger-as-documentation-24edd56ea0d0)
- `php artisan swagger-lume:generate` whenever you want to generate new documentation
- seems to work better if you host server like this `php -S localhost:8000 public/index.php`
- good site to import your generated `\storage\api-docs\api-docs.json` is `https://editor.swagger.io/`
- the file that hosts the swagger documentation is found here `resources\views\vendor\swagger-lume\index.blade.php`
- you can edit the blade file to hide the explore bar at the top, convert to dark mode, etc.
- to deploy on server you will need to run the following command from the project root  
`cp -r vendor/swagger-api/swagger-ui/dist public/swagger-ui-assets`
- the above isn't enough, not sure but maybe has to do with fact that storage isn't linked
- to have proper docs generate that pass all the tests and look ok, you will need `operationId="review.index"` for each request, otherwise some UI's will just put their own GUID in that place, which looks weird.


### Setting up for CORS

- add a middleware to intercept all calls and return proper headers and register it in bootstrap\app.php
- add an options request provider that allows preflight calls to get 200 ok and register it in bootstrap.php
- grab sample files here [Gist](https://gist.github.com/danharper/06d2386f0b826b669552)
- test it using this website: [Cors Tester](https://myxml.in/cors-tester.html)
- this ended up being more trouble than initially anticipated.  I tried using the `CatchAllOptionsRequestsProvider.php` as mentioned in the above GIST but it just never fired.
- see current `CorsMiddleware.php` for final working implementation.

### Setting up mailgun
*Should note that accidentally stored mailgun api secret in code and committed, don't do this, they disabled the account 
and it was a nightmare getting things enabled again. My fault tho.  I switched to [Mailtrap](https://mailtrap.io) for testing instead and switched back to mailgun once we went live.*  
  

- installed illuminate/mail `composer require illuminate/mail "8.*"`
- installed guzzle `composer require guzzlehttp/guzzle`
- created config/services.php
- created config/mail.php
- added these four lines to bootstrap/app.php
``` 
    $app->register(App\Providers\AppServiceProvider::class);
    $app->register(Illuminate\Mail\MailServiceProvider::class);

    $app->configure('services');
    $app->configure('mail');
```
- make sure `$app->withFacades();` is also uncommented
- created basic mail template views/mail.blade.php
- grabbed mailgun api key and domain from the site
- updated .env to have those values
```
MAIL_DRIVER=mailgun
MAILGUN_DOMAIN=
MAILGUN_SECRET=
MAIL_FROM_ADDRESS=
MAIL_FROM_NAME=
```
- had to configure the cacerts for php8 ini file
- downloaded file here [CACert.pem](https://curl.se/ca/cacert.pem)
- copied it to php folder
- edited this line in the php.ini `curl.cainfo = "c:\tools\php81\cacert.pem"`
- good reference here [Lumen and Mailgun Setup](https://stackoverflow.com/questions/47124070/easiest-way-to-send-emails-with-lumen-5-4-and-mailgun)
- might be simpler to use curl [Send Mail in Laravel with Curl](https://www.pakainfo.com/laravel-send-mailgun-mail-using-php-curl/)

### Backend google analytics
```
<!-- Global site tag (gtag.js) - Google Analytics -->
<script async src="https://www.googletagmanager.com/gtag/js?id=XX-XXXXXX-XX"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'UA-XXXXXX-XX');
</script>
```
- to build your query string to hit google analytics with use [Hit Builder](https://ga-dev-tools.web.app/hit-builder/)
- you can watch in real time or you can go to reporting but it always defaults to end of previous day so change that manually
- documentation on what you can pass to GA [Google Analytics Parameters](https://developers.google.com/analytics/devguides/collection/protocol/v1/parameters)
- let me break down the parameters that are important to us here:
- v=1&t=pageview&tid=UA-XXXXXXX-XX&cid=8&dp=%2Fapi%2Fintegrations
  - v = version, required, default to 1
  - t = transaction type, required, has to be one of a set of values
  - tid = your GA account id, required
  - cid = client id, optional, going to use integration_id
  - ti = transaction id, optional, could pass in todo id or something
  - dp = display page, optional, going to pass the api path /api/integrations
- to get this working we need a snippet of code, install this library using composer
`composer require theiconic/php-ga-measurement-protocol --with-all-dependencies`
- then add this code to your Controller constructor so it runs every time (fun fact you can pass Request $request into the constructor)
```
 $analytics = new Analytics(true);
        $analytics
            ->setProtocolVersion('1')
            ->setHitType("pageview")
            ->setTrackingId(env('GOOGLE_ANALYTICS') )
            ->setClientId($this->integration_id ?? 'new')
            ->setDocumentPath($request->path())
            ->setDocumentTitle($request->path());

        $analytics->sendPageview();
```
- test it like this in a browser
[https://google-analytics.com/debug/collect?v=1&t=pageview&tid=UA-1625181-15&cid=15&ci=100&dp=api/testga](https://google-analytics.com/debug/collect?v=1&t=pageview&tid=UA-1625181-15&cid=15&ci=100&dp=api/testga)
- good reference site [Server Side Google Analytics](https://stackify.dev/239106-send-event-to-google-analytics-using-api-server-sided)

### Timezone and datetime
- there are 3 aspects to timestamps and timezones: php (storage), mysql server (storage), and mysql workbench (display)
- php Date function looks in php.ini `If none of the above succeed, date_default_timezone_get() will return a default timezone of UTC.`
- to verify timezone being used by php `dd(date_default_timezone_get());`
- to get laravel to use the php settings use the date function `date("Y-m-d H:i:s")`
- using now() in mysql will use the my.cnf file setting `default-time-zone = "+00:00"`
- or you can use the following commands to set and verify mysql timezone either server (globally) or for your session (in mysql workbench)
```
SET GLOBAL time_zone = '+00:00'; //'SYSTEM' is default
SET time_zone = '+00:00'; //'-07:00' is GMT
SELECT @@global.time_zone;
Select @@time_zone;
```
- we ran into a weird issue, both machines values are set to system and our system time is GMT, mine displays values in GMT while his are 
consistently UTC.
- Mysql workbench (when working properly I think) displays timestamp columns as the current configured local time by default, and you don't see timestamp information at all

### Google Recaptcha
- sign up here [https://www.google.com/recaptcha](https://www.google.com/recaptcha)
- manage your captcha keys here - fun fact, you can use same key across subdomains
[Google Recaptcha Verify](https://developers.google.com/recaptcha/docs/verify)
- didn't want to install another library so kept this one simple
```
function checkCaptcha($captcha){
    //verify captcha
    $secretKey = env('NOCAPTCHA_SECRET');
    // post request to server
    $url = 'https://www.google.com/recaptcha/api/siteverify?secret=' . urlencode($secretKey) .  
        '&response=' . urlencode($captcha);
    $response = file_get_contents($url);
    $responseKeys = json_decode($response,true);
    return $responseKeys["success"];
```

### Nginx server stuff
- sudo apt install certbot python3-certbot-nginx
- ufw remember to ufw allow nginx https
- `ufw status numbered` is your friend
- had to add if statement to get nginx to serve php without extension, this is different from v1.10.x

### Rate Limiting
- very easy to setup, although i think that instructions are wrong, don't need to enable auth provider [Guide](https://github.com/rogervila/lumen-rate-limiting)
- i set limit to 20 not 60, should still be sufficient for this type of api



### Tips
- to serve locally ``` php -S localhost:8000 -t ./public```