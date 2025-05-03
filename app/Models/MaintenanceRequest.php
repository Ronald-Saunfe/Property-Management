<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class MaintenanceRequest
 * 
 * @property int $id
 * @property int $unit_id
 * @property int|null $tenant_id
 * @property string $title
 * @property string $description
 * @property string $priority
 * @property string $status
 * @property Carbon $reported_date
 * @property Carbon|null $completed_date
 * @property int|null $assigned_to
 * @property float|null $cost
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Unit $unit
 * @property Tenant|null $tenant
 * @property User|null $user
 *
 * @package App\Models
 */
class MaintenanceRequest extends Model
{
	protected $table = 'maintenance_requests';

	protected $casts = [
		'unit_id' => 'int',
		'tenant_id' => 'int',
		'reported_date' => 'datetime',
		'completed_date' => 'datetime',
		'assigned_to' => 'int',
		'cost' => 'float'
	];

	protected $fillable = [
		'unit_id',
		'tenant_id',
		'title',
		'description',
		'priority',
		'status',
		'reported_date',
		'completed_date',
		'assigned_to',
		'cost'
	];

	public function unit()
	{
		return $this->belongsTo(Unit::class);
	}

	public function tenant()
	{
		return $this->belongsTo(Tenant::class);
	}

	public function user()
	{
		return $this->belongsTo(User::class, 'assigned_to');
	}
}
