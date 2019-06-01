<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    protected $table = 'clientes';
    protected $primaryKey = 'id';  
    public $timestamps = false;
    
    protected $fillable = [
        'id',		
        'sender',
        'primer_nombre',
        'segundo_nombre',
        'estado'
    ];   
}