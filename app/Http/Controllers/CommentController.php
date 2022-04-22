<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\Comment;
use Illuminate\Http\JsonResponse;
use \Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class CommentController extends Controller
{
    /**
     * @OA\Post(
     * path="/api/reviews/{id}/comments",
     * summary="Create a comment on a review",
     * operationId="comment.post",
     * tags={"Comment"},
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
     *         description="ID of review to attach comment to",
     *         required=true,
     *         @OA\Schema(type="string"),
     *      ),
     * @OA\RequestBody(
     *    required=true,
     *    description="Submit comment field values",
     *    @OA\JsonContent(
     *          required={"body"},
     *     @OA\Property(property="body", type="string"),
     *     @OA\Property(property="author", type="string"),
     *     @OA\Property(property="author_email", type="string"),
     *     @OA\Property(property="author_slug", type="string"),
     *     @OA\Property(property="meta", type="object"),
     *     @OA\Property(property="commented_at", type="string"),
     *    ),
     * ),
     * @OA\Response(
     *    response=200,
     *    description="Comment created successfully",
     *    @OA\JsonContent(
     *     @OA\Property(property="body", type="string"),
     *     @OA\Property(property="author", type="string"),
     *     @OA\Property(property="author_email", type="string"),
     *     @OA\Property(property="author_slug", type="string"),
     *     @OA\Property(property="meta", type="object"),
     *     @OA\Property(property="commented_at", type="string"),
     *        )
     *     )
     * )
     */
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function post(Request $request, int $id): JsonResponse
    {
        //get and check review
        $review = Review::where('id', $id)->first();
        if (!$review) return response()->json("Review {$id} not found", 404);

        //check if comment exists already
        $comment = $review->comments()->first();
        if ($comment) return response()->json("Review {$id} already has a comment", 403);

        //get and check proper integration
        if ($review->integration_id != $this->integration_id)
            return response()->json("Review {$id} not found", 404);

        $validated = $this->validate($request, [
            'body' => 'required|string|max:1024',
            'author' => 'sometimes|string|max:255',
            'author_email' => 'sometimes|string|max:255',
            'author_slug' => 'sometimes|string|max:255',
            'commented_at' => 'sometimes|date|max:255',
            'meta' => 'sometimes|array'
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
        
        
        //create comment
        $comment = new Comment($validated);
        $comment->integration_id = $this->integration_id;
        $comment->review_id = $review->id;
        $review->comments()->save($comment);
        
        return response()->json($comment, 200);
    }

    /**
     * @OA\Put(
     * path="/api/reviews/{review_id}/comments/{id}",
     * summary="Update a comment on a review",
     * operationId="comment.put",
     * tags={"Comment"},
     *     @OA\Parameter(
     *         name="apikey",
     *         in="query",
     *         description="All api calls require the ?apikey querystring",
     *         required=true,
     *         @OA\Schema(type="string"),
     *      ),
     *      @OA\Parameter(
     *         name="review_id",
     *         in="path",
     *         description="ID of review the comment belongs to",
     *         required=true,
     *         @OA\Schema(type="string"),
     *      ),
     *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Comment ID",
     *         required=true,
     *         @OA\Schema(type="string"),
     *      ),
     * @OA\RequestBody(
     *    required=true,
     *    description="Submit comment field values",
     *    @OA\JsonContent(
     *     @OA\Property(property="body", type="string"),
     *     @OA\Property(property="author", type="string"),
     *     @OA\Property(property="author_email", type="string"),
     *     @OA\Property(property="author_slug", type="string"),
     *     @OA\Property(property="meta", type="object"),
     *     @OA\Property(property="commented_at", type="string"),
     *    ),
     * ),
     * @OA\Response(
     *    response=200,
     *    description="Comment updated successfully",
     *    @OA\JsonContent(
     *     @OA\Property(property="body", type="string"),
     *     @OA\Property(property="author", type="string"),
     *     @OA\Property(property="author_email", type="string"),
     *     @OA\Property(property="author_slug", type="string"),
     *     @OA\Property(property="meta", type="object"),
     *     @OA\Property(property="commented_at", type="string"),
     *        )
     *     )
     * )
     */
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function put(Request $request, int $review_id, int $id): JsonResponse
    {
        $comment = Comment::where('review_id', $review_id)->find($id);
        if ($comment && $comment->integration_id == $this->integration_id)
        {
            $validated = $this->validate($request, [
                'body' => 'sometimes|string|max:1024',
                'author' => 'sometimes|string|max:255',
                'author_email' => 'sometimes|string|max:255',
                'author_slug' => 'sometimes|string|max:255',
                'commented_at' => 'sometimes|date|max:255',
                'meta' => 'sometimes|array',
                'tags' => 'sometimes|array'
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

            //validate tags field
            if(isset($validated['tags']) && $this->json_validator($validated['tags'])){
                $size = mb_strlen(json_encode($validated['tags'], JSON_NUMERIC_CHECK), '8bit');
                if($size > 512){
                    return response()->json("Tags data field cannot exceed 512 bytes", 403);
                }
                $validated['tags'] = json_encode($validated['tags']);
            }
            else
                $validated['tags'] = null;
            
            
            //change the comment
            $comment->update($validated);

            return response()->json($comment, 200);
        }
        else
        {
            return response()->json("Comment {$id} on review {$review_id} not found", 404);
        }
    }

    private function json_validator($data=NULL) : bool {

        if (!empty($data)) {

            @json_encode($data);

            return (json_last_error() === JSON_ERROR_NONE);

        }
        return false;
    }

    /**
     * @OA\Delete(
     *     path="/api/reviews/{review_id}/comments/{id}",
     *     summary="Deletes a specified comment on a review",
     *     operationId="comment.remove",
     *     tags={"Comment"},
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
     *         description="ID of review the comment belongs to",
     *         required=true,
     *         @OA\Schema(type="string"),
     *      ),
     *      @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Comment ID",
     *         required=true,
     *         @OA\Schema(type="string"),
     *      ),
     *     @OA\Response(response="200", description="Returns a message")
     * )
     */
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function remove(int $review_id, int $id) : JsonResponse
    {
        $comment = Comment::where('review_id', $review_id)->find($id);
        if ($comment && $comment->integration_id == $this->integration_id)
        {
            $comment->delete();
            return response()->json("Comment {$id} on review {$id} deleted", 200);
        }
        else
        {
            return response()->json("Comment {$id} on review {$id} not found", 404);
        }
    }
}
