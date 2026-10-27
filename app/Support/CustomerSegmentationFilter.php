<?php

namespace App\Support;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class CustomerSegmentationFilter
{
    public static function ids(): array
    {
        $user = auth()->user();

        if (!$user) {
            return [];
        }

        return $user
            ->loadMissing('segmentation.segmentation')
            ->segmentation
            ->pluck('segmentation.id')
            ->filter(fn ($id) => $id !== null)
            ->unique()
            ->values()
            ->all();
    }

    public static function apply(
        Builder $query,
        string $customerAlias = 'c'
    ): Builder {
        $segmentationIds = self::ids();

        return $query->whereIn(
            "{$customerAlias}.segmentation_id",
            $segmentationIds
        );
    }
}