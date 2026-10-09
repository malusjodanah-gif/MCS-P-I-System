@php $active = collect($line)->contains(fn ($value) => $value !== null && $value !== ''); @endphp
<details class="ge-line" data-index="{{ $index }}" @if($index === 0 || $active) open @endif>
    <summary>Line <span class="line-number">{{ is_numeric($index) ? $index + 1 : '' }}</span> <span class="line-summary text-muted">— optional</span></summary>
    <div class="form-grid">
        <div class="form-group stock-fields"><label for="item-{{ $index }}">Item ID (STOCK)</label><select id="item-{{ $index }}" class="line-item" name="lines[{{ $index }}][item_id]"><option value="">— Select item —</option>@foreach($items as $item)<option value="{{ $item->item_id }}" data-description="{{ $item->item }}" data-uom="{{ $item->uom }}" @selected(($line['item_id'] ?? null)==$item->item_id)>{{ $item->item_id }} — {{ $item->item }}</option>@endforeach</select></div>
        <div class="form-group"><label for="description-{{ $index }}">Item / service description</label><input id="description-{{ $index }}" class="line-description" name="lines[{{ $index }}][item]" maxlength="255" value="{{ $line['item'] ?? '' }}"><small class="line-help">STOCK descriptions come from the selected item.</small></div>
        <div class="form-group stock-fields"><label for="uom-{{ $index }}">UOM</label><input id="uom-{{ $index }}" class="line-uom" disabled></div>
        <div class="form-group"><label for="quantity-{{ $index }}">Quantity</label><input id="quantity-{{ $index }}" class="line-quantity" type="number" name="lines[{{ $index }}][quantity]" min="1" max="2147483647" step="1" value="{{ $line['quantity'] ?? '' }}"></div>
        <div class="form-group"><label for="cost-{{ $index }}">Unit Cost</label><input id="cost-{{ $index }}" class="line-cost" type="number" name="lines[{{ $index }}][unit_cost]" min="0" max="99999999.9999" step="0.0001" value="{{ $line['unit_cost'] ?? '' }}"></div>
        <div class="form-group"><label>Total Cost</label><output class="line-total money">0.00</output></div>
        <button type="button" class="clear-line btn btn-secondary btn-sm">Clear line</button>
    </div>
</details>
