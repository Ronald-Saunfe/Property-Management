<?php

/**
 * Created by Reliese Model.
 */

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Class Payment
 * 
 * @property int $id
 * @property int $lease_id
 * @property float $amount
 * @property Carbon $due_date
 * @property Carbon|null $payment_date
 * @property string|null $payment_method
 * @property string|null $transaction_id
 * @property string $status
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * 
 * @property Lease $lease
 *
 * @package App\Models
 */
class Payment extends Model
{
	
	use HasFactory;

	protected $table = 'payments';

	protected $casts = [
		'lease_id' => 'int',
		'amount' => 'float',
		'due_date' => 'datetime',
		'payment_date' => 'datetime'
	];

	protected $fillable = [
		'lease_id',
		'amount',
		'due_date',
		'payment_date',
		'payment_method',
		'transaction_id',
		'status',
		'notes'
	];

	public function lease()
	{
		return $this->belongsTo(Lease::class);
	}
}
