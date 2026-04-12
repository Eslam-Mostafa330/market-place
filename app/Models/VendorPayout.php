<?php

namespace App\Models;

use App\Enums\PayoutMethod;
use App\Enums\PayoutStatus;
use App\Filters\VendorPayoutFilters;
use Essa\APIToolKit\Filters\Filterable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorPayout extends BaseModel
{
    use Filterable;
    
    protected string $default_filters = VendorPayoutFilters::class;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'vendor_id',
        'order_id',
        'processed_by',
        'updated_by',
        'amount',
        'status',
        'payout_method',
        'reference',
        'paid_at',
        'payout_proof',
        'notes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payout_method' => PayoutMethod::class,
            'paid_at'       => 'datetime',
            'amount'        => 'decimal:2',
            'status'        => PayoutStatus::class,
        ];
    }

        /**** ************* ****/
    /**** Relationships ****/
    /**** ************* ****/
    /** 
     * A payout belongs to a vendor
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    /** 
     * The payout is processed by an admin user
     */
    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    /** 
     * The payout is updated by an admin user
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /** 
     * A payout belongs to an order
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /****************************/
    /***** Accessor Methods *****/
    /****************************/
    /**
     * Accessor that can access the payout proof screenshot URL
     */
    protected function payoutProofUrl(): Attribute
    {
        return Attribute::get(
            fn () => $this->payout_proof ? asset('storage/' . $this->payout_proof) : null
        );
    }
}