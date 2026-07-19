<?php

declare(strict_types=1);

namespace Kurt\Modules\Loyalty\Models;

use Illuminate\Database\Eloquent\Model;

class WalletRegistration extends Model
{
    protected $table = 'loyalty_wallet_registrations';

    protected $guarded = [];
}
