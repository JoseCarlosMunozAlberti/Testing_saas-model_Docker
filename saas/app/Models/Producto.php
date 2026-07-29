<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use App\Traits\Multitenant;

class Producto extends Model
{
    use HasFactory, Multitenant, SoftDeletes;

    protected $table = 'productos';

    protected $fillable = [
        'nombre',
        'precio',
        'stock',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'precio' => 'decimal:2',
            'stock' => 'integer',
            'deleted_at' => 'datetime',
        ];
    }
}
