<?php

namespace Modules\Essentials\Entities;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class EssentialsLoan extends Model
{
    use LogsActivity;

    protected static $logAttributes = ['*'];

    protected static $logAttributesToIgnore = ['created_at', 'updated_at'];

    protected static $logOnlyDirty = true;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty();
    }
    
    public function user()
    {
        return $this->belongsTo(\App\User::class);
    }

    public function approved_by_user()
    {
        return $this->belongsTo(\App\User::class, 'approved_by');
    }
}

