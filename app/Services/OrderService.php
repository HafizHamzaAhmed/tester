<?php

namespace App\Services;

use App\Models\Affiliate;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\User;

class OrderService
{
    public function __construct(
        protected AffiliateService $affiliateService
    ) {}

    /**
     * Process an order and log any commissions.
     * This should create a new affiliate if the customer_email is not already associated with one.
     * This method should also ignore duplicates based on order_id.
     *
     * @param  array{order_id: string, subtotal_price: float, merchant_domain: string, discount_code: string, customer_email: string, customer_name: string} $data
     * @return void
     */
    public function processOrder(array $data)
    {
        // Check if an order with the same order_id already exists
        if (Order::where('id', $data['order_id'])->exists()) {
            return; // Ignore duplicates
        }

        // Find or create a merchant based on the merchant_domain
        $merchant = Merchant::firstOrCreate(['domain' => $data['merchant_domain']], [
            // You may add any other merchant attributes here if needed
        ]);

        // Find or create an affiliate based on the discount_code
        $affiliate = Affiliate::firstOrCreate(['discount_code' => $data['discount_code']], [
            'merchant_id' => $merchant->id,
            'commission_rate' => 0.1, // You may adjust the commission rate as needed
        ]);

        // Register the affiliate if the customer_email is not already associated
        $this->affiliateService->register($merchant, $data['customer_email'], $data['customer_name'], $affiliate->commission_rate);

        // Create the order
        Order::create([
            'id' => $data['order_id'],
            'subtotal' => $data['subtotal_price'],
            'merchant_id' => $merchant->id,
            'affiliate_id' => $affiliate->id,
            'commission_owed' => $data['subtotal_price'] * $affiliate->commission_rate,
            'payout_status' => Order::STATUS_UNPAID,
            'customer_email' => $data['customer_email'],
        ]);
    }
}
