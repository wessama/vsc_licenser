<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property mixed id
 * @property mixed ticket_id
 * @property int|mixed|null exit_code
 * @property mixed|string message
 * @property array|mixed|string last_run_cmd
 * @property float|mixed started_at
 * @property float|mixed ended_at
 */
class Log extends Model
{
    use SoftDeletes;

    public $table = 'logs';

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $fillable = [
        'exit_code',
        'message',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function tickets()
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }
}
