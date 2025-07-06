<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Post;
use App\Http\Resources\PostResource;
// use Illuminate\Foundation\Concerns\HasMiddleware;

class PostController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function __construct()
    {
        //$this->middleware('auth:sanctum')->except(['index', 'show']);
    }

    public function index()
    {
        $posts = Post::with(['tags','category','user'])->get(); // Eager load relationships
        return PostResource::collection($posts);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            // 'image' => 'nullable|image|max:2048', // Optional image field
            // 'slug' => 'required|string|max:255|unique:posts,slug',
            ]);

            $post = null;

            \DB::transaction(function () use ($request, $validated, &$post) {
                $post = $request->user()->posts()->create($validated);
                $post->tags()->sync($request->input('tags', [])); // Sync tags if provided
            });

            // Handle image upload if provided
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                $path = $image->store('posts', 'public');

                    $post->images()->create([
                        'image_path' => $path,
                    ]);
                }
            }

        return response()->json('Post created successfully.', 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create post.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $post = Post::findOrFail($id);

        return new PostResource($post);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $post = Post::findOrFail($id);

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'content' => 'sometimes|required|string',
        ]);

        $post->update($validated);

        return response()->json($post);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $post = Post::findOrFail($id);
        $post->delete();

        return response()->json(null, 204);
    }
}
