<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class LeaseTenant
 * 
 * @property int $id
 * @property int $lease_id
 * @property int $tenant_id
 * @property bool $is_primary
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Lease $lease
 * @property Tenant $tenant
 *
 * @package App\Models
 */
class LeaseTenant extends Model
{
	protected $table = 'lease_tenants';

	protected $casts = [
		'lease_id' => 'int',
		'tenant_id' => 'int',
		'is_primary' => 'bool'
	];

	protected $fillable = [
		'lease_id',
		'tenant_id',
		'is_primary'
	];

	public function lease()
	{
		return $this->belongsTo(Lease::class);
	}

	public function tenant()
	{
		return $this->belongsTo(Tenant::class);
	}
}
