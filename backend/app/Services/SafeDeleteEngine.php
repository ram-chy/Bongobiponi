<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use ReflectionMethod;
use ReflectionNamedType;

class SafeDeleteEngine
{
    private const SKIP_RELATIONSHIPS = ['creator', 'updater'];

    /**
     * Attempt a safe delete: check references, check latest record, then delete.
     */
    public function delete(Model $model): SafeDeleteResult
    {
        $modelClass = class_basename($model);
        $display_name = $this->getModelDisplayName($modelClass);

        // Rule 1: Relationship protection
        $referenceCheck = $this->checkReferences($model, $display_name);
        if ($referenceCheck !== null) {
            return $referenceCheck;
        }

        // Rule 2: Latest record protection
        $latestCheck = $this->checkLatestRecord($model, $display_name);
        if ($latestCheck !== null) {
            return $latestCheck;
        }

        // Rule 3: All checks passed — proceed with delete
        $model->delete();

        return SafeDeleteResult::deleted("{$display_name} deleted successfully.");
    }

    /**
     * Auto-discover blocking relationships via reflection and check for references.
     */
    private function checkReferences(Model $model, string $display_name): ?SafeDeleteResult
    {
        foreach ($this->discoverBlockingRelationships($model) as [$relationName, $relation]) {
            $count = $relation->withoutGlobalScopes()->count();

            if ($count > 0) {
                $sample_records = $this->findSampleRecords($relation);

                $module_name = $this->humanizeModuleName($relation->getRelated());

                return SafeDeleteResult::blocked(
                    message: "Unable to delete {$display_name}.",
                    reason: "This " . strtolower($display_name) . " is referenced by existing " . strtolower($module_name) . ".",
                    referenceModule: $module_name,
                    referenceCount: $count,
                    sampleRecords: $sample_records,
                );
            }
        }

        return null;
    }

    /**
     * Determine if this is the latest (most recent) record.
     * Only the highest-ID non-deleted record may be deleted.
     */
    private function checkLatestRecord(Model $model, string $display_name): ?SafeDeleteResult
    {
        $latestId = $model->newQuery()
            ->withoutGlobalScopes()
            ->latest('id')
            ->value('id');

        if ($latestId !== null && $latestId !== $model->getKey()) {
            return SafeDeleteResult::blockedByLatestRecord(
                message: "Unable to delete {$display_name}.",
                reason: "Newer records exist. Delete the most recently created record first.",
                latestRecordId: (int) $latestId,
            );
        }

        return null;
    }

    /**
     * Use reflection to discover all blocking relationship methods on the model.
     * Returns array of [methodName, relationInstance] pairs.
     */
    private function discoverBlockingRelationships(Model $model): array
    {
        $relations = [];

        foreach (get_class_methods($model) as $methodName) {
            if (in_array($methodName, self::SKIP_RELATIONSHIPS, true)) {
                continue;
            }

            $reflection = new ReflectionMethod($model, $methodName);

            // Skip methods with parameters, static methods, and getter-style methods
            if (
                $reflection->getNumberOfParameters() > 0
                || $reflection->isStatic()
                || str_starts_with($methodName, 'get')
            ) {
                continue;
            }

            // Check return type via reflection
            $returnType = $reflection->getReturnType();

            if (
                $returnType instanceof ReflectionNamedType
                && is_a($returnType->getName(), Relation::class, true)
                && ! $returnType->allowsNull()
            ) {
                try {
                    $relation = $model->{$methodName}();

                    if ($this->isBlockingRelationship($relation)) {
                        $relations[] = [$methodName, $relation];
                    }
                } catch (\Throwable) {
                    // Method requires dependencies or throws — skip
                }
            }
        }

        return $relations;
    }

    /**
     * Check if a relationship instance is a blocking type (HasOne, HasMany, BelongsToMany, MorphMany).
     */
    private function isBlockingRelationship(mixed $relation): bool
    {
        return $relation instanceof HasOne
            || $relation instanceof HasMany
            || $relation instanceof BelongsToMany
            || $relation instanceof MorphMany;
    }

    /**
     * Fetch sample record names/labels for the blocking relationship.
     */
    private function findSampleRecords(mixed $relation): array
    {
        return $relation->withoutGlobalScopes()
            ->limit(3)
            ->pluck($this->getDisplayNameColumn($relation->getRelated()))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Get the displayable name column for a model (title, name, etc.).
     */
    private function getDisplayNameColumn(Model $model): string
    {
        return match (class_basename($model)) {
            'Book' => 'title',
            default => 'name',
        };
    }

    /**
     * Convert a model class name to a human-readable module name.
     * E.g., "ReceiveOrder" → "Receive Orders", "Book" → "Books".
     */
    private function humanizeModuleName(Model $model): string
    {
        $class_name = class_basename($model);

        $words = preg_replace('/([A-Z])/', ' $1', $class_name);
        $words = trim($words);
        $words = ucwords($words);

        return $words . 's';
    }

    /**
     * Convert a model class name to a human-readable display name.
     * E.g., "Publisher" → "Publisher", "ReceiveOrder" → "Receive Order".
     */
    private function getModelDisplayName(string $modelClass): string
    {
        $words = preg_replace('/([A-Z])/', ' $1', $modelClass);

        return trim($words);
    }
}
