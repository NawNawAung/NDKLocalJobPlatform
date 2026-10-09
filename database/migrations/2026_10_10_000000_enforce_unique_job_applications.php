<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const UNIQUE_INDEX = 'applications_job_job_seeker_unique';
    private const LEGACY_INDEX = 'applications_job_id_job_seeker_id_index';

    public function up(): void
    {
        if (! Schema::hasTable('applications')) {
            return;
        }

        $indexes = Schema::getIndexes('applications');
        $hasUniquePair = collect($indexes)->contains(fn (array $index) =>
            ($index['unique'] ?? false)
            && ($index['columns'] ?? []) === ['job_id', 'job_seeker_id']
        );

        if (! $hasUniquePair) {
            $duplicatesExist = DB::table('applications')
                ->select('job_id', 'job_seeker_id')
                ->groupBy('job_id', 'job_seeker_id')
                ->havingRaw('COUNT(*) > 1')
                ->exists();

            if ($duplicatesExist) {
                throw new \RuntimeException(
                    'Cannot enforce one application per job seeker and job: duplicate application groups exist. Review and resolve them without deleting history, then retry this migration.'
                );
            }

            Schema::table('applications', function (Blueprint $table) {
                $table->unique(['job_id', 'job_seeker_id'], self::UNIQUE_INDEX);
            });
        }

        // The new unique index covers the old composite lookup index. Remove
        // the redundant index only after the uniqueness constraint exists.
        $indexes = Schema::getIndexes('applications');
        $legacy = collect($indexes)->firstWhere('name', self::LEGACY_INDEX);
        if ($legacy && ! ($legacy['unique'] ?? false)) {
            Schema::table('applications', function (Blueprint $table) {
                $table->dropIndex(self::LEGACY_INDEX);
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('applications')) {
            return;
        }

        $indexes = Schema::getIndexes('applications');
        $unique = collect($indexes)->firstWhere('name', self::UNIQUE_INDEX);
        if ($unique) {
            Schema::table('applications', function (Blueprint $table) {
                $table->dropUnique(self::UNIQUE_INDEX);
            });
        }

        $indexes = Schema::getIndexes('applications');
        $legacyExists = collect($indexes)->contains(fn (array $index) =>
            ($index['name'] ?? null) === self::LEGACY_INDEX
            || (($index['unique'] ?? false) === false && ($index['columns'] ?? []) === ['job_id', 'job_seeker_id'])
        );
        if (! $legacyExists) {
            Schema::table('applications', function (Blueprint $table) {
                $table->index(['job_id', 'job_seeker_id'], self::LEGACY_INDEX);
            });
        }
    }
};
