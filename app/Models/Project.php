<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Project extends Model
{
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty()
            ->useLogName('Project')
            ->setDescriptionForEvent(fn(string $eventName) => "Project has been {$eventName}");;
    }

    public function user()
    {
    	return $this->belongsTo(User::class,'user_id','id');
    }

    public function hindrances()
    {
        return $this->hasMany(Hindrance::class,'project_id','id');
    }
}
