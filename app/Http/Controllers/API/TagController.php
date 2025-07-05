<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TagController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
         $validated = $request->validate([
        'title' => 'required',
        'body' => 'required',
        'category_id' => 'required',
        'tags' => 'array',
        'image' => 'nullable|image'
        ]);

        $slug = Str::slug($request->title);
        $validated['slug'] = $slug;
        $validated['user_id'] = auth()->id();

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('images', 'public');
        }

        $post = Post::create($validated);
        $post->tags()->attach($request->tags);

        return response()->json($post->load('tags', 'category', 'author'));
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
