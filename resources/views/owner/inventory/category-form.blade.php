@extends('owner.layouts.app')

@section('title', $category ? 'Edit Category' : 'Create Category')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="mb-4">
        <a href="{{ route('owner.inventory.categories.index', ['site_id' => $site->id]) }}" class="link-secondary text-decoration-none">
            <i class="bi bi-arrow-left"></i> Back to Categories
        </a>
        <h1 class="h3 fw-bold text-dark mt-2">{{ $category ? 'Edit Category' : 'Create Category' }}</h1>
    </div>

    <!-- Form -->
    <div class="row">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <form method="POST" action="{{ $category ? route('owner.inventory.categories.update', $category->id) : route('owner.inventory.categories.store') }}">
                        @csrf
                        @if($category)
                            @method('PUT')
                        @endif

                        <input type="hidden" name="site_id" value="{{ $site->id }}">

                        <!-- Category Name -->
                        <div class="mb-3">
                            <label for="name" class="form-label fw-500">Category Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                   id="name" name="name" value="{{ old('name', $category->name ?? '') }}" 
                                   placeholder="e.g., Cement & Materials" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Type -->
                        <div class="mb-3">
                            <label for="type" class="form-label fw-500">Category Type <span class="text-danger">*</span></label>
                            <select class="form-select @error('type') is-invalid @enderror" id="type" name="type" required>
                                <option value="">-- Select Type --</option>
                                <option value="material" {{ old('type', $category->type ?? '') === 'material' ? 'selected' : '' }}>Material</option>
                                <option value="tool" {{ old('type', $category->type ?? '') === 'tool' ? 'selected' : '' }}>Tool</option>
                                <option value="equipment" {{ old('type', $category->type ?? '') === 'equipment' ? 'selected' : '' }}>Equipment</option>
                            </select>
                            <small class="form-text text-muted">
                                Materials are consumables (cement, paint), Tools are reusable hand tools, Equipment is large machinery
                            </small>
                            @error('type')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Description -->
                        <div class="mb-4">
                            <label for="description" class="form-label fw-500">Description</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                      id="description" name="description" rows="4"
                                      placeholder="Add details about this category...">{{ old('description', $category->description ?? '') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Actions -->
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg"></i> {{ $category ? 'Update Category' : 'Create Category' }}
                            </button>
                            <a href="{{ route('owner.inventory.categories.index', ['site_id' => $site->id]) }}" class="btn btn-outline-secondary">
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
                        <i class="bi bi-info-circle"></i> Tips
                    </h6>
                    <ul class="list-unstyled text-sm text-muted">
                        <li class="mb-2">
                            <strong>Category Name:</strong> Use descriptive names that make sense for your items
                        </li>
                        <li class="mb-2">
                            <strong>Type:</strong> Choose the appropriate category type to help organize inventory
                        </li>
                        <li class="mb-2">
                            <strong>Description:</strong> Add notes about the category's purpose or usage
                        </li>
                        <li>
                            After creating a category, you'll be able to add items to it
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
