<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Tenant
 * 
 * @property int $id
 * @property string $first_name
 * @property string $last_name
 * @property string $email
 * @property string $phone
 * @property Carbon|null $date_of_birth
 * @property string|null $emergency_contact_name
 * @property string|null $emergency_contact_phone
 * @property string|null $occupation
 * @property float|null $income
 * @property int|null $credit_score
 * @property string $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Collection|Lease[] $leases
 * @property Collection|MaintenanceRequest[] $maintenance_requests
 *
 * @package App\Models
 */
class Tenant extends Model
{
	protected $table = 'tenants';

	protected $casts = [
		'date_of_birth' => 'datetime',
		'income' => 'float',
		'credit_score' => 'int'
	];

	protected $fillable = [
		'first_name',
		'last_name',
		'email',
		'phone',
		'date_of_birth',
		'emergency_contact_name',
		'emergency_contact_phone',
		'occupation',
		'income',
		'credit_score',
		'status'
	];

	public function leases()
	{
		return $this->hasMany(Lease::class);
	}

	public function maintenance_requests()
	{
		return $this->hasMany(MaintenanceRequest::class);
	}
}
