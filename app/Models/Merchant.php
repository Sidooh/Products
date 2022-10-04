<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Merchant
 *
 * @property int $id
 * @property string $name
 * @property string $code
 * @property string $contact_name
 * @property string $contact_phone
 * @property string $balance
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Database\Factories\MerchantFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|Merchant newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Merchant newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Merchant query()
 * @method static \Illuminate\Database\Eloquent\Builder|Merchant whereBalance($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Merchant whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Merchant whereContactName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Merchant whereContactPhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Merchant whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Merchant whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Merchant whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Merchant whereUpdatedAt($value)
 * @mixin IdeHelperMerchant
 */
class Merchant extends Model
{
    use HasFactory;
}
