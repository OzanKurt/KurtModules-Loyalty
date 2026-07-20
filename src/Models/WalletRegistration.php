<?php

declare(strict_types=1);

namespace Kurt\Modules\Loyalty\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $device_library_id
 * @property string $push_token
 * @property string $pass_serial
 */
class WalletRegistration extends Model
{
    protected $table = 'loyalty_wallet_registrations';

    protected $guarded = [];
}
