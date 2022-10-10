<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Category;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Alert;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class PostController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:post_show', ['only' => 'index']);
        $this->middleware('permission:post_create', ['only' => ['create', 'store']]);
        $this->middleware('permission:post_update', ['only' => ['edit', 'update']]);
        $this->middleware('permission:post_detail', ['only' => 'show']);
        $this->middleware('permission:post_delete', ['only' => 'destroy']);
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $statusSelected = in_array($request->get('status'), ['all', 'publish', 'draft']) ? $request->get('status') : "all";
        switch ($statusSelected) {
            case 'publish':
                $posts = Post::publish()->paginate(10)->withQueryString();
                break;
            case 'draft':
                $posts = Post::draft()->paginate(10)->withQueryString();
                break;

            default:
                $posts = Post::paginate(10)->withQueryString();
                break;
        }
        if ($request->get('keyword')) {
            // $posts->search($request->get('keyword'));
            $posts = Post::search($request->get('keyword'))->get();
        }
        return view('posts.index', [
            'posts' => $posts,
            'statuses' => $this->statusesSearch(),
            'statusSelected' => $statusSelected
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('posts.create', [
            'categories' => Category::with('descendants')->onlyParent()->get(),
            'statuses' => $this->statuses()
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'title' => 'required|string|max:60',
                'slug' => 'required|string|unique:posts,slug',
                'thumbnail' => 'required',
                'description' => 'required|string|max:240',
                'content' => 'required',
                'category' => 'required',
                'tag' => 'required',
                'status' => 'required',
            ],
            [],
            $this->attributes()
        );

        if ($validator->fails()) {
            if ($request['tag']) {
                $request['tag'] = Tag::select('id', 'title')->whereIn('id', $request->tag)->get();
            }
            Alert::toast(trans('categories.alert.required.message.error'), 'error');
            return redirect()->back()->withInput($request->all())->withErrors($validator);
        }

        DB::beginTransaction();
        try {
            $post = Post::create([
                "title" => $request->title,
                "slug" => $request->slug,
                "thumbnail" => parse_url($request->thumbnail)['path'],
                "description" => $request->description,
                "content" => $request->content,
                "status" => $request->status,
                "user_id" => Auth::user()->id,
            ]);
            $post->tags()->attach($request->tag);
            $post->categories()->attach($request->category);
            Alert::toast(trans('posts.alert.create.message.success'), 'success');
            return redirect()->route('posts.index');
        } catch (\Throwable $th) {
            DB::rollBack();
            Alert::toast(trans('posts.alert.create.message.error', ['error' => $th->getMessage()]), 'error');
            if ($request['tag']) {
                $request['tag'] = Tag::select('id', 'title')->whereIn('id', $request->tag)->get();
            }
            return redirect()->back()->withInput($request->all());
        } finally {
            DB::commit();
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Post  $post
     * @return \Illuminate\Http\Response
     */
    public function show(Post $post)
    {
        $categories = $post->categories;
        $tags = $post->tags;
        return view('posts.detail', compact('post', 'categories', 'tags'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Post  $post
     * @return \Illuminate\Http\Response
     */
    public function edit(Post $post)
    {
        return view('posts.edit', [
            'post' => $post,
            'categories' => Category::with('descendants')->onlyParent()->get(),
            'statuses' => $this->statuses()
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Post  $post
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Post $post)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'title' => 'required|string|max:60',
                'slug' => 'required|string|unique:posts,slug,' . $post->id,
                'thumbnail' => 'required',
                'description' => 'required|string|max:240',
                'content' => 'required',
                'category' => 'required',
                'tag' => 'required',
                'status' => 'required',
            ],
            [],
            $this->attributes()
        );

        if ($validator->fails()) {
            if ($request['tag']) {
                $request['tag'] = Tag::select('id', 'title')->whereIn('id', $request->tag)->get();
            }
            Alert::toast(trans('categories.alert.required.message.error'), 'error');
            return redirect()->back()->withInput($request->all())->withErrors($validator);
        }

        DB::beginTransaction();
        try {
            $post->update([
                "title" => $request->title,
                "slug" => $request->slug,
                "thumbnail" => parse_url($request->thumbnail)['path'],
                "description" => $request->description,
                "content" => $request->content,
                "status" => $request->status,
                "user_id" => Auth::user()->id,
            ]);
            $post->tags()->sync($request->tag);
            $post->categories()->sync($request->category);
            Alert::toast(trans('posts.alert.update.message.success'), 'success');
            return redirect()->route('posts.index');
        } catch (\Throwable $th) {
            DB::rollBack();
            Alert::toast(trans('posts.alert.update.message.error', ['error' => $th->getMessage()]), 'error');
            if ($request['tag']) {
                $request['tag'] = Tag::select('id', 'title')->whereIn('id', $request->tag)->get();
            }
            return redirect()->back()->withInput($request->all());
        } finally {
            DB::commit();
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Post  $post
     * @return \Illuminate\Http\Response
     */
    public function destroy(Post $post)
    {
        DB::beginTransaction();
        try {
            $post->tags()->detach();
            $post->categories()->detach();
            $post->delete();
            Alert::toast(trans('posts.alert.delete.message.success'), 'success');
            return redirect()->route('posts.index');
        } catch (\Throwable $th) {
            DB::rollBack();
            Alert::toast(trans('posts.alert.delete.message.error', ['error' => $th->getMessage()]), 'error');
        } finally {
            DB::commit();
            return redirect()->back();
        }
    }

    private function statuses()
    {
        return [
            'draft' => trans('posts.form_control.select.status.option.draft'),
            'publish' => trans('posts.form_control.select.status.option.publish'),
        ];
    }

    private function statusesSearch()
    {
        return [
            'all' => trans('posts.form_control.select.status.option.all'),
            'draft' => trans('posts.form_control.select.status.option.draft'),
            'publish' => trans('posts.form_control.select.status.option.publish'),
        ];
    }

    private function attributes()
    {
        return [
            'title' => trans('posts.form_control.input.title.attribute'),
            'slug' => trans('posts.form_control.input.slug.attribute'),
            'thumbnail' => trans('posts.form_control.input.thumbnail.attribute'),
            'description' => trans('posts.form_control.textarea.description.attribute'),
            'content' => trans('posts.form_control.textarea.content.attribute'),
            'category' => trans('posts.form_control.input.category.attribute'),
            'tag' => trans('posts.form_control.select.tag.attribute'),
            'status' => trans('posts.form_control.select.status.attribute'),
        ];
    }
}
