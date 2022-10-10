<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Post;

class BlogController extends Controller
{
    private $perpage = 10;
    public function home()
    {
        return view('blog.home', [
            'posts' => Post::publish()->latest()->paginate($this->perpage)
        ]);
    }
}
