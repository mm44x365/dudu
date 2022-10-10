<?php

namespace App\Http\Controllers;

use App\Models\Category;
// use Dotenv\Validator;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Alert;

class CategoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:category_show', ['only' => 'index']);
        $this->middleware('permission:category_create', ['only' => ['create', 'store']]);
        $this->middleware('permission:category_update', ['only' => ['edit', 'update']]);
        $this->middleware('permission:category_detail', ['only' => 'show']);
        $this->middleware('permission:category_delete', ['only' => 'destroy']);
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $categories = Category::with('descendants');
        if ($request->has('keyword') && trim($request->keyword)) {
            $categories->search($request->keyword);
        } else {
            $categories->onlyParent();
        }
        return view('categories.index', [
            'categories' => $categories->paginate(5)->appends(['keyword' => $request->get('keyword')])
        ]);
    }

    public function select(Request $request)
    {
        $categories = [];
        if ($request->has('q')) {
            $search = $request->q;
            $categories = Category::select('id', 'title')->where('title', 'LIKE', "%$search%")->limit(6)->get();
        } else {
            $categories = Category::select('id', 'title')->onlyParent()->limit(6)->get();
        }

        return response()->json($categories);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('categories.create');
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
                'title' => 'required|string|max:60',
                'slug' => 'required|string|unique:categories,slug',
                'thumbnail' => 'required',
                'description' => 'required|string|max:240',
            ],
            [],
            $this->attributes() //kirim atribut dari method bahasa dibawah
        );

        // jika validasi ada yang salah
        if ($validator->fails()) {
            if ($request->has('parent_category')) {
                $request['parent_category'] = Category::select('id', 'title')->find($request->parent_category);
            }
            Alert::toast(trans('categories.alert.required.message.error'), 'error');
            return redirect()->back()->withInput($request->all())->withErrors($validator);
        }

        // coba simpan data
        try {
            Category::create([
                'title' => $request->title,
                'slug' => $request->slug,
                'thumbnail' => parse_url($request->thumbnail)['path'],
                'description' => $request->description,
                'parent_id' => $request->parent_category
            ]);
            // jika berhasil kirim notif dan arahkan ke
            Alert::toast(trans('categories.alert.create.message.success'), 'success');
            return redirect()->route('categories.index');
        } catch (\Throwable $th) {
            if ($request->has('parent_category')) {
                $request['parent_category'] = Category::select('id', 'title')->find($request->parent_category);
            }
            Alert::toast(trans('categories.alert.create.message.error', ['error' => $th->getMessage()]), 'error');
            return redirect()->back()->withInput($request->all());
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Category  $category
     * @return \Illuminate\Http\Response
     */
    public function show(Category $category)
    {
        return view('categories.show', compact('category'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Category  $category
     * @return \Illuminate\Http\Response
     */
    public function edit(Category $category)
    {
        return view('categories.edit', compact('category'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Category  $category
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Category $category)
    {
        // cek validasi inputan data
        $validator = Validator::make(
            $request->all(),
            [
                'title' => 'required|string|max:60',
                'slug' => 'required|string|unique:categories,slug,' . $category->id,
                'thumbnail' => 'required',
                'description' => 'required|string|max:240',
            ],
            [],
            $this->attributes() //kirim atribut dari method bahasa dibawah
        );

        // jika validasi ada yang salah
        if ($validator->fails()) {
            if ($request->has('parent_category')) {
                $request['parent_category'] = Category::select('id', 'title')->find($request->parent_category);
            }
            Alert::toast(trans('categories.alert.required.message.error'), 'error');
            return redirect()->back()->withInput($request->all())->withErrors($validator);
        }

        // coba edit data
        try {
            $category->update([
                'title' => $request->title,
                'slug' => $request->slug,
                'thumbnail' => parse_url($request->thumbnail)['path'],
                'description' => $request->description,
                'parent_id' => $request->parent_category
            ]);
            // jika berhasil kirim dan notif arahkan ke
            Alert::toast(trans('categories.alert.update.message.success'), 'success');
            return redirect()->route('categories.index');
        } catch (\Throwable $th) {
            if ($request->has('parent_category')) {
                $request['parent_category'] = Category::select('id', 'title')->find($request->parent_category);
            }
            Alert::toast(trans('categories.alert.update.message.error', ['error' => $th->getMessage()]), 'error');
            return redirect()->back()->withInput($request->all());
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Category  $category
     * @return \Illuminate\Http\Response
     */
    public function destroy(Category $category)
    {
        try {
            $category->delete();
            Alert::toast(trans('categories.alert.delete.message.success'), 'success');
        } catch (\Throwable $th) {
            Alert::toast(trans('categories.alert.delete.message.error', ['error' => $th->getMessage()]), 'error');
        }

        return redirect()->back();
    }

    private function attributes()
    {
        return [
            'title' => trans('categories.form_control.input.title.attribute'),
            'slug' => trans('categories.form_control.input.slug.attribute'),
            'thumbnail' => trans('categories.form_control.input.thumbnail.attribute'),
            'description' => trans('categories.form_control.textarea.description.attribute'),
        ];
    }
}
