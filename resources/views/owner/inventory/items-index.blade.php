@extends('owner.layouts.app')

@section('title', 'Inventory Items')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="{{ route('owner.inventory.categories.index', ['site_id' => $site->id]) }}" class="link-secondary text-decoration-none">
                <i class="bi bi-arrow-left"></i> Back to Categories
            </a>
            <h1 class="h3 fw-bold text-dark mt-2">Items in {{ $category->name }}</h1>
            <p class="text-muted">{{ $site->name }} • {{ ucfirst($category->type) }} Category</p>
        </div>
        <a href="{{ route('owner.inventory.items.create', $category->id) }}" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> Add Item
        </a>
    </div>

    <!-- Items List -->
    @if($items->isNotEmpty())
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Item Name</th>
                        <th>SKU</th>
                        <th>Unit</th>
                        <th>Status</th>
                        <th style="width: 150px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($items as $item)
                    <tr>
                        <td class="fw-500">{{ $item->name }}</td>
                        <td>
                            <code class="bg-light px-2 py-1 rounded text-sm">{{ $item->sku ?? '--' }}</code>
                        </td>
                        <td>{{ $item->unit }}</td>
                        <td>
                            @if($item->is_active)
                                <span class="badge bg-success">Active</span>
                            @else
                                <span class="badge bg-secondary">Inactive</span>
                            @endif
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm" role="group">
                                <a href="{{ route('owner.inventory.items.edit', [$category->id, $item->id]) }}" class="btn btn-outline-secondary" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form method="POST" action="{{ route('owner.inventory.items.destroy', [$category->id, $item->id]) }}" style="display:inline;" onsubmit="return confirm('Delete this item?');">
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
        <h5 class="text-muted">No items in this category</h5>
        <p class="text-muted mb-3">Add your first item to start tracking inventory</p>
        <a href="{{ route('owner.inventory.items.create', $category->id) }}" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg"></i> Add Item
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
