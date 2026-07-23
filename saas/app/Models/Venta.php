<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Traits\Multitenant;

class Venta extends Model
{
    use HasFactory, Multitenant;

    protected $table = 'ventas';

    protected $fillable = [
        'cliente_id',
        'usuario_id',
        'total',
        'estado',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'total' => 'decimal:2',
        ];
    }
}
