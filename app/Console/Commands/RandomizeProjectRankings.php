<?php

namespace App\Console\Commands;

use App\Models\Project;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RandomizeProjectRankings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'projects:randomize-rankings';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Randomize the ranking order for all projects to ensure fair, consistent pagination';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting project ranking randomization...');

        try {
            // Get all published, available projects (matching the criteria used in ProjectController)
            $projects = Project::available()
                ->where('is_published', true)
                ->pluck('id')
                ->shuffle();

            // Assign random rankings starting from 1
            $ranking = 1;
            DB::transaction(function () use ($projects, &$ranking) {
                foreach ($projects as $projectId) {
                    Project::where('id', $projectId)->update(['random_ranking' => $ranking++]);
                }
            });

            $this->info("✓ Successfully randomized rankings for {$projects->count()} projects.");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to randomize project rankings: '.$e->getMessage());

            return Command::FAILURE;
        }
    }
}
