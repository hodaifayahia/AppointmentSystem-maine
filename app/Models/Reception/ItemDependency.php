<?php

namespace App\Models\Reception;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\CONFIGURATION\Prestation;

class ItemDependency extends Model
{
    protected $fillable = [
        'parent_item_id',
        'dependent_item_id',
        'dependency_type',
        'convenction_id',
        'dependency_prestation_id', // This stores the prestation ID directly
        'notes',
    ];

    /**
     * Dependency types
     */
    const TYPE_REQUIRED = 'required';        // Required dependencies for prestations
    const TYPE_CUSTOM_GROUP = 'custom_group'; // Custom prestation grouping
    const TYPE_PACKAGE = 'package';          // Package items

    /**
     * Get the parent item (main prestation in fiche_navette_items)
     */
    public function parentItem(): BelongsTo
    {
        return $this->belongsTo(ficheNavetteItem::class, 'parent_item_id');
    }

    /**
     * Get the dependent item (if exists - for backward compatibility)
     */
    public function dependentItem(): BelongsTo
    {
        return $this->belongsTo(ficheNavetteItem::class, 'dependent_item_id');
    }

    /**
     * Get the dependency prestation directly (THIS IS WHAT STORES THE DEPENDENCIES)
     */
    public function dependencyPrestation(): BelongsTo
    {
        return $this->belongsTo(Prestation::class, 'dependency_prestation_id');
    }
}
