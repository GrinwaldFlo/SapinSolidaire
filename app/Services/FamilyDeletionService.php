<?php

namespace App\Services;

use App\Models\EmailToken;
use App\Models\Family;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class FamilyDeletionService
{
    /**
     * Permanently delete a family with all its gift requests, children,
     * access tokens and proof of habitation files.
     *
     * @return array<int, string> Stored file paths that could not be deleted
     */
    public function delete(Family $family): array
    {
        // Collect file paths before deleting DB rows that reference them
        $proofPaths = $family->giftRequests()
            ->whereNotNull('proof_of_habitation_path')
            ->pluck('proof_of_habitation_path');

        DB::transaction(function () use ($family): void {
            EmailToken::where('email', $family->email)->delete();

            // Children and gift requests are removed by cascading foreign keys
            $family->delete();
        });

        return self::deleteLocalFiles($proofPaths);
    }

    /**
     * Delete files from the local disk.
     *
     * @param  iterable<int, string>  $paths
     * @return array<int, string> Paths that could not be deleted
     */
    public static function deleteLocalFiles(iterable $paths): array
    {
        $failed = [];

        foreach ($paths as $path) {
            if (! Storage::disk('local')->delete($path)) {
                $failed[] = $path;
            }
        }

        return $failed;
    }
}
