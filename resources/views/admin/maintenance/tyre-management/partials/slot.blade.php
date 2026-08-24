<div class="tyre-slot p-2 d-flex flex-column justify-content-between align-items-center {{ $tyre ? 'occupied' : 'empty' }}"
     data-slot-code="{{ $slotCode }}"
     ondragover="handleDragOver(event)"
     ondragleave="handleDragLeave(event)"
     ondrop="handleDrop(event, '{{ $slotCode }}')"
     @if(!$tyre) onclick="handleEmptySlotClick('{{ $slotCode }}')" title="Click to add new tyre at position {{ $slotCode }}" @endif>

    <!-- Slot Header Position Badge -->
    <div class="w-100 d-flex justify-content-between align-items-center mb-1">
        <span class="badge bg-primary text-white fw-bold shadow-xs" style="font-size: 0.68rem; padding: 3px 6px;">{{ $slotCode }}</span>
        <small class="slot-title text-truncate opacity-75 fw-semibold" style="font-size: 0.65rem;" title="{{ $slotName }}">{{ $slotName }}</small>
    </div>

    <!-- Tyre Content OR Empty Placeholder -->
    @if($tyre)
        @include('admin.maintenance.tyre-management.partials.tyre-card', ['tyre' => $tyre, 'slotCode' => $slotCode])
    @else
        <div class="empty-slot-placeholder text-center my-auto py-2 w-100">
            <i class="bx bx-plus-circle fs-3 d-block mb-1 slot-plus-icon text-primary"></i>
            <span class="empty-slot-text fw-bold d-block text-primary" style="font-size: 0.72rem;">+ Add Tyre</span>
            <small class="text-muted d-block empty-slot-hint" style="font-size: 0.60rem;">Click to create</small>
        </div>
    @endif
</div>

