<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use Illuminate\View\View as IlluminateView;

class MovieController extends Controller
{
    public function index(): IlluminateView
    {
        $movies = Movie::all();

        return view('movies.index', compact('movies'));
    }

    public function show(Movie $movie): IlluminateView
    {
        return view('movies.show', compact('movie'));
    }
}
