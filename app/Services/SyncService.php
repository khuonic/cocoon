<?php

namespace App\Services;

use App\Enums\SyncAction;
use App\Models\Birthday;
use App\Models\Bookmark;
use App\Models\Expense;
use App\Models\MealIdea;
use App\Models\Note;
use App\Models\Recipe;
use App\Models\ShoppingItem;
use App\Models\ShoppingList;
use App\Models\SweetMessage;
use App\Models\SyncLog;
use App\Models\Todo;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class SyncService
{
    /**
     * Mapping from sync type strings to model classes.
     *
     * @var array<string, class-string<Model>>
     */
    private const MODEL_MAP = [
        'expenses' => Expense::class,
        'shopping_lists' => ShoppingList::class,
        'shopping_items' => ShoppingItem::class,
        'todos' => Todo::class,
        'meal_ideas' => MealIdea::class,
        'recipes' => Recipe::class,
        'notes' => Note::class,
        'bookmarks' => Bookmark::class,
        'sweet_messages' => SweetMessage::class,
        'birthdays' => Birthday::class,
    ];

    /**
     * Apply a single change from a remote device.
     * Returns true if the change was applied, false if rejected (last-write-wins).
     */
    public function applyChange(string $type, string $uuid, SyncAction $action, ?array $data, Carbon $updatedAt): bool
    {
        $modelClass = $this->getModelClass($type);

        if (! $modelClass) {
            return false;
        }

        return match ($action) {
            SyncAction::Created => $this->applyCreate($modelClass, $uuid, $data, $updatedAt),
            SyncAction::Updated => $this->applyUpdate($modelClass, $uuid, $data, $updatedAt),
            SyncAction::Deleted => $this->applyDelete($modelClass, $uuid),
        };
    }

    /**
     * Get all changes since a given timestamp, optionally excluding a device.
     *
     * @return Collection<int, array{type: string, uuid: string, action: string, data: ?array, updated_at: string}>
     */
    public function getChangesSince(Carbon $since, ?string $excludeDeviceId = null): Collection
    {
        $changes = collect();

        foreach (self::MODEL_MAP as $type => $modelClass) {
            /** @var Model $instance */
            $instance = new $modelClass;
            $table = $instance->getTable();

            $records = $modelClass::query()
                ->where('updated_at', '>', $since)
                ->get();

            foreach ($records as $record) {
                $changes->push([
                    'type' => $type,
                    'uuid' => $record->uuid,
                    'action' => SyncAction::Updated->value,
                    'data' => $this->buildPayload($type, $record),
                    'updated_at' => $record->updated_at->toIso8601String(),
                ]);
            }
        }

        // Also include deletes from sync_logs since that timestamp
        $deleteLogs = SyncLog::query()
            ->where('action', SyncAction::Deleted)
            ->where('created_at', '>', $since)
            ->get();

        foreach ($deleteLogs as $log) {
            $type = $this->getTypeFromMorphClass($log->syncable_type);

            if ($type) {
                $changes->push([
                    'type' => $type,
                    'uuid' => $log->syncable_uuid,
                    'action' => SyncAction::Deleted->value,
                    'data' => null,
                    'updated_at' => $log->created_at->toIso8601String(),
                ]);
            }
        }

        return $changes;
    }

    /**
     * Get all data for a full sync.
     *
     * @return Collection<int, array{type: string, uuid: string, action: string, data: ?array, updated_at: string}>
     */
    public function getAllData(): Collection
    {
        $changes = collect();

        foreach (self::MODEL_MAP as $type => $modelClass) {
            $records = $modelClass::all();

            foreach ($records as $record) {
                $changes->push([
                    'type' => $type,
                    'uuid' => $record->uuid,
                    'action' => SyncAction::Created->value,
                    'data' => $this->buildPayload($type, $record),
                    'updated_at' => $record->updated_at->toIso8601String(),
                ]);
            }
        }

        return $changes;
    }

    /**
     * @return class-string<Model>|null
     */
    public function getModelClass(string $type): ?string
    {
        return self::MODEL_MAP[$type] ?? null;
    }

    /**
     * Build the sync payload for a record.
     * For recipes, includes ingredients and steps.
     *
     * @return array<string, mixed>
     */
    private function buildPayload(string $type, Model $record): array
    {
        $payload = $record->toArray();

        if ($type === 'recipes') {
            /** @var Recipe $record */
            $payload['ingredients'] = $record->ingredients->toArray();
            $payload['steps'] = $record->steps->toArray();
        }

        return $payload;
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    private function applyCreate(string $modelClass, string $uuid, ?array $data, Carbon $updatedAt): bool
    {
        if (! $data) {
            return false;
        }

        $existing = $modelClass::query()->where('uuid', $uuid)->first();

        if ($existing) {
            // Already exists — treat as update
            return $this->applyUpdate($modelClass, $uuid, $data, $updatedAt);
        }

        $fillable = (new $modelClass)->getFillable();
        $attributes = array_intersect_key($data, array_flip($fillable));

        /** @var Model $model */
        $model = new $modelClass;
        $model->isSyncing = true;
        $model->fill($attributes);
        $model->save();

        // Handle recipe ingredients and steps
        if ($modelClass === Recipe::class) {
            $this->syncRecipeChildren($model, $data);
        }

        return true;
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    private function applyUpdate(string $modelClass, string $uuid, ?array $data, Carbon $updatedAt): bool
    {
        if (! $data) {
            return false;
        }

        $existing = $modelClass::query()->where('uuid', $uuid)->first();

        if (! $existing) {
            // Doesn't exist — treat as create
            return $this->applyCreate($modelClass, $uuid, $data, $updatedAt);
        }

        // Last-write-wins: reject if remote change is older
        if ($existing->updated_at && $existing->updated_at->greaterThan($updatedAt)) {
            return false;
        }

        $fillable = $existing->getFillable();
        $attributes = array_intersect_key($data, array_flip($fillable));

        $existing->isSyncing = true;
        $existing->fill($attributes);
        $existing->save();

        if ($modelClass === Recipe::class) {
            $this->syncRecipeChildren($existing, $data);
        }

        return true;
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    private function applyDelete(string $modelClass, string $uuid): bool
    {
        $existing = $modelClass::query()->where('uuid', $uuid)->first();

        if (! $existing) {
            return false;
        }

        $existing->isSyncing = true;
        $existing->delete();

        return true;
    }

    /**
     * Sync recipe ingredients and steps from payload.
     */
    private function syncRecipeChildren(Model $recipe, array $data): void
    {
        if (isset($data['ingredients'])) {
            $recipe->ingredients()->delete();

            foreach ($data['ingredients'] as $ingredient) {
                $recipe->ingredients()->create([
                    'name' => $ingredient['name'] ?? '',
                    'quantity' => $ingredient['quantity'] ?? null,
                    'unit' => $ingredient['unit'] ?? null,
                    'sort_order' => $ingredient['sort_order'] ?? 0,
                ]);
            }
        }

        if (isset($data['steps'])) {
            $recipe->steps()->delete();

            foreach ($data['steps'] as $step) {
                $recipe->steps()->create([
                    'instruction' => $step['instruction'] ?? '',
                    'sort_order' => $step['sort_order'] ?? 0,
                ]);
            }
        }
    }

    /**
     * Get the sync type string from a morph class.
     */
    private function getTypeFromMorphClass(string $morphClass): ?string
    {
        $flipped = array_flip(self::MODEL_MAP);

        return $flipped[$morphClass] ?? null;
    }
}
