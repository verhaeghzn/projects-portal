<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Filament\Facades\Filament;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProjectExportController extends Controller
{
    /**
     * Stream a CSV with all projects containing only the information needed to
     * divide/assign projects (numbers, titles, division, section, supervisors, ...).
     * Descriptions, images and rich content are intentionally left out.
     */
    public function __invoke(): StreamedResponse
    {
        abort_unless(
            (bool) auth()->user()?->canAccessPanel(Filament::getPanel('admin')),
            403
        );

        $columns = [
            'Project number',
            'Title',
            'Division',
            'Section',
            'Group',
            'Type(s)',
            'Supervisors',
            'Owner',
            'Organization',
            'Status',
            'Student',
            'Published',
            'Created at',
        ];

        $filename = 'projects-export-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($columns) {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM so Excel renders accented characters correctly.
            fwrite($handle, "\xEF\xBB\xBF");

            // Semicolon delimiter matches the default Excel separator in NL/EU locales.
            fputcsv($handle, $columns, ';');

            Project::query()
                ->with([
                    'supervisorLinks.supervisor.group.section.division',
                    'owner',
                    'organization',
                    'types',
                ])
                ->orderBy('project_number')
                ->chunk(200, function ($projects) use ($handle) {
                    foreach ($projects as $project) {
                        $section = $project->section;
                        $group = $project->group;

                        $supervisors = $project->supervisorLinks
                            ->map(fn ($link) => $link->name)
                            ->filter()
                            ->implode(', ');

                        fputcsv($handle, [
                            $project->project_number,
                            $project->name,
                            $section?->division?->name,
                            $section?->name,
                            $group?->name,
                            $project->types->pluck('name')->implode(', '),
                            $supervisors,
                            $project->owner?->name,
                            $project->organization?->name,
                            $project->is_taken ? 'Taken' : 'Available',
                            $project->student_name,
                            $project->is_published ? 'Yes' : 'No',
                            $project->created_at?->format('Y-m-d'),
                        ], ';');
                    }
                });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
