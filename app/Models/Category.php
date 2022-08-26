<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    public function scopeOnlyParent($query)
    {
        return $query->whereNull('parent_id');
    }

    public function childern()
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function descendants()
    {
        return $this->childern()->with('descendants');
    }
}
