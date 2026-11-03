<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['code', 'name', 'description', 'capabilities', 'is_active', 'sort_order'])]
class Chair extends Model
{
    protected function casts(): array
    {
        return [
            'capabilities' => 'array',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @param  list<string>  $required
     */
    public function supportsCapabilities(array $required): bool
    {
        if ($required === []) {
            return true;
        }

        $available = $this->capabilities ?? [];

        foreach ($required as $capability) {
            if (! in_array($capability, $available, true)) {
                return false;
            }
        }

        return true;
    }
}
