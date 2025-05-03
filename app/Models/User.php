<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class User
 * 
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string $role
 * @property string|null $phone
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Collection|Document[] $documents
 * @property Collection|MaintenanceRequest[] $maintenance_requests
 * @property Collection|Notification[] $notifications
 * @property Collection|Property[] $properties
 * @property Collection|PropertyManager[] $property_managers
 *
 * @package App\Models
 */
class User extends Model
{
	protected $table = 'users';

	protected $casts = [
		'email_verified_at' => 'datetime'
	];

	protected $hidden = [
		'password',
		'remember_token'
	];

	protected $fillable = [
		'name',
		'email',
		'email_verified_at',
		'password',
		'role',
		'phone',
		'remember_token'
	];

	public function documents()
	{
		return $this->hasMany(Document::class, 'uploaded_by');
	}

	public function maintenance_requests()
	{
		return $this->hasMany(MaintenanceRequest::class, 'assigned_to');
	}

	public function notifications()
	{
		return $this->hasMany(Notification::class);
	}

	public function properties()
	{
		return $this->hasMany(Property::class);
	}

	public function property_managers()
	{
		return $this->hasMany(PropertyManager::class);
	}
}
