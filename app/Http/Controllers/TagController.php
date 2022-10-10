<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Alert;

class TagController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:tag_show', ['only' => 'index']);
        $this->middleware('permission:tag_create', ['only' => ['create', 'store']]);
        $this->middleware('permission:tag_update', ['only' => ['edit', 'update']]);
        $this->middleware('permission:tag_delete', ['only' => 'destroy']);
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $tags = Tag::all();
        return view('tags.index', compact('tags'));
    }

    public function select(Request $request)
    {
        $tags = [];
        if ($request->has('q')) {
            $search = $request->q;
            $tags = Tag::select('id', 'title')->where('title', 'LIKE', "%$search%")->get();
        } else {
            $tags = Tag::select('id', 'title')->limit(5)->get();
        }

        return response()->json($tags);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('tags.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // cek validasi inputan data
        $validator = Validator::make(
            $request->all(),
            [
                'title' => 'required|string|max:25',
                'slug' => 'required|string|unique:tags,slug'
            ],
            [],
            $this->getAttributes()
        );

        // jika validasi ada yang salah
        if ($validator->fails()) {
            Alert::toast(trans('tags.alert.required.message.error'), 'error');
            return redirect()->back()->withInput($request->all())->withErrors($validator);
        }

        // coba simpan data
        try {
            Tag::create([
                'title' => $request->title,
                'slug' => $request->slug,
            ]);
            // jika berhasil kirim notif dan arahkan ke
            Alert::toast(trans('tags.alert.create.message.success'), 'success');
            return redirect()->route('tags.index');
        } catch (\Throwable $th) {
            Alert::toast(trans('tags.alert.create.message.error', ['error' => $th->getMessage()]), 'error');
            return redirect()->back()->withInput($request->all());
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Tag  $tag
     * @return \Illuminate\Http\Response
     */
    public function show(Tag $tag)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Tag  $tag
     * @return \Illuminate\Http\Response
     */
    public function edit(Tag $tag)
    {
        return view('tags.edit', compact('tag'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Tag  $tag
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Tag $tag)
    {
        // cek validasi inputan data
        $validator = Validator::make(
            $request->all(),
            [
                'title' => 'required|string|max:25',
                'slug' => 'required|string|unique:tags,slug,' . $tag->id
            ],
            [],
            $this->getAttributes()
        );

        // jika validasi ada yang salah
        if ($validator->fails()) {
            Alert::toast(trans('tags.alert.required.message.error'), 'error');
            return redirect()->back()->withInput($request->all())->withErrors($validator);
        }

        // coba simpan data
        try {
            $tag->update([
                'title' => $request->title,
                'slug' => $request->slug,
            ]);
            // jika berhasil kirim notif dan arahkan ke
            Alert::toast(trans('tags.alert.update.message.success'), 'success');
            return redirect()->route('tags.index');
        } catch (\Throwable $th) {
            Alert::toast(trans('tags.alert.update.message.error', ['error' => $th->getMessage()]), 'error');
            return redirect()->back()->withInput($request->all());
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Tag  $tag
     * @return \Illuminate\Http\Response
     */
    public function destroy(Tag $tag)
    {
        try {
            $tag->delete();
            Alert::toast(trans('tags.alert.delete.message.success'), 'success');
        } catch (\Throwable $th) {
            Alert::toast(trans('tags.alert.delete.message.error', ['error' => $th->getMessage()]), 'error');
        }

        return redirect()->back();
    }
    private function getAttributes()
    {
        return [
            'title' => trans('tags.form_control.input.title.attribute'),
            'slug' => trans('tags.form_control.input.slug.attribute'),
        ];
    }
}
