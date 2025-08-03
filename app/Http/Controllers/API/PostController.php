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
        $posts = Post::with(['tags','category','user'])->where(['is_active'=>1])->get(); // Eager load relationships
        return response()->json(PostResource::collection($posts));
    }

    public function articles(Request $request)
    {
        $posts = Post::with(['tags','category','user'])->where(['is_publish'=>1,'is_active'=>1])->limit(6)->get(); // Eager load relationships
        $newTicker=Post::select(['id','title'])->where(['is_publish'=>1,'is_active'=>1])->limit(15)->get();
        return response()->json(['posts'=>PostResource::collection($posts),'news_tickers'=>$newTicker]);
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
        try{
            $post = Post::findOrFail($id);

           // return response()->json($request->all());

            $validated = $request->validate([
                'title' => 'sometimes|required|string|max:255',
                'body' => 'sometimes|required|string',
                'category_id' => 'required|exists:categories,id',
                'thumbnail' => 'nullable|image|max:10000',
            ]);

            // ✅ Handle thumbnail upload (optional)
            if ($request->hasFile('thumbnail')) {
                $thumbnail = $request->file('thumbnail');
                $validated['image'] = $thumbnail->store('posts', 'public'); // stored as 'image' field
            }

        //     return $validated;
        //$validated['category_id']=$request->input('category_id');

            $post->update($validated);
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

           return response()->json(['message' => 'Post update successfully'], 201);
        }
        catch (\Throwable $e) {
            Log::error('Post creation failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to update post.',
                'error' => $e->getMessage(),
            ], 500);
        }

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $post = Post::findOrFail($id);
        $post->update(['is_active',0]);

        return response('Post deleted')->json(null, 204);
    }


    public function togglePublish(Request $request, $id)
    {
        $post = Post::findOrFail($id);
        $post->is_publish = $request->input('is_publish', 0);
        $post->save();

        return response()->json(['message' => 'Publish status updated']);
    }
    public function publish_post(string $id)
    {
        $post = Post::findOrFail($id);
        $post->update(['is_publish',0]);

        return response('Post deleted')->json(null, 204);
    }

    public function active_post(string $id)
    {
        $post = Post::findOrFail($id);
        $post->update(['is_active',1]);

        return response('Post deleted')->json(null, 204);
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
