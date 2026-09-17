@extends('admin.layouts.app')
@section('title', 'Inventory')
@section('page-title', 'Inventory Management')

@section('content')
<div class="grid-2 mb-24">
    <!-- Adjust Stock Form -->
    <div class="card">
        <div class="card-title mb-16">Manual Stock Adjustment</div>
        <form method="POST" action="{{ route('admin.inventory.adjust') }}" id="inventory-adjust-form">
            @csrf
            <div class="form-group">
                <label>Product *</label>
                <select name="product_id" id="inventory-product" class="form-control" required onchange="loadVariants(this)">
                    <option value="">Select product…</option>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}"
                                data-variants="{{ $product->variants->map(fn($v) => ['id'=>$v->id,'sku'=>$v->sku,'stock'=>$v->stock,'title'=>$v->sku])->toJson() }}"
                                {{ old('product_id') == $product->id ? 'selected' : '' }}>
                            {{ $product->name }}
                        </option>
                    @endforeach
                </select>
                @error('product_id')<div class="form-error">{{ $message }}</div>@enderror
            </div>

            <!-- Variant Selector (shown only when product has variants) -->
            <div class="form-group" id="variant-group" style="display:none">
                <label>Variant <span style="font-weight:400;color:var(--muted)">(optional – leave blank to adjust base product stock)</span></label>
                <select name="product_variant_id" id="inventory-variant" class="form-control">
                    <option value="">— Base Product Stock —</option>
                </select>
                @error('product_variant_id')<div class="form-error">{{ $message }}</div>@enderror
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Type *</label>
                    <select name="type" class="form-control" required>
                        <option value="">Select type…</option>
                        @foreach($types as $type)
                            <option value="{{ $type }}" {{ old('type') === $type ? 'selected' : '' }}>
                                {{ ucwords(str_replace('_', ' ', $type)) }}
                            </option>
                        @endforeach
                    </select>
                    @error('type')<div class="form-error">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label>Quantity *</label>
                    <input type="number" name="quantity" class="form-control" min="1" value="{{ old('quantity') }}" required>
                    @error('quantity')<div class="form-error">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="form-group">
                <label>Notes</label>
                <input type="text" name="notes" class="form-control" value="{{ old('notes') }}" placeholder="Optional reason…">
            </div>
            <button type="submit" class="btn btn-primary w-full">✅ Apply Adjustment</button>
        </form>
    </div>

    <!-- Legend -->
    <div class="card" style="align-self:start">
        <div class="card-title mb-16">Adjustment Types</div>
        <ul style="list-style:none;display:flex;flex-direction:column;gap:10px">
            <li><span class="badge badge-green">Stock In</span> <span class="text-sm text-muted">— Receiving new stock from supplier</span></li>
            <li><span class="badge badge-red">Stock Out</span> <span class="text-sm text-muted">— Removing stock manually</span></li>
            <li><span class="badge badge-yellow">Damaged</span> <span class="text-sm text-muted">— Writing off damaged goods</span></li>
            <li><span class="badge badge-blue">Adjustment In</span> <span class="text-sm text-muted">— Stock count correction (increase)</span></li>
            <li><span class="badge badge-purple">Adjustment Out</span> <span class="text-sm text-muted">— Stock count correction (decrease)</span></li>
            <li><span class="badge badge-gray">Return</span> <span class="text-sm text-muted">— Returned from cancelled order (auto)</span></li>
            <li><span class="badge badge-gray">Sale</span> <span class="text-sm text-muted">— Deducted on order placement (auto)</span></li>
        </ul>
    </div>
</div>

<!-- Log Table -->
<div class="card">
    <div class="card-header">
        <div class="card-title">Inventory History</div>
    </div>
    <form method="GET" class="filter-bar">
        <select name="product_id" class="form-control">
            <option value="">All Products</option>
            @foreach($products as $product)
                <option value="{{ $product->id }}" {{ request('product_id') == $product->id ? 'selected' : '' }}>{{ $product->name }}</option>
            @endforeach
        </select>
        <select name="type" class="form-control">
            <option value="">All Types</option>
            @foreach($types as $type)
                <option value="{{ $type }}" {{ request('type') === $type ? 'selected' : '' }}>{{ ucwords(str_replace('_', ' ', $type)) }}</option>
            @endforeach
        </select>
        <button class="btn btn-primary btn-sm">Filter</button>
        <a href="{{ route('admin.inventory.index') }}" class="btn btn-outline btn-sm">Reset</a>
    </form>

    <div class="table-wrap">
        <table>
            <thead>
                <tr><th>Date</th><th>Product</th><th>Variant</th><th>Type</th><th>Qty</th><th>Before</th><th>After</th><th>By</th><th>Notes</th><th>Ref #</th></tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                <tr>
                    <td class="text-sm text-muted">{{ $log->created_at->format('M d, Y H:i') }}</td>
                    <td><strong>{{ $log->product?->name ?? '—' }}</strong></td>
                    <td class="text-sm text-muted">{{ $log->variant?->sku ?? '—' }}</td>
                    <td>
                        @php
                            $badgeCls = match(true) {
                                in_array($log->type, ['stock_in', 'adjustment_in']) => 'badge-green',
                                in_array($log->type, ['damaged', 'stock_out', 'adjustment_out']) => 'badge-red',
                                $log->type === 'return' => 'badge-blue',
                                default => 'badge-gray'
                            };
                        @endphp
                        <span class="badge {{ $badgeCls }}">{{ str_replace('_', ' ', $log->type) }}</span>
                    </td>
                    <td><strong>{{ $log->quantity }}</strong></td>
                    <td class="text-muted">{{ $log->quantity_before }}</td>
                    <td class="text-muted">{{ $log->quantity_after }}</td>
                    <td class="text-sm">{{ $log->user?->name ?? 'System' }}</td>
                    <td class="text-sm text-muted">{{ $log->notes ?? '—' }}</td>
                    <td class="text-sm">{{ $log->reference_id ? "#$log->reference_id" : '—' }}</td>
                </tr>
                @empty
                <tr><td colspan="10" style="text-align:center;padding:32px;color:var(--muted)">No inventory records yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pagination">{{ $logs->links() }}</div>
</div>

@push('scripts')
<script>
function loadVariants(selectEl) {
    const variantGroup   = document.getElementById('variant-group');
    const variantSelect  = document.getElementById('inventory-variant');
    const selectedOption = selectEl.options[selectEl.selectedIndex];
    let variants = [];

    try { variants = JSON.parse(selectedOption.dataset.variants || '[]'); } catch(e) {}

    // Reset variant dropdown
    variantSelect.innerHTML = '<option value="">— Base Product Stock —</option>';

    if (variants.length > 0) {
        variants.forEach(function(v) {
            const opt = document.createElement('option');
            opt.value       = v.id;
            opt.textContent = v.sku + ' (Stock: ' + v.stock + ')';
            variantSelect.appendChild(opt);
        });
        variantGroup.style.display = '';
    } else {
        variantGroup.style.display = 'none';
    }
}
</script>
@endpush
@endsection
