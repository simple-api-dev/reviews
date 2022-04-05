<?php

use App\Http\Middleware\ApiKeyAuthentication;
use App\Http\Middleware\Authenticate;
use App\Http\Middleware\OriginCheckMiddleware;
use App\Http\Middleware\SecureHttpMiddleware;
use Laravel\Lumen\Routing\Router;

/** @var Router $router */

$router->group(['prefix' => env('app_dir'),'middleware' => [
    ApiKeyAuthentication::class,
    SecureHttpMiddleware::class,
    Authenticate::class]
    ], function () use ($router) {
        /* Review routes */
        $router->get('reviews',  ['uses' => 'ReviewController@index']);
        $router->get('authors/{author_slug}/reviews',  ['uses' => 'ReviewController@indexByUser']);
        $router->get('related/{related_slug}/reviews',  ['uses' => 'ReviewController@indexByRelated']);

        $router->get('reviews/{slug}',  ['uses' => 'ReviewController@get']);

        $router->post('reviews', ['uses' => 'ReviewController@post']);

        $router->delete('reviews/{slug}', ['uses' => 'ReviewController@remove']);

        $router->put('reviews/{slug}', ['uses' => 'ReviewController@put']);

        /* Comment routes */
        $router->post('reviews/{slug}/comments', ['uses' => 'CommentController@post']);
        $router->put('reviews/{slug}/comments/{id}', ['uses' => 'CommentController@put']);
        $router->delete('reviews/{slug}/comments/{id}', ['uses' => 'CommentController@remove']);
});

// This is used to register for a new api key.  That's it.  We will enforce CORS so that
// only requests from the website will actually work
$router->group(['prefix' => env('app_dir'), 'middleware' => OriginCheckMiddleware::class], function () use ($router) {
    $router->post('keys/register', ['uses' => 'IntegrationController@register']);
    $router->post('keys/forgot', ['uses' => 'IntegrationController@forgot']);
    $router->post('keys/deregister', ['uses' => 'IntegrationController@deregister']);
});

$router->get('/', function () use ($router) {
    //return redirect(getenv('APP_URL') . '/docs');
});
