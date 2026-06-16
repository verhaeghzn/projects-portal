<?php

namespace App\Console\Commands;

use App\Ai\Agents\GroupSearchSummaryGenerator;
use App\Models\Group;
use App\Services\GroupSearchSummaryService;
use Illuminate\Console\Command;
use Throwable;

class GenerateGroupSearchSummaries extends Command
{
    protected $signature = 'groups:generate-search-summaries
                            {--force : Regenerate summaries even when up to date}
                            {--limit= : Maximum number of groups to process}';

    protected $description = 'Generate LLM search summaries for research groups based on their projects';

    public function handle(GroupSearchSummaryService $summaryService): int
    {
        if (! filled(config('ai.providers.openai.key')) && ! GroupSearchSummaryGenerator::isFaked()) {
            $this->warn('OPENAI_API_KEY is not configured; skipping group search summary generation.');

            return Command::SUCCESS;
        }

        $force = (bool) $this->option('force');
        $limit = $this->option('limit') !== null ? (int) $this->option('limit') : null;

        $groups = Group::query()
            ->orderBy('id')
            ->get();

        if (! $force) {
            $groups = $groups->filter(fn (Group $group) => $summaryService->needsRegeneration($group));
        }

        if ($limit !== null) {
            $groups = $groups->take($limit);
        }

        $total = $groups->count();

        if ($total === 0) {
            $this->info('No groups need search summary generation.');

            return Command::SUCCESS;
        }

        $this->info("Generating search summaries for up to {$total} group(s)...");

        $processed = 0;
        $succeeded = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($groups as $group) {
            $processed++;

            if ($summaryService->projectsQuery($group)->doesntExist()) {
                $skipped++;
                $this->line("  - Group #{$group->id}: {$group->name} (no published projects, skipped)");

                continue;
            }

            try {
                $summary = $summaryService->generateFor($group);
                if ($summary === null) {
                    $skipped++;
                    $this->line("  - Group #{$group->id}: {$group->name} (no summary produced)");

                    continue;
                }

                $succeeded++;
                $this->line("  ✓ Group #{$group->id}: {$group->name}");
            } catch (Throwable $e) {
                $failed++;
                $this->error("  ✗ Group #{$group->id}: {$e->getMessage()}");
            }
        }

        $this->newLine();
        $this->info("Done. Processed {$processed}, generated {$succeeded}, skipped {$skipped}, failed {$failed}.");

        return $failed > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}
