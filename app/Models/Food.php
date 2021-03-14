<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Food extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name', 'description', 'ingredients',
        'price', 'rate', 'types','picturesPath'
    ];
    
    public function getCreatedAtAttribute($value)
    {
        return Carbon::parse($value)->timestamp;
    }

    public function getUpdatedAtAttribute($value)
    {
        return Carbon::parse($value)->timestamp;
    }

    public function toArray()
    {
        $toArray = parent::toArray();
        $toArray['picturesPath'] = $this->picturesPath;
        return $toArray;
    }

    public function getPicturepathAttribute()
    {
        return url('') . Storage::url($this->attributes['picturesPath']);
    }

}
