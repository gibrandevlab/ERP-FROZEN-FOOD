<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseRequest extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'ticket_number',
        'product_id',
        'location_id',
        'supplier_id',
        'requested_qty',
        'received_qty',
        'status',
        'requested_by',
        'request_notes',
        'reviewed_by',
        'rejection_reason',
        'reviewed_at',
        'purchased_by',
        'invoice_number',
        'proof_image',
        'total_cost',
        'purchased_at',
        'received_by',
        'received_at',
        'ledger_id',
    ];

    protected function casts(): array
    {
        return [
            'requested_qty' => 'integer',
            'received_qty'  => 'integer',
            'total_cost'    => 'decimal:2',
            'reviewed_at'   => 'datetime',
            'purchased_at'  => 'datetime',
            'received_at'   => 'datetime',
        ];
    }

    // ─── Relasi ──────────────────────────────────────────────

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewedBy()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function purchasedBy()
    {
        return $this->belongsTo(User::class, 'purchased_by');
    }

    public function receivedBy()
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function ledger()
    {
        return $this->belongsTo(Ledger::class);
    }

    // ─── Scopes Status ────────────────────────────────────────

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopePurchased($query)
    {
        return $query->where('status', 'purchased');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }
}
