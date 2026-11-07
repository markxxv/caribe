<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Blog;
use App\Models\Tour;
class Front extends Controller
{
    public function index()
    {
        $blogs = Blog::query()
            ->where('active', true)
            ->with(['countries', 'media'])
            ->orderByDesc('created_at')
            ->take(6)
            ->get();

        $tours = Tour::query()
            ->where('active', true)
            ->with(['countries', 'media'])
            ->orderByDesc('created_at')
            ->take(6)
            ->get();

        return view ('home', compact('blogs', 'tours'));
    }

    public function blogIndex(Request $request)
    {
        $query = Blog::query()
            ->where('active', true)
            ->with(['countries', 'media']);

        // Filter by country
        if ($request->has('country') && $request->country !== 'all') {
            $query->whereHas('countries', function($q) use ($request) {
                $q->where('countries.id', $request->country);
            });
        }

        // Filter by category (prepared for future)
        if ($request->has('category') && $request->category !== 'all') {
            $query->whereHas('categories', function($q) use ($request) {
                $q->where('categories.id', $request->category);
            });
        }

        // Search functionality
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('meta_description', 'like', "%{$search}%");
            });
        }

        $blogs = $query->orderByDesc('created_at')->paginate(12);

        // Get all countries for filter
        $countries = \App\Models\Country::query()
            ->whereHas('blogs', function($q) {
                $q->where('active', true);
            })
            ->orderBy('title')
            ->get();

        return view('blog.index', compact('blogs', 'countries'));
    }


    public function blogShow(string $slug)
    {
        $blog = Blog::query()
            ->where('slug', $slug)
            ->where('active', true)
            ->with(['countries', 'media'])
            ->firstOrFail();

        return view('blog.show', compact('blog'));
    }


    public function toursIndex(Request $request)
    {
        $query = Tour::query()
            ->where('active', true)
            ->with(['countries', 'media']);

        // Filter by country
        if ($request->has('country') && $request->country !== 'all') {
            $query->whereHas('countries', function($q) use ($request) {
                $q->where('countries.id', $request->country);
            });
        }

        // Filter by category (prepared for future)
        if ($request->has('category') && $request->category !== 'all') {
            $query->whereHas('categories', function($q) use ($request) {
                $q->where('categories.id', $request->category);
            });
        }

        // Search functionality
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('meta_description', 'like', "%{$search}%");
            });
        }

        $tours = $query->orderByDesc('created_at')->paginate(12);

        // Get all countries for filter
        $countries = \App\Models\Country::query()
            ->whereHas('blogs', function($q) {
                $q->where('active', true);
            })
            ->orderBy('title')
            ->get();

        return view('tours.index', compact('tours', 'countries'));
    }


    public function toursShow(string $slug)
    {
        $tour = Tour::query()
            ->where('slug', $slug)
            ->where('active', true)
            ->with(['countries', 'media'])
            ->firstOrFail();

        return view('tours.show', compact('tour'));
    }



}
