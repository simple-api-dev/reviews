<?php

namespace App\Http\Controllers;

use App\Models\Review;
use Illuminate\Http\JsonResponse;
use \Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class ReviewController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/reviews",
     *     summary="Retrieve all reviews",
     *     operationId="review.index",
     *     tags={"Review"},
     *     @OA\Parameter(
     *         name="apikey",
     *         in="query",
     *         description="All api calls require the ?apikey querystring",
     *         required=true,
     *         @OA\Schema(type="string"),
     *      ),
     *      @OA\Parameter(
     *         name="spam",
     *         in="query",
     *         description="?spam=true to get reviews marked as spam",
     *         required=false,
     *         @OA\Schema(type="string", enum={"true", "false", 1,0}),
     *      ),
     *      @OA\Parameter(
     *         name="bad",
     *         in="query",
     *         description="?bad=true to get reviews marked as inappropriate/bad",
     *         required=false,
     *         @OA\Schema(type="string", enum={"true", "false", 1,0}),
     *      ),
     *      @OA\Parameter(
     *         name="helpful",
     *         in="query",
     *         description="?helpful=true to get reviews that are helpful",
     *         required=false,
     *         @OA\Schema(type="string", enum={"true", "false", 1,0}),
     *      ),
     *      @OA\Parameter(
     *         name="tags",
     *         in="query",
     *         description="?tags=tag1,tag2... to get reviews grouped by tag",
     *         required=false,
     *         @OA\Schema(
     *             type="array",
     *             @OA\Items(type="string")
     *          ),
     *          style="simple",
     *          explode=false
     *      ),
     *     @OA\Response(response="200", description="A collection of reviews")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $query = Review::where("integration_id", $this->integration_id)
                        ->orderByDesc('reviewed_at')
                        ->with(['comments' => function($query){
                            return $query->orderByDesc('commented_at');
                        }]);

        //spam filter
        if (strtolower($request->get('spam')) == "true" || strtolower($request->get('spam')) == "false") {
            $query->where("spam", strtolower($request->get('spam')) == "true");
        }
        elseif (strtolower($request->get('spam')) == "1" || strtolower($request->get('spam')) == "0")
        {
            $query->where("spam", strtolower($request->get('spam')) == "1");
        }

        //bad filter
        if (strtolower($request->get('bad')) == "true" || strtolower($request->get('bad')) == "false") {
            $query->where("bad", strtolower($request->get('bad')) == "true");
        }
        elseif (strtolower($request->get('bad')) == "1" || strtolower($request->get('bad')) == "0") {
            $query->where("bad", strtolower($request->get('bad')) == "1");
        }

        $reviews = $query->get();

        //helpful/non-helpful filter
        if(strtolower($request->get('helpful')) == "true" || strtolower($request->get('helpful')) == "false") {
            $reviews = $reviews->filter(function($item) use ($request)
            {
                if (strtolower($request->get('helpful')) == "true" && $item->isHelpful())
                    return $item;
                elseif (strtolower($request->get('helpful')) == "false" && !$item->isHelpful())
                    return $item;
            });
        }
        elseif(strtolower($request->get('helpful')) == "1" || strtolower($request->get('helpful')) == "0") {
            $reviews = $reviews->filter(function($item) use ($request)
            {
                if (strtolower($request->get('helpful')) == "1" && $item->isHelpful())
                    return $item;
                elseif (strtolower($request->get('helpful')) == "0" && !$item->isHelpful())
                    return $item;
            });
        }

        //filter for tags
        if ($request->get('tags'))
        {
            $tags = explode(',', $request->get('tags'));

            $reviews = $reviews->filter(function($item) use ($tags)
            {
                //all tags have to be present in item->tags
                if (count(array_diff($tags, explode(',', $item->tags))) == 0)
                    return $item;
            })->values();
        }

        //compile results with metadata
        $results = ['reviews' => $reviews];
        $results['avg_rating'] = count($reviews) > 0? $reviews->sum(fn($item) => $item->rating) / count($reviews) : null;
        $results['most_helpful'] = $reviews->where('helpful_score', $reviews->max(fn($review) => $review->helpful_score))->first();
        $results['least_helpful'] = $reviews->where('helpful_score', $reviews->min(fn($review) => $review->helpful_score))->first();
        $results['reviews_count'] = count($reviews);

        return response()->json($results, 200);
    }

    /**
     * @OA\Get(
     *     path="/api/authors/{author_slug}",
     *     summary="Retrieve all reviews by author",
     *     operationId="review.indexByUser",
     *     tags={"Review"},
     *     @OA\Parameter(
     *         name="apikey",
     *         in="query",
     *         description="All api calls require the ?apikey querystring",
     *         required=true,
     *         @OA\Schema(type="string"),
     *      ),
     *      @OA\Parameter(
     *         name="author_slug",
     *         in="path",
     *         description="The slug of the author",
     *         required=true,
     *         @OA\Schema(type="string"),
     *      ),
     *      @OA\Parameter(
     *         name="spam",
     *         in="query",
     *         description="?spam=true to get reviews marked as spam",
     *         required=false,
     *         @OA\Schema(type="string", enum={"true", "false", 1,0}),
     *      ),
     *      @OA\Parameter(
     *         name="bad",
     *         in="query",
     *         description="?bad=true to get reviews marked as inappropriate/bad",
     *         required=false,
     *         @OA\Schema(type="string", enum={"true", "false", 1,0}),
     *      ),
     *      @OA\Parameter(
     *         name="helpful",
     *         in="query",
     *         description="?helpful=true to get reviews that are helpful",
     *         required=false,
     *         @OA\Schema(type="string", enum={"true", "false", 1,0}),
     *      ),
     *      @OA\Parameter(
     *         name="tags",
     *         in="query",
     *         description="?tags=tag1,tag2... to get reviews grouped by tag",
     *         required=false,
     *         @OA\Schema(
     *             type="array",
     *             @OA\Items(type="string")
     *          ),
     *          style="simple",
     *          explode=false
     *      ),
     *     @OA\Response(response="200", description="A collection of reviews")
     * )
     */
    public function indexByUser(Request $request, string $author_slug): JsonResponse
    {
        $query = Review::where("integration_id", $this->integration_id)
                        ->with(['comments' => function($query){
                            return $query->orderByDesc('commented_at');
                        }])
                        ->orderByDesc('reviewed_at')
                        ->where('author_slug', $author_slug);

        //spam filter
        if (strtolower($request->get('spam')) == "true" || strtolower($request->get('spam')) == "false") {
            $query->where("spam", strtolower($request->get('spam')) == "true");
        }
        elseif (strtolower($request->get('spam')) == "1" || strtolower($request->get('spam')) == "0")
        {
            $query->where("spam", strtolower($request->get('spam')) == "1");
        }

        //bad filter
        if (strtolower($request->get('bad')) == "true" || strtolower($request->get('bad')) == "false") {
            $query->where("bad", strtolower($request->get('bad')) == "true");
        }
        elseif (strtolower($request->get('bad')) == "1" || strtolower($request->get('bad')) == "0") {
            $query->where("bad", strtolower($request->get('bad')) == "1");
        }

        $reviews = $query->get();

        //helpful/non-helpful filter
        if(strtolower($request->get('helpful')) == "true" || strtolower($request->get('helpful')) == "false") {
            $reviews = $reviews->filter(function($item) use ($request)
            {
                if (strtolower($request->get('helpful')) == "true" && $item->isHelpful())
                    return $item;
                elseif (strtolower($request->get('helpful')) == "false" && !$item->isHelpful())
                    return $item;
            });
        }
        elseif(strtolower($request->get('helpful')) == "1" || strtolower($request->get('helpful')) == "0") {
            $reviews = $reviews->filter(function($item) use ($request)
            {
                if (strtolower($request->get('helpful')) == "1" && $item->isHelpful())
                    return $item;
                elseif (strtolower($request->get('helpful')) == "0" && !$item->isHelpful())
                    return $item;
            });
        }

        //filter for tags
        if ($request->get('tags'))
        {
            $tags = explode(',', $request->get('tags'));

            $reviews = $reviews->filter(function($item) use ($tags)
            {
                //all tags have to be present in item->tags
                if (count(array_diff($tags, explode(',', $item->tags))) == 0)
                    return $item;
            })->values();
        }

        //compile results with metadata
        $results = ['reviews' => $reviews];
        $results['avg_rating'] = count($reviews) > 0? $reviews->sum(fn($item) => $item->rating) / count($reviews) : null;
        $results['most_helpful'] = $reviews->where('helpful_score', $reviews->max(fn($review) => $review->helpful_score))->first();
        $results['least_helpful'] = $reviews->where('helpful_score', $reviews->min(fn($review) => $review->helpful_score))->first();
        $results['reviews_count'] = count($reviews);

        return response()->json($results, 200);
    }

    /**
     * @OA\Get(
     *     path="/api/reviews/{id}",
     *     summary="Retrieve specific review",
     *     operationId="review.get",
     *     tags={"Review"},
     *     @OA\Parameter(
     *         name="apikey",
     *         in="query",
     *         description="All api calls require the ?apikey querystring",
     *         required=true,
     *         @OA\Schema(type="string"),
     *      ),
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="The ID of the review to retrieve",
     *         required=true,
     *         @OA\Schema(type="integer"),
     *      ),
     *     @OA\Response(response="200", description="A review")
     * )
     */
    public function get(Request $request, $id): JsonResponse
    {
        $query = Review::where("integration_id", $this->integration_id)
                ->with(['comments' => function($query){
                    return $query->orderByDesc('commented_at');
                }])
                ->where('id', $id);

        $result = $query->first();

        if($result)
            return response()->json($result);
        else
            return response()->json("Review {$id} not found", 404);
    }

    /**
     * @OA\Post(
     * path="/api/reviews",
     * summary="Create a new review.  There's a limit of 100 reviews per developer account.",
     * operationId="review.post",
     * tags={"Review"},
     *     @OA\Parameter(
     *         name="apikey",
     *         in="query",
     *         description="All api calls require the ?apikey querystring",
     *         required=true,
     *         @OA\Schema(type="string"),
     *      ),
     * @OA\RequestBody(
     *    required=true,
     *    description="Submit review field values",
     *    @OA\JsonContent(
     *     @OA\Property(property="rating", type="number"),
     *     @OA\Property(property="title", type="string"),
     *     @OA\Property(property="body", type="string"),
     *     @OA\Property(property="bad", type="boolean"),
     *     @OA\Property(property="helpful_counter", type="integer"),
     *     @OA\Property(property="unhelpful_counter", type="integer"),
     *     @OA\Property(property="author", type="string"),
     *     @OA\Property(property="author_email", type="string"),
     *     @OA\Property(property="author_slug", type="string"),
     *     @OA\Property(property="meta", type="object"),
     *     @OA\Property(property="tags", type="string"),
     *     @OA\Property(property="reviewed_at", type="string"),
     *    ),
     * ),
     * @OA\Response(
     *    response=200,
     *    description="Review created successfully",
     *    @OA\JsonContent(
     *     @OA\Property(property="rating", type="number"),
     *     @OA\Property(property="title", type="string"),
     *     @OA\Property(property="body", type="string"),
     *     @OA\Property(property="bad", type="boolean"),
     *     @OA\Property(property="helpful_counter", type="integer"),
     *     @OA\Property(property="unhelpful_counter", type="integer"),
     *     @OA\Property(property="author", type="string"),
     *     @OA\Property(property="author_email", type="string"),
     *     @OA\Property(property="author_slug", type="string"),
     *     @OA\Property(property="meta", type="object"),
     *     @OA\Property(property="tags", type="string"),
     *     @OA\Property(property="reviewed_at", type="string"),
     *     )
     *   )
     * )
     */
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function post(Request $request) : JsonResponse{

        $this->validate($request, [
            'rating' => 'sometimes|numeric|between:0.00,1.00',
            'title' => 'sometimes|string|max:255',
            'body' => 'sometimes|string|max:1024',
            'bad' => 'sometimes|boolean',
            'spam' => 'sometimes|boolean',
            'helpful_counter' => 'sometimes|numeric',
            'unhelpful_counter' => 'sometimes|numeric',
            'related_slug' => 'sometimes|string|max:255',
            'author' => 'sometimes|string|max:255',
            'author_email' => 'sometimes|string|max:255',
            'author_slug' => 'sometimes|string|max:255',
            'reviewed_at' => 'sometimes|date|max:255',
            'meta' => 'sometimes|array',
            'tags' => 'sometimes|string'
        ]);

        // Limit the total reviews per account
        $limit_reached = Review::where("integration_id", $this->integration_id)->count() > 100;
        $message = "Maximum number of reviews (100) per developer account has been reached.";
        
        if($limit_reached){
            return response()->json($message, 403);
        }

        $review = Review::make($request->all());
        $review->integration_id = $this->integration_id;

        //validate meta field
        if($request->meta && $this->json_validator($request->meta)){
            $size = mb_strlen(json_encode($request->meta, JSON_NUMERIC_CHECK), '8bit');
            if($size > 512){
                return response()->json("Meta data field cannot exceed 512 bytes", 403);
            }
            $review->meta = json_encode($request->meta);
        }
        else
            $review->meta = null;

        $review->save();

        return response()->json($review);
    }

    private function json_validator($data=NULL) : bool {

        if (!empty($data)) {

            @json_encode($data);

            return (json_last_error() === JSON_ERROR_NONE);

        }
        return false;
    }
    
    /**
     * @OA\Put(
     * path="/api/reviews/{id}",
     * summary="Update a review",
     * operationId="review.put",
     * tags={"Review"},
     *     @OA\Parameter(
     *         name="apikey",
     *         in="query",
     *         description="All api calls require the ?apikey querystring",
     *         required=true,
     *         @OA\Schema(type="string"),
     *      ),
     *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of review to update",
     *         required=true,
     *         @OA\Schema(type="integer"),
     *      ),
     * @OA\RequestBody(
     *    required=true,
     *    description="Submit review fields to update review with",
     *    @OA\JsonContent(
     *     @OA\Property(property="rating", type="number"),
     *     @OA\Property(property="title", type="string"),
     *     @OA\Property(property="body", type="string"),
     *     @OA\Property(property="bad", type="boolean"),
     *     @OA\Property(property="helpful_counter", type="integer"),
     *     @OA\Property(property="unhelpful_counter", type="integer"),
     *     @OA\Property(property="author", type="string"),
     *     @OA\Property(property="author_email", type="string"),
     *     @OA\Property(property="author_slug", type="string"),
     *     @OA\Property(property="meta", type="object"),
     *     @OA\Property(property="tags", type="string"),
     *     @OA\Property(property="reviewed_at", type="string"),
     *     )
     * ),
     * @OA\Response(
     *    response=200,
     *    description="Review updated successfully",
     *    @OA\JsonContent(
     *     @OA\Property(property="rating", type="number"),
     *     @OA\Property(property="title", type="string"),
     *     @OA\Property(property="body", type="string"),
     *     @OA\Property(property="bad", type="boolean"),
     *     @OA\Property(property="helpful_counter", type="integer"),
     *     @OA\Property(property="unhelpful_counter", type="integer"),
     *     @OA\Property(property="author", type="string"),
     *     @OA\Property(property="author_email", type="string"),
     *     @OA\Property(property="author_slug", type="string"),
     *     @OA\Property(property="meta", type="object"),
     *     @OA\Property(property="tags", type="string"),
     *     @OA\Property(property="reviewed_at", type="string"),
     *    )
     *  )
     * )
     */
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function put(Request $request, $id): JsonResponse
    {
        $review = Review::where('id', $id)->first();
        if($review && $review->integration_id == $this->integration_id) 
        {
            $validated = $this->validate($request, [
                'rating' => 'sometimes|numeric|between:0.00,1.00',
                'title' => 'sometimes|string|max:255',
                'body' => 'sometimes|string|max:1024',
                'bad' => 'sometimes|boolean',
                'spam' => 'sometimes|boolean',
                'helpful_counter' => 'sometimes|numeric',
                'unhelpful_counter' => 'sometimes|numeric',
                'related_slug' => 'sometimes|string|max:255',
                'author' => 'sometimes|string|max:255',
                'author_email' => 'sometimes|string|max:255',
                'author_slug' => 'sometimes|string|max:255',
                'reviewed_at' => 'sometimes|date|max:255',
                'meta' => 'sometimes|array',
                'tags' => 'sometimes|string'
            ]);

            //validate meta field
            if(isset($validated['meta']) && $this->json_validator($validated['meta'])){
                $size = mb_strlen(json_encode($validated['meta'], JSON_NUMERIC_CHECK), '8bit');
                if($size > 512){
                    return response()->json("Meta data field cannot exceed 512 bytes", 403);
                }
                $validated['meta'] = json_encode($validated['meta']);
            }
            else
                $validated['meta'] = null;

            $review->update($validated);

            return response()->json($review, 200);
        }
        else {
            return response()->json("Review {$id} not found", 404);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/reviews/{id}",
     *     summary="Deletes the review.",
     *     operationId="review.remove",
     *     tags={"Review"},
     *     @OA\Parameter(
     *         name="apikey",
     *         in="query",
     *         description="All api calls require the ?apikey querystring",
     *         required=true,
     *         @OA\Schema(type="string"),
     *      ),
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of review to delete",
     *         required=true,
     *         @OA\Schema(type="integer"),
     *      ),
     *     @OA\Response(response="200", description="Returns a message")
     * )
     */
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function remove($id) : JsonResponse
    {
        $review = Review::where('id', $id)->first();

        if ($review && $review->integration_id == $this->integration_id) {
            $review->delete();
            return response()->json("Review {$id} deleted", 200);
        }

        return response()->json("Review {$id} not found", 404);
    }
}
