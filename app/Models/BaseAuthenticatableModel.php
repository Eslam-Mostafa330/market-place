<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Foundation\Auth\User as Authenticatable;

abstract class BaseAuthenticatableModel extends Authenticatable
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;
}