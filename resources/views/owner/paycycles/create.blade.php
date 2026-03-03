@extends('owner.layouts.app')

@section('title', 'Create Pay-Cycle')
@section('page-title', 'Create New Pay-Cycle')

@section('content')
<div class="row">
    <div class="col-lg-8 mx-auto">
        <div class="card kpi-card">
            <div class="card-body">
                <p class="text-muted">Create a pay-cycle to compute wages based on attendance records for a specific period.</p>
                
                <form method="POST" action="{{ route('owner.paycycles.store') }}">
                    @csrf
                    
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label">Select Site *</label>
                            <select class="form-select @error('site_id') is-invalid @enderror" name="site_id" required>
                                <option value="">-- Choose Site --</option>
                                @foreach($sites as $site)
                                    <option value="{{ $site->id }}" {{ old('site_id') == $site->id ? 'selected' : '' }}>
                                        {{ $site->name }} - {{ $site->location }}
                                    </option>
                                @endforeach
                            </select>
                            @error('site_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Start Date *</label>
                            <input type="date" class="form-control @error('start_date') is-invalid @enderror" name="start_date" value="{{ old('start_date') }}" required>
                            @error('start_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <small class="text-muted">First day of the pay period</small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">End Date *</label>
                            <input type="date" class="form-control @error('end_date') is-invalid @enderror" name="end_date" value="{{ old('end_date') }}" required>
                            @error('end_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <small class="text-muted">Last day of the pay period</small>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Recurrence Pattern (Optional)</label>
                            <select class="form-select @error('recurrence_pattern') is-invalid @enderror" name="recurrence_pattern">
                                <option value="">No Recurring (One-time)</option>
                                <option value="weekly" {{ old('recurrence_pattern') == 'weekly' ? 'selected' : '' }}>Weekly - Auto-create next cycle</option>
                                <option value="bi-weekly" {{ old('recurrence_pattern') == 'bi-weekly' ? 'selected' : '' }}>Bi-Weekly - Every 2 weeks</option>
                                <option value="monthly" {{ old('recurrence_pattern') == 'monthly' ? 'selected' : '' }}>Monthly - Same day each month</option>
                            </select>
                            @error('recurrence_pattern')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <small class="text-muted">If enabled, system will auto-create next pay-cycle when current one is paid</small>
                        </div>

                        <div class="col-12">
                            <div class="alert alert-info border small">
                                <strong>How it works (Real-time Payroll):</strong>
                                <ul class="mb-0 mt-2">
                                    <li><strong>Step 1:</strong> Create pay cycle for date range (e.g., Mon-Fri)</li>
                                    <li><strong>Step 2:</strong> Foreman/Owner records attendance daily</li>
                                    <li><strong>Step 3:</strong> System <strong>automatically updates</strong> worker wages as attendance is recorded</li>
                                    <li><strong>Step 4:</strong> Owner can see <strong>live totals</strong> on dashboard anytime</li>
                                    <li><strong>Step 5:</strong> When ready, approve to send payments via M-Pesa</li>
                                    <li><strong>Benefits:</strong> Workers added mid-week are included • Flexible dates • See totals in real-time</li>
                                    <li style="color: #d39e00;"><strong>Note:</strong> Cannot create overlapping cycles for same dates</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Create Pay-Cycle</button>
                        <a href="{{ route('owner.payroll') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
