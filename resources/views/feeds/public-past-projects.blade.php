<?= '<?xml version="1.0" encoding="UTF-8"?>' ?>

<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
    <channel>
        <title>{{ config('app.name') }} — Past Projects</title>
        <link>{{ route('projects.past') }}</link>
        <description>Completed research projects published publicly by Eindhoven University of Technology.</description>
        <language>{{ str_replace('_', '-', app()->getLocale()) }}</language>
        <lastBuildDate>{{ $lastBuildDate->toRfc2822String() }}</lastBuildDate>
        <atom:link href="{{ route('projects.past.feed') }}" rel="self" type="application/rss+xml" />

        @foreach ($projects as $project)
            <item>
                <title>{{ $project->name }}</title>
                <link>{{ route('projects.show', $project) }}</link>
                <guid isPermaLink="true">{{ route('projects.show', $project) }}</guid>
                <pubDate>{{ $project->updated_at->toRfc2822String() }}</pubDate>
                <description><![CDATA[{{ $project->short_description }}]]></description>
            </item>
        @endforeach
    </channel>
</rss>
