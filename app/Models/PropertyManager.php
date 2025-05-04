<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Class PropertyManager
 * 
 * @property int $id
 * @property int $property_id
 * @property int $user_id
 * @property bool $is_primary
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Property $property
 * @property User $user
 *
 * @package App\Models
 */
class PropertyManager extends Model
{
	
	use HasFactory;

	protected $table = 'property_managers';

	protected $casts = [
		'property_id' => 'int',
		'user_id' => 'int',
		'is_primary' => 'bool'
	];

	protected $fillable = [
		'property_id',
		'user_id',
		'is_primary'
	];

	public function property()
	{
		return $this->belongsTo(Property::class);
	}

	public function user()
	{
		return $this->belongsTo(User::class);
	}
}
