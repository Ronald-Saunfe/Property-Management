<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Document
 * 
 * @property int $id
 * @property int $documentable_id
 * @property string $documentable_type
 * @property string $name
 * @property string $file_path
 * @property string $file_type
 * @property int $file_size
 * @property int $uploaded_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property User $user
 *
 * @package App\Models
 */
class Document extends Model
{
	protected $table = 'documents';

	protected $casts = [
		'documentable_id' => 'int',
		'file_size' => 'int',
		'uploaded_by' => 'int'
	];

	protected $fillable = [
		'documentable_id',
		'documentable_type',
		'name',
		'file_path',
		'file_type',
		'file_size',
		'uploaded_by'
	];

	public function user()
	{
		return $this->belongsTo(User::class, 'uploaded_by');
	}
}
