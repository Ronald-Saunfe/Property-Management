<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Unit
 * 
 * @property int $id
 * @property int $property_id
 * @property string $unit_number
 * @property string|null $floor_plan
 * @property float|null $square_feet
 * @property int|null $bedrooms
 * @property float|null $bathrooms
 * @property float $monthly_rent
 * @property string $status
 * @property string|null $features
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Property $property
 * @property Collection|Lease[] $leases
 * @property Collection|MaintenanceRequest[] $maintenance_requests
 *
 * @package App\Models
 */
class Unit extends Model
{
	use HasFactory;
	
	protected $table = 'units';

	protected $casts = [
		'property_id' => 'int',
		'square_feet' => 'float',
		'bedrooms' => 'int',
		'bathrooms' => 'float',
		'monthly_rent' => 'float'
	];

	protected $fillable = [
		'property_id',
		'unit_number',
		'floor_plan',
		'square_feet',
		'bedrooms',
		'bathrooms',
		'monthly_rent',
		'status',
		'features'
	];

	public function property()
	{
		return $this->belongsTo(Property::class);
	}

	public function leases()
	{
		return $this->hasMany(Lease::class);
	}
	
}
