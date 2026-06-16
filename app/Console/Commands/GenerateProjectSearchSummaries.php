<?php

namespace App\Console\Commands;

use App\Ai\Agents\ProjectSearchSummaryGenerator;
use App\Models\Project;
use App\Services\ProjectSearchSummaryService;
use Illuminate\Console\Command;
use Throwable;

class GenerateProjectSearchSummaries extends Command
{
    protected $signature = 'projects:generate-search-summaries
                            {--force : Regenerate summaries even when up to date}
                            {--limit= : Maximum number of projects to process}';

    protected $description = 'Generate LLM search summaries for published, available projects';

    public function handle(ProjectSearchSummaryService $summaryService): int
    {
        if (! filled(config('ai.providers.openai.key')) && ! ProjectSearchSummaryGenerator::isFaked()) {
            $this->warn('OPENAI_API_KEY is not configured; skipping search summary generation.');

            return Command::SUCCESS;
        }

        $force = (bool) $this->option('force');
        $limit = $this->option('limit') !== null ? (int) $this->option('limit') : null;

        $query = Project::query()
            ->where('is_published', true)
            ->available()
            ->orderBy('id');

        if (! $force) {
            $query->where(function ($q) {
                $q->whereNull('search_summary')
                    ->orWhereNull('search_summary_generated_at')
                    ->orWhereColumn('updated_at', '>', 'search_summary_generated_at');
            });
        }

        $total = (clone $query)->count();

        if ($total === 0) {
            $this->info('No projects need search summary generation.');

            return Command::SUCCESS;
        }

        if ($limit !== null) {
            $total = min($total, $limit);
        }

        $this->info("Generating search summaries for up to {$total} project(s)...");

        $processed = 0;
        $succeeded = 0;
        $failed = 0;

        $query->when($limit !== null, fn ($q) => $q->limit($limit))
            ->chunkById(25, function ($projects) use ($summaryService, $force, &$processed, &$succeeded, &$failed) {
                foreach ($projects as $project) {
                    $processed++;

                    if (! $summaryService->needsRegeneration($project, $force)) {
                        continue;
                    }

                    try {
                        $summaryService->generateFor($project);
                        $succeeded++;
                        $this->line("  ✓ Project #{$project->id}: {$project->name}");
                    } catch (Throwable $e) {
                        $failed++;
                        $this->error("  ✗ Project #{$project->id}: {$e->getMessage()}");
                    }
                }
            });

        $this->newLine();
        $this->info("Done. Processed {$processed}, generated {$succeeded}, failed {$failed}.");

        return $failed > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}
