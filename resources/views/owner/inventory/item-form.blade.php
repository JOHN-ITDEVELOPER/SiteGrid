@extends('owner.layouts.app')

@section('title', $item ? 'Edit Item' : 'Create Item')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="mb-4">
        <a href="{{ route('owner.inventory.items.index', $category->id) }}" class="link-secondary text-decoration-none">
            <i class="bi bi-arrow-left"></i> Back to Items
        </a>
        <h1 class="h3 fw-bold text-dark mt-2">{{ $item ? 'Edit Item' : 'Create Item' }}</h1>
    </div>

    <!-- Form -->
    <div class="row">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <form method="POST" action="{{ $item ? route('owner.inventory.items.update', [$category->id, $item->id]) : route('owner.inventory.items.store', $category->id) }}">
                        @csrf
                        @if($item)
                            @method('PUT')
                        @endif

                        <!-- Item Name -->
                        <div class="mb-3">
                            <label for="name" class="form-label fw-500">Item Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                   id="name" name="name" value="{{ old('name', $item->name ?? '') }}" 
                                   placeholder="e.g., OPC Cement 50kg" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- SKU -->
                        <div class="mb-3">
                            <label for="sku" class="form-label fw-500">SKU (Stock Keeping Unit)</label>
                            <input type="text" class="form-control @error('sku') is-invalid @enderror" 
                                   id="sku" name="sku" value="{{ old('sku', $item->sku ?? '') }}" 
                                   placeholder="e.g., CEMENT-OPC-50">
                            <small class="form-text text-muted">Unique identifier for inventory tracking</small>
                            @error('sku')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Unit -->
                        <div class="mb-3">
                            <label for="unit" class="form-label fw-500">Unit of Measurement <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('unit') is-invalid @enderror" 
                                   id="unit" name="unit" value="{{ old('unit', $item->unit ?? '') }}" 
                                   placeholder="e.g., bag, piece, liter, kg, ton" required>
                            <small class="form-text text-muted">How this item is measured (pieces, bags, liters, kg, etc.)</small>
                            @error('unit')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Status -->
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1"
                                       {{ old('is_active', $item->is_active ?? true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_active">
                                    Active (available for procurement and tracking)
                                </label>
                            </div>
                        </div>

                        <!-- Description -->
                        <div class="mb-4">
                            <label for="description" class="form-label fw-500">Description</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                      id="description" name="description" rows="4"
                                      placeholder="Add details about this item...">{{ old('description', $item->description ?? '') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Actions -->
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg"></i> {{ $item ? 'Update Item' : 'Create Item' }}
                            </button>
                            <a href="{{ route('owner.inventory.items.index', $category->id) }}" class="btn btn-outline-secondary">
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Info Panel -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm bg-light">
                <div class="card-body">
                    <h6 class="card-title fw-600 mb-3">
                        <i class="bi bi-info-circle"></i> Category
                    </h6>
                    <p class="text-sm mb-3">
                        <strong>{{ $category->name }}</strong><br>
                        <span class="badge bg-{{ $category->type === 'material' ? 'primary' : ($category->type === 'tool' ? 'warning text-dark' : 'success') }}">
                            {{ ucfirst($category->type) }}
                        </span>
                    </p>

                    <hr>

                    <h6 class="card-title fw-600 mb-3">
                        <i class="bi bi-lightbulb"></i> Tips
                    </h6>
                    <ul class="list-unstyled text-sm text-muted">
                        <li class="mb-2">
                            <strong>Item Name:</strong> Be descriptive and specific
                        </li>
                        <li class="mb-2">
                            <strong>SKU:</strong> Use a consistent naming pattern for easy identification
                        </li>
                        <li class="mb-2">
                            <strong>Unit:</strong> Choose the most practical measurement (bag, piece, liter, kg, etc.)
                        </li>
                        <li>
                            <strong>Status:</strong> Deactivate items you no longer track
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
