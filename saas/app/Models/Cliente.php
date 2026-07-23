<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Traits\Multitenant;

class Cliente extends Model
{
    use HasFactory, Multitenant;

    protected $table = 'clientes';

    protected $fillable = [
        'nombre',
        'ci',
        'telefono',
    ];
}
