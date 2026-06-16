<?php

namespace App\Exports;

use App\Models\Division;
use App\Models\Project;
use Maatwebsite\Excel\Concerns\FromGenerator;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DivisionProjectsExport implements FromGenerator, ShouldAutoSize, WithHeadings, WithStyles, WithTitle
{
    public function __construct(protected Division $division) {}

    public function title(): string
    {
        return mb_substr($this->division->name, 0, 31);
    }

    public function headings(): array
    {
        return [
            'Project number',
            'Title',
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
    }

    /**
     * Stream the rows so large divisions don't exhaust memory. A project's
     * division is derived from its first supervisor's group/section, matching
     * the division shown elsewhere and used for project numbering.
     */
    public function generator(): \Generator
    {
        $projects = Project::query()
            ->with([
                'supervisorLinks.supervisor.group.section.division',
                'owner',
                'organization',
                'types',
            ])
            ->orderBy('project_number')
            ->lazy(200);

        foreach ($projects as $project) {
            $section = $project->section;

            if ($section?->division?->id !== $this->division->id) {
                continue;
            }

            $supervisors = $project->supervisorLinks
                ->map(fn ($link) => $link->name)
                ->filter()
                ->implode(', ');

            yield [
                $project->project_number,
                $project->name,
                $section?->name,
                $project->group?->name,
                $project->types->pluck('name')->implode(', '),
                $supervisors,
                $project->owner?->name,
                $project->organization?->name,
                $project->is_taken ? 'Taken' : 'Available',
                $project->student_name,
                $project->is_published ? 'Yes' : 'No',
                $project->created_at?->format('Y-m-d'),
            ];
        }
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
