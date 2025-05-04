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
 * Class Lease
 * 
 * @property int $id
 * @property int $unit_id
 * @property int $tenant_id
 * @property Carbon $start_date
 * @property Carbon $end_date
 * @property float $monthly_rent
 * @property float $security_deposit
 * @property string $lease_type
 * @property int $payment_day
 * @property string $status
 * @property string|null $document_path
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Unit $unit
 * @property Tenant $tenant
 * @property Collection|Tenant[] $tenants
 * @property Collection|Payment[] $payments
 *
 * @package App\Models
 */
class Lease extends Model
{
	
	use HasFactory;

	protected $table = 'leases';

	protected $casts = [
		'unit_id' => 'int',
		'tenant_id' => 'int',
		'start_date' => 'datetime',
		'end_date' => 'datetime',
		'monthly_rent' => 'float',
		'security_deposit' => 'float',
		'payment_day' => 'int'
	];

	protected $fillable = [
		'unit_id',
		'tenant_id',
		'start_date',
		'end_date',
		'monthly_rent',
		'security_deposit',
		'lease_type',
		'payment_day',
		'status',
		'document_path',
		'notes'
	];

	public function unit()
	{
		return $this->belongsTo(Unit::class);
	}

	public function tenant()
	{
		return $this->belongsTo(Tenant::class);
	}

	public function tenants()
	{
		return $this->belongsToMany(Tenant::class, 'lease_tenants')
					->withPivot('id', 'is_primary')
					->withTimestamps();
	}

	public function payments()
	{
		return $this->hasMany(Payment::class);
	}
}
