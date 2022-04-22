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
        $router->get('authors/{author_slug}',  ['uses' => 'ReviewController@indexByUser']);

        $router->get('reviews/{id}',  ['uses' => 'ReviewController@get']);

        $router->post('reviews', ['uses' => 'ReviewController@post']);

        $router->delete('reviews/{id}', ['uses' => 'ReviewController@remove']);

        $router->put('reviews/{id}', ['uses' => 'ReviewController@put']);

        /* Comment routes */
        $router->post('reviews/{id}/comments', ['uses' => 'CommentController@post']);
        $router->put('reviews/{review_id}/comments/{id}', ['uses' => 'CommentController@put']);
        $router->delete('reviews/{review_id}/comments/{id}', ['uses' => 'CommentController@remove']);
});

// This is used to register for a new api key.  That's it.  We will enforce CORS so that
// only requests from the website will actually work
$router->group(['prefix' => env('app_dir'), 'middleware' => OriginCheckMiddleware::class], function () use ($router) {
    $router->post('keys/register', ['uses' => 'IntegrationController@register']);
    $router->post('keys/forgot', ['uses' => 'IntegrationController@forgot']);
    $router->post('keys/deregister', ['uses' => 'IntegrationController@deregister']);
});
