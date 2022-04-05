<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Lumen\Routing\Controller as BaseController;
use OpenApi\Annotations as OA;
use TheIconic\Tracking\GoogleAnalytics\Analytics;

class Controller extends BaseController
{
    /**
     * @OA\Info(
     *   title="Review API",
     *   version="1.0",
     *   description="This is an open and free to use API for developers to test their client projects.
        All API calls require you to pass in <br> your API Key with the querystring
        <b>?apikey=########-####-####-####-############</b>, <br><br>Please
        register for your own API Key today at https://reviews.simpleapi.dev/register ",
     *
     * )
     */
    protected string $integration_id;
    protected string $app_name;
    protected bool $is_admin = false;

    public function __construct(Request $request)
    {
        $this->integration_id = app()->has('integration_id') ? app()->get('integration_id') : "";
        $this->app_name = config("app.name");

        $analytics = new Analytics(true);
        $analytics
            ->setProtocolVersion('1')
            ->setHitType("pageview")
            ->setTrackingId(env('GOOGLE_ANALYTICS') )
            ->setClientId($this->integration_id ?? 'new')
            ->setDocumentPath($request->path())
            ->setDocumentTitle($request->path());

        $analytics->sendPageview();
    }

}
