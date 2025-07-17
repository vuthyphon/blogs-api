<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Post;
use App\Models\Category;
use App\Models\Tag;
use App\Models\PostTags;
use App\Models\PostImage;

use App\Http\Resources\PostResource;
use App\Http\Resources\ArticleResource;
use App\Http\Resources\MetaResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
        return response()->json(PostResource::collection($posts));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            // ✅ Validate input
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'body' => 'required|string',
                'category_id' => 'required|exists:categories,id',
                'thumbnail' => 'nullable|image|max:10000',
            ]);

            // ✅ Handle thumbnail upload (optional)
            if ($request->hasFile('thumbnail')) {
                $thumbnail = $request->file('thumbnail');
                $validated['image'] = $thumbnail->store('posts', 'public'); // stored as 'image' field
            }

            // ✅ Start DB transaction
            DB::transaction(function () use ($request, $validated) {
                // Create post
                $post = $request->user()->posts()->create($validated);

                // Attach tags
                $tagsInput = $request->input('tags', []);
                $tags = is_array($tagsInput) ? $tagsInput : explode(',', $tagsInput);
                $post->tags()->sync(array_map('intval', $tags));

                // Upload multiple images
                if ($request->hasFile('images')) {
                    $images = $request->file('images');
                    if (!is_array($images)) {
                        $images = [$images]; // ensure it's an array
                    }

                    $imageData = [];
                    foreach ($images as $image) {
                        $imageData[] = ['image_path' => $image->store('posts', 'public')];
                    }

                    $post->images()->createMany($imageData);
                }
            });

            return response()->json(['message' => 'Post created successfully'], 201);

        } catch (\Throwable $e) {
            Log::error('Post creation failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to create post.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $post = Post::findOrFail($id);
        if($post)
        {
             return response()->json(new ArticleResource($post));
        }
        else{
            return response()->json(["message"=>'not found!']);
        }


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

    public function upload(Request $request)
    {
        $request->validate([
            'images.*' => 'image|mimes:jpg,jpeg,png,gif|max:2048'
        ]);

        //return $request->file('images');
        $paths = [];
        foreach ($request->file('images') as $file) {
            $paths[] = $file->store('img', 'public');
        }

        return response()->json([
            'message' => 'Images uploaded successfully!',
            'paths' => $paths
        ]);
    }

    // test upload with posts image

    public function upload_images(Request $request)
    {
       if ($request->hasFile('images')) {

                $images = $request->file('images');

                if (!is_array($images)) {
                    $images = [$images];
                }
                //return response()->json($request->input('images'));
                $data = [];
                foreach ($images as $image) {
                    $path = $image->store('posts', 'public');
                    $data[] = [
                        'image_path' => $path,
                        'post_id' => 78
                    ];
                }

                //return response()->json($data);
                 PostImage::insert($data);

                   // $imageData=["image_path"=>'test_path','post_id'=>78];

                   // PostImage::insert($imageData);

            }

    }

    public function meta(Request $request)
    {
        $cate=Category::select('id', 'name')->get();
        $tags=Tag::select('id', 'name')->get();
        $meta=[
            'categories' =>$cate ,
            'tags' => $tags,
        ];

        //return $meta;

        return (new MetaResource($meta))->response()->getData(true);
    }
}
