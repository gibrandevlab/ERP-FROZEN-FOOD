<?php

namespace App\Observers;

use App\Models\{
    AuditLog,
    Category,
    Customer,
    Ledger,
    LedgerItem,
    Location,
    Product,
    PurchaseRequest,
    Supplier,
    User,
    UserPermission
};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AuditLogObserver
{
    private const SNAPSHOT_RELATION = '__audit_log_before';

    private const AUDITED_FIELDS = [
        Product::class => ['name', 'category_id', 'slug', 'sku', 'description', 'price', 'wholesale_price', 'wholesale_min_qty', 'cost', 'unit', 'image', 'is_active', 'lead_time', 'min_stock'],
        Category::class => ['name', 'slug', 'description'],
        Location::class => ['name', 'description', 'is_active'],
        Customer::class => ['name', 'phone', 'type'],
        Supplier::class => ['name', 'phone', 'address', 'description', 'is_active'],
        Ledger::class => ['type', 'title', 'amount', 'payment_status', 'due_date', 'description', 'date', 'reference', 'product_id', 'location_id', 'quantity', 'stock_movement', 'proof_image', 'customer_id', 'supplier_id'],
        LedgerItem::class => ['ledger_id', 'product_id', 'location_id', 'product_name', 'location_name', 'quantity', 'unit_price'],
        PurchaseRequest::class => ['ticket_number', 'product_id', 'location_id', 'supplier_id', 'requested_qty', 'received_qty', 'status', 'request_notes', 'reviewed_by', 'rejection_reason', 'reviewed_at', 'purchased_by', 'invoice_number', 'proof_image', 'total_cost', 'purchased_at', 'received_by', 'received_at', 'ledger_id'],
        User::class => ['name', 'email', 'is_admin'],
        UserPermission::class => ['user_id', 'permission_id', 'can_view', 'can_create', 'can_edit', 'can_delete'],
    ];

    public function created(Model $model): void
    {
        $this->record($model, 'created', $this->creationChanges($model));
    }

    public function updating(Model $model): void
    {
        $model->setRelation(self::SNAPSHOT_RELATION, $this->snapshot($model));
    }

    public function updated(Model $model): void
    {
        $before = $model->getRelation(self::SNAPSHOT_RELATION) ?? [];
        $model->unsetRelation(self::SNAPSHOT_RELATION);

        $changes = [];
        foreach (self::AUDITED_FIELDS[$model::class] ?? [] as $field) {
            $old = $before[$field] ?? null;
            $new = $model->getAttributes()[$field] ?? null;
            if ($old !== $new) {
                $changes[$field] = ['old' => $old, 'new' => $new];
            }
        }

        if ($changes !== []) {
            $this->record($model, 'updated', $changes);
        }
    }

    public function deleting(Model $model): void
    {
        $model->setRelation(self::SNAPSHOT_RELATION, $this->snapshot($model));
    }

    public function deleted(Model $model): void
    {
        $before = $model->getRelation(self::SNAPSHOT_RELATION) ?? [];
        $model->unsetRelation(self::SNAPSHOT_RELATION);
        $changes = [];
        foreach ($before as $field => $value) {
            if ($value !== null) {
                $changes[$field] = ['old' => $value, 'new' => null];
            }
        }

        $this->record($model, 'deleted', $changes);
    }

    public function restoring(Model $model): void
    {
        $model->setRelation(self::SNAPSHOT_RELATION, $this->snapshot($model));
    }

    public function restored(Model $model): void
    {
        $model->unsetRelation(self::SNAPSHOT_RELATION);
        $this->record($model, 'restored', []);
    }

    private function creationChanges(Model $model): array
    {
        $changes = [];
        foreach (self::AUDITED_FIELDS[$model::class] ?? [] as $field) {
            if (array_key_exists($field, $model->getAttributes()) && $model->getAttributes()[$field] !== null) {
                $changes[$field] = ['old' => null, 'new' => $model->getAttributes()[$field]];
            }
        }

        return $changes;
    }

    private function snapshot(Model $model): array
    {
        $snapshot = [];
        foreach (self::AUDITED_FIELDS[$model::class] ?? [] as $field) {
            $snapshot[$field] = $model->getRawOriginal($field);
        }

        return $snapshot;
    }

    private function record(Model $model, string $event, array $changes): void
    {
        $actor = auth()->user();
        $attributes = $model->getAttributes();
        $subjectLabel = $this->subjectLabel($model, $attributes);

        AuditLog::create([
            'actor_id' => $actor?->getKey(),
            'actor_name' => $actor?->name ?? 'Sistem',
            'event' => $event,
            'subject_type' => $model->getMorphClass(),
            'subject_id' => (string) $model->getKey(),
            'subject_label' => Str::limit($subjectLabel, 255, ''),
            'changes' => $changes,
            'created_at' => now(),
        ]);
    }

    private function subjectLabel(Model $model, array $attributes): string
    {
        if ($model instanceof LedgerItem) {
            return trim(($attributes['product_name'] ?? 'Produk dihapus').' - '.($attributes['location_name'] ?? 'Lokasi dihapus'));
        }

        foreach (['name', 'title', 'ticket_number', 'reference', 'product_name', 'email'] as $field) {
            if (! empty($attributes[$field])) {
                return (string) $attributes[$field];
            }
        }

        return Str::headline(class_basename($model)).' #'.$model->getKey();
    }
}
