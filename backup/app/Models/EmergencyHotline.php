<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmergencyHotline extends Model
{
    protected $table = 'emergency_hotlines';
    public $timestamps = false;

    protected $fillable = [
        'name',
        'number',
        'description',
        'barangay',
        'cityMunicipality',
        'createdAt',
    ];
}
