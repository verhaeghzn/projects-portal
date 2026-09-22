<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Response;

class PublicPastProjectsRssFeedController extends Controller
{
    public function __invoke(): Response
    {
        $projects = Project::query()
            ->publicPast()
            ->with(['supervisors', 'tags'])
            ->latest('updated_at')
            ->get();

        $lastBuildDate = $projects->max('updated_at') ?? now();

        return response()
            ->view('feeds.public-past-projects', [
                'projects' => $projects,
                'lastBuildDate' => $lastBuildDate,
            ])
            ->header('Content-Type', 'application/rss+xml; charset=UTF-8');
    }
}
