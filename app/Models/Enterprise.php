<?php

namespace App\Models;

use Database\Factories\EnterpriseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * App\Models\Enterprise
 *
 * @property int                                 $id
 * @property string                              $name
 * @property array                               $settings
 * @property Carbon|null     $created_at
 * @property Carbon|null     $updated_at
 * @property-read Collection|EnterpriseAccount[] $enterpriseAccounts
 * @property-read int|null                       $enterprise_accounts_count
 * @property-read FloatAccount|null              $floatAccount
 * @property-read Collection|Voucher[]           $vouchers
 * @property-read int|null                       $vouchers_count
 * @method static EnterpriseFactory factory(...$parameters)
 * @method static Builder|Enterprise newModelQuery()
 * @method static Builder|Enterprise newQuery()
 * @method static Builder|Enterprise query()
 * @method static Builder|Enterprise whereCreatedAt($value)
 * @method static Builder|Enterprise whereId($value)
 * @method static Builder|Enterprise whereName($value)
 * @method static Builder|Enterprise whereSettings($value)
 * @method static Builder|Enterprise whereUpdatedAt($value)
 * @mixin IdeHelperEnterprise
 */
class Enterprise extends Model
{
    use HasFactory;

    protected $fillable = [
        "name",
        "settings"
    ];

    protected $casts = [
        'settings' => 'array'
    ];


    protected function maxLunch(): Attribute
    {
        return new Attribute(
            get: function($value, $attributes) {
                $settings = json_decode($attributes['settings'], true);
                $setting = collect($settings)->firstWhere('type', 'lunch');
                return $setting['max'] ?? null;
            },
        );
    }

    protected function maxGeneral(): Attribute
    {
        return new Attribute(
            get: function($value, $attributes) {
                $settings = json_decode($attributes['settings'], true);
                $setting = collect($settings)->firstWhere('type', 'general');
                return $setting['max'] ?? null;
            },
        );
    }


    /**
     * ---------------------------------------- Relationships ----------------------------------------
     */
    public function enterpriseAccounts(): HasMany
    {
        return $this->hasMany(EnterpriseAccount::class);
    }
}
