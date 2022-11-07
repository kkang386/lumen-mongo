<?php

namespace App\Models;

// use Illuminate\Database\Eloquent\Model;
use Jenssegers\Mongodb\Eloquent\Model;

class Person extends Model
{
    // protected $connection = 'mongodb';
    protected $collection = 'person';

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'name', 
        'birth_date',
        'timezone'
    ];    
}
