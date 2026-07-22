<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class SerialGeneratorService
{
    public function __construct(
        private readonly string $prefix,
        private readonly string $modelClass,
        private readonly string $column,
    ) {}

    public function generate(): string
    {
        $yearSuffix = now()->format('y');
        $pattern = "{$this->prefix}/%/{$yearSuffix}";

        /** @var Model $model */
        $model = new $this->modelClass;

        $lastSerial = $model->newQuery()
            ->withoutGlobalScopes()
            ->withTrashed()
            ->where($this->column, 'like', $pattern)
            ->lockForUpdate()
            ->orderBy('id', 'desc')
            ->value($this->column);

        if ($lastSerial) {
            $parts = explode('/', $lastSerial);
            $lastNumber = (int) $parts[1];
            $newNumber = str_pad((string) ($lastNumber + 1), 3, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '001';
        }

        return "{$this->prefix}/{$newNumber}/{$yearSuffix}";
    }
}
