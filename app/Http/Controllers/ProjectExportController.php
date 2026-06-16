<?php

namespace App\Http\Controllers;

use App\Exports\DivisionProjectsExport;
use App\Models\Division;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProjectExportController extends Controller
{
    /**
     * Download an Excel (.xlsx) file with the projects of a single division,
     * containing only the information needed to divide/assign projects
     * (numbers, titles, section, supervisors, ...). Descriptions, images and
     * rich content are intentionally left out.
     *
     * Administrators may export any division; other holders of the
     * "export division projects" permission may only export their own division.
     */
    public function __invoke(Division $division): BinaryFileResponse
    {
        $user = auth()->user();

        $canExport = $user && (
            $user->hasRole('Administrator')
            || ($user->can('export division projects')
                && $user->group?->section?->division_id === $division->id)
        );

        abort_unless($canExport, 403);

        $filename = 'projects-'.Str::slug($division->name).'-'.now()->format('Y-m-d').'.xlsx';

        return Excel::download(new DivisionProjectsExport($division), $filename);
    }
}
