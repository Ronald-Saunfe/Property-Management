<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens; // Add this import
use Illuminate\Foundation\Auth\User as Authenticatable; // Add this import
use Illuminate\Database\Eloquent\Factories\HasFactory; 
use Illuminate\Notifications\Notifiable; 
 

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
 


class User extends Authenticatable
{ 

	use HasApiTokens, HasFactory, Notifiable;


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


	public function properties()
	{
		return $this->hasMany(Property::class);
	}

	public function property_managers()
	{
		return $this->hasMany(PropertyManager::class);
	}
}
