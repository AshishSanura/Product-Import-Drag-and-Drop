<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Upload extends Model
{
	protected $fillable = [
		'uuid',
		'filename',
		'checksum',
		'total_chunks',
		'uploaded_chunks',
		'size',
		'mimetype',
		'completed',
		'entity_type',
		'entity_id',
	];

	protected $casts = [
        'completed' => 'boolean',
    ];
}