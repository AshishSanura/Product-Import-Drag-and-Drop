<?php
namespace Ashish\UserDiscounts\Models;

use Illuminate\Database\Eloquent\Model;

class DiscountAudit extends Model
{
    protected $table = 'discount_audits';
    protected $fillable = ['discount_id','user_id','action','meta'];
    protected $casts = ['meta'=>'array'];
}
