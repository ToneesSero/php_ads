<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ListingImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'listing_id',
        'image_path',
        'is_main',
    ];

    protected $casts = [
        'is_main' => 'boolean',
    ];

    public function listing()
    {
        return $this->belongsTo(Listing::class);
    }
}
