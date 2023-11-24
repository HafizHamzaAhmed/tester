<?php

namespace App\Services;

use App\Exceptions\AffiliateCreateException;
use App\Mail\AffiliateCreated;
use App\Models\Affiliate;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class AffiliateService
{
    public function __construct(
        protected ApiService $apiService
    ) {}

    /**
     * Create a new affiliate for the merchant with the given commission rate.
     *
     * @param  Merchant $merchant
     * @param  string $email
     * @param  string $name
     * @param  float $commissionRate
     * @return Affiliate
     */
    public function register(Merchant $merchant, string $email, string $name, float $commissionRate): Affiliate
    {
        // Check if the email is already in use as a merchant
        if ($merchant->user->email === $email) {
            throw new AffiliateCreateException('Email is already in use as a merchant.');
        }

        // Check if the email is already in use as an affiliate
        if (Affiliate::where('merchant_id', $merchant->id)->whereHas('user', function ($query) use ($email) {
            $query->where('email', $email);
        })->exists()) {
            throw new AffiliateCreateException('Email is already in use as an affiliate.');
        }

        // Create a new user for the affiliate
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => '12345',
            'type' => User::TYPE_AFFILIATE,
        ]);

        // Create a new affiliate associated with the user and merchant
        $affiliate = Affiliate::create([
            'merchant_id' => $merchant->id,
            'user_id' => $user->id,
            'commission_rate' => $commissionRate,
            'discount_code' => $this->apiService->createDiscountCode($merchant)['code'],
        ]);

        // Send an email notification
        Mail::to($email)->send(new AffiliateCreated($affiliate));

        return $affiliate;
    }
}
