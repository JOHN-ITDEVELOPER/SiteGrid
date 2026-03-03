@extends('owner.layouts.app')

@section('title', 'Inventory Categories')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 fw-bold text-dark">Inventory Categories</h1>
            <p class="text-muted">Manage inventory categories and items for {{ $selectedSite->name ?? 'your site' }}</p>
        </div>
        <a href="{{ route('owner.inventory.categories.create', ['site_id' => $selectedSite->id ?? '']) }}" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> Add Category
        </a>
    </div>

    <!-- Site Selector -->
    @if(count($sites) > 1)
    <div class="row mb-4">
        <div class="col-md-4">
            <label class="form-label fw-500">Select Site</label>
            <form method="GET" action="{{ route('owner.inventory.categories.index') }}" class="d-flex gap-2">
                <select name="site_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    @forelse($sites as $site)
                        <option value="{{ $site->id }}" {{ $selectedSite?->id === $site->id ? 'selected' : '' }}>
                            {{ $site->name }}
                        </option>
                    @empty
                        <option disabled>No sites available</option>
                    @endforelse
                </select>
            </form>
        </div>
    </div>
    @endif

    <!-- Template Selector (if no categories yet) -->
    @if($categories->isEmpty() && $templates)
    <div class="alert alert-info border-0 rounded-3 mb-4" role="alert">
        <div class="d-flex align-items-center gap-3">
            <div class="flex-shrink-0">
                <i class="bi bi-lightbulb fs-5"></i>
            </div>
            <div class="flex-grow-1">
                <h6 class="alert-heading mb-2">Quick Start with Templates</h6>
                <p class="mb-3 text-sm">Start with a pre-configured template and customize it for your site:</p>
                <div class="d-flex flex-wrap gap-2">
                    @foreach($templates as $key => $template)
                    <form method="POST" action="{{ route('owner.inventory.categories.apply-template') }}" class="d-inline">
                        @csrf
                        <input type="hidden" name="site_id" value="{{ $selectedSite->id }}">
                        <input type="hidden" name="template" value="{{ $key }}">
                        <button type="submit" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-check2-circle"></i> {{ $template['label'] }}
                        </button>
                    </form>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Categories List -->
    @if($categories->isNotEmpty())
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Category Name</th>
                        <th>Type</th>
                        <th>Items</th>
                        <th>Description</th>
                        <th style="width: 150px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($categories as $category)
                    <tr>
                        <td class="fw-500">{{ $category->name }}</td>
                        <td>
                            <span class="badge 
                                @if($category->type === 'material') bg-primary
                                @elseif($category->type === 'tool') bg-warning text-dark
                                @else bg-success
                                @endif
                            ">
                                {{ ucfirst($category->type) }}
                            </span>
                        </td>
                        <td>
                            <a href="{{ route('owner.inventory.items.index', $category->id) }}" class="text-decoration-none">
                                <span class="badge bg-secondary">{{ $category->items->count() }} items</span>
                            </a>
                        </td>
                        <td class="text-muted text-sm">{{ substr($category->description, 0, 50) . (strlen($category->description) > 50 ? '...' : '') }}</td>
                        <td>
                            <div class="btn-group btn-group-sm" role="group">
                                <a href="{{ route('owner.inventory.categories.edit', $category->id) }}" class="btn btn-outline-secondary" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a href="{{ route('owner.inventory.items.index', $category->id) }}" class="btn btn-outline-primary" title="Manage Items">
                                    <i class="bi bi-list-ul"></i>
                                </a>
                                <form method="POST" action="{{ route('owner.inventory.categories.destroy', $category->id) }}" style="display:inline;" onsubmit="return confirm('Delete this category and all its items?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger" title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @else
    <div class="alert alert-light border-0 text-center py-5" role="alert">
        <i class="bi bi-inbox fs-1 text-muted d-block mb-3"></i>
        <h5 class="text-muted">No categories yet</h5>
        <p class="text-muted mb-3">Create your first category or use a quick-start template above</p>
        <a href="{{ route('owner.inventory.categories.create', ['site_id' => $selectedSite->id ?? '']) }}" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg"></i> Create Category
        </a>
    </div>
    @endif
</div>

@if ($errors->any())
<div class="alert alert-danger alert-dismissible fade show position-fixed bottom-0 end-0 m-3" role="alert" style="z-index: 1050; max-width: 400px;">
    <strong>Errors:</strong>
    <ul class="mb-0 mt-2">
        @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show position-fixed bottom-0 end-0 m-3" role="alert" style="z-index: 1050; max-width: 400px;">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif
@endsection
