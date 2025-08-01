<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\Post;
use App\Models\Like;
use App\Models\Comment;

class PostController extends Controller
{
    /**
     * Create a new post (Admin and SuperAdmin only).
     */
    public function createPost(Request $request)
    {
        $request->validate([
            'content' => 'required|string',
            'image'   => 'nullable|string', // Base64 encoded image
        ]);

        try {
            $post = new Post();
            $post->user_id = Auth::id();
            $post->content = $request->content;
            $post->image = $request->image; // Store Base64 directly
            $post->save();

            return response()->json([
                'response_code' => 201,
                'status'        => 'success',
                'message'       => 'Post created successfully',
                'post'          => $post,
            ], 201);
        } catch (\Exception $e) {
            Log::error('Post Creation Error: ' . $e->getMessage());

            return response()->json([
                'response_code' => 500,
                'status'        => 'error',
                'message'       => 'Failed to create post',
            ], 500);
        }
    }

    /**
     * Get paginated posts.
     */
    public function getPosts()
    {
        try {
            $posts = Post::with(['user', 'likes', 'comments'])->latest()->paginate(10);

            return response()->json([
                'response_code' => 200,
                'status'        => 'success',
                'message'       => 'Fetched posts successfully',
                'posts'         => $posts,
            ]);
        } catch (\Exception $e) {
            Log::error('Post Fetch Error: ' . $e->getMessage());

            return response()->json([
                'response_code' => 500,
                'status'        => 'error',
                'message'       => 'Failed to fetch posts',
            ], 500);
        }
    }

    /**
     * Like a post.
     */
    public function likePost(Request $request, $postId)
    {
        try {
            $post = Post::findOrFail($postId);
            $userId = Auth::id();

            $like = Like::firstOrCreate([
                'user_id' => $userId,
                'post_id' => $postId,
            ]);

            return response()->json([
                'response_code' => 200,
                'status'        => 'success',
                'message'       => 'Post liked successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('Like Error: ' . $e->getMessage());

            return response()->json([
                'response_code' => 500,
                'status'        => 'error',
                'message'       => 'Failed to like post',
            ], 500);
        }
    }

    /**
     * Comment on a post.
     */
    public function commentPost(Request $request, $postId)
    {
        $request->validate([
            'content' => 'required|string',
        ]);

        try {
            $post = Post::findOrFail($postId);
            $comment = new Comment();
            $comment->user_id = Auth::id();
            $comment->post_id = $postId;
            $comment->content = $request->content;
            $comment->save();

            return response()->json([
                'response_code' => 201,
                'status'        => 'success',
                'message'       => 'Comment added successfully',
                'comment'       => $comment,
            ], 201);
        } catch (\Exception $e) {
            Log::error('Comment Error: ' . $e->getMessage());

            return response()->json([
                'response_code' => 500,
                'status'        => 'error',
                'message'       => 'Failed to add comment',
            ], 500);
        }
    }
}