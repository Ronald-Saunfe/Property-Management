<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Class Property
 * 
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string $address
 * @property string $city
 * @property string $state
 * @property string $zip_code
 * @property string $property_type
 * @property Carbon|null $year_built
 * @property string|null $description
 * @property string $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property User $user
 * @property Collection|PropertyManager[] $property_managers
 * @property Collection|Unit[] $units
 *
 * @package App\Models
 */
class Property extends Model
{
	
	use HasFactory;

	protected $table = 'properties';

	protected $casts = [
		'user_id' => 'int',
		'year_built' => 'datetime'
	];

	protected $fillable = [
		'user_id',
		'name',
		'address',
		'city',
		'state',
		'zip_code',
		'property_type',
		'year_built',
		'description',
		'status'
	];

	public function user()
	{
		return $this->belongsTo(User::class);
	}

	public function property_managers()
	{
		return $this->hasMany(PropertyManager::class);
	}

	public function units()
	{
		return $this->hasMany(Unit::class);
	}
}
