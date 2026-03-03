@extends('admin.layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Integration Health Dashboard</h1>
        <button class="btn btn-primary" onclick="location.reload()">
            <i class="bi bi-arrow-clockwise"></i> Refresh
        </button>
    </div>

    <!-- Overall Payout Stats -->
    <div class="row mb-4">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Payout Status (Last 30 Days)</h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-3">
                            <div class="p-3">
                                <h3 class="text-success">{{ $payoutStats['completed'] ?? 0 }}</h3>
                                <small class="text-muted">Completed</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="p-3">
                                <h3 class="text-warning">{{ $payoutStats['pending'] ?? 0 }}</h3>
                                <small class="text-muted">Pending</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="p-3">
                                <h3 class="text-info">{{ $payoutStats['processing'] ?? 0 }}</h3>
                                <small class="text-muted">Processing</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="p-3">
                                <h3 class="text-danger">{{ $payoutStats['failed'] ?? 0 }}</h3>
                                <small class="text-muted">Failed</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Integration Health Cards -->
    <div class="row">
        <!-- MPesa Integration -->
        <div class="col-lg-4 mb-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-phone"></i> {{ $mpesaHealth['name'] }}
                    </h5>
                    @if($mpesaHealth['status'] === 'healthy')
                        <span class="badge bg-success">Healthy</span>
                    @elseif($mpesaHealth['status'] === 'warning')
                        <span class="badge bg-warning">Warning</span>
                    @else
                        <span class="badge bg-danger">Critical</span>
                    @endif
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <strong>24h Activity:</strong>
                        <div class="d-flex justify-content-between">
                            <span>Total Calls:</span>
                            <span class="badge bg-primary">{{ $mpesaHealth['total_calls_24h'] }}</span>
                        </div>
                        <div class="d-flex justify-content-between mt-2">
                            <span>Success Rate:</span>
                            <span class="badge bg-{{ $mpesaHealth['success_rate_24h'] >= 80 ? 'success' : ($mpesaHealth['success_rate_24h'] >= 50 ? 'warning' : 'danger') }}">
                                {{ $mpesaHealth['success_rate_24h'] }}%
                            </span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <strong>7 Day Activity:</strong>
                        <div class="d-flex justify-content-between">
                            <span>Total Calls:</span>
                            <span class="badge bg-primary">{{ $mpesaHealth['total_calls_7d'] }}</span>
                        </div>
                        <div class="d-flex justify-content-between mt-2">
                            <span>Success Rate:</span>
                            <span class="badge bg-{{ $mpesaHealth['success_rate_7d'] >= 80 ? 'success' : ($mpesaHealth['success_rate_7d'] >= 50 ? 'warning' : 'danger') }}">
                                {{ $mpesaHealth['success_rate_7d'] }}%
                            </span>
                        </div>
                    </div>

                    <hr>

                    <div>
                        <small class="text-muted">Last Success:</small><br>
                        {{ $mpesaHealth['last_success'] ? \Carbon\Carbon::parse($mpesaHealth['last_success'])->diffForHumans() : 'Never' }}
                    </div>
                    <div class="mt-2">
                        <small class="text-muted">Last Failure:</small><br>
                        {{ $mpesaHealth['last_failure'] ? \Carbon\Carbon::parse($mpesaHealth['last_failure'])->diffForHumans() : 'Never' }}
                    </div>
                </div>
            </div>
        </div>

        <!-- USSD Integration -->
        <div class="col-lg-4 mb-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-hash"></i> {{ $ussdHealth['name'] }}
                    </h5>
                    @if($ussdHealth['status'] === 'healthy')
                        <span class="badge bg-success">Healthy</span>
                    @elseif($ussdHealth['status'] === 'warning')
                        <span class="badge bg-warning">Warning</span>
                    @else
                        <span class="badge bg-danger">Critical</span>
                    @endif
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <strong>24h Activity:</strong>
                        <div class="d-flex justify-content-between">
                            <span>Total Calls:</span>
                            <span class="badge bg-primary">{{ $ussdHealth['total_calls_24h'] }}</span>
                        </div>
                        <div class="d-flex justify-content-between mt-2">
                            <span>Success Rate:</span>
                            <span class="badge bg-{{ $ussdHealth['success_rate_24h'] >= 80 ? 'success' : ($ussdHealth['success_rate_24h'] >= 50 ? 'warning' : 'danger') }}">
                                {{ $ussdHealth['success_rate_24h'] }}%
                            </span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <strong>7 Day Activity:</strong>
                        <div class="d-flex justify-content-between">
                            <span>Total Calls:</span>
                            <span class="badge bg-primary">{{ $ussdHealth['total_calls_7d'] }}</span>
                        </div>
                        <div class="d-flex justify-content-between mt-2">
                            <span>Success Rate:</span>
                            <span class="badge bg-{{ $ussdHealth['success_rate_7d'] >= 80 ? 'success' : ($ussdHealth['success_rate_7d'] >= 50 ? 'warning' : 'danger') }}">
                                {{ $ussdHealth['success_rate_7d'] }}%
                            </span>
                        </div>
                    </div>

                    <hr>

                    <div>
                        <small class="text-muted">Last Success:</small><br>
                        {{ $ussdHealth['last_success'] ? \Carbon\Carbon::parse($ussdHealth['last_success'])->diffForHumans() : 'Never' }}
                    </div>
                    <div class="mt-2">
                        <small class="text-muted">Last Failure:</small><br>
                        {{ $ussdHealth['last_failure'] ? \Carbon\Carbon::parse($ussdHealth['last_failure'])->diffForHumans() : 'Never' }}
                    </div>
                </div>
            </div>
        </div>

        <!-- SMS Integration -->
        <div class="col-lg-4 mb-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-chat-dots"></i> {{ $smsHealth['name'] }}
                    </h5>
                    @if($smsHealth['status'] === 'healthy')
                        <span class="badge bg-success">Healthy</span>
                    @elseif($smsHealth['status'] === 'warning')
                        <span class="badge bg-warning">Warning</span>
                    @else
                        <span class="badge bg-danger">Critical</span>
                    @endif
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <strong>24h Activity:</strong>
                        <div class="d-flex justify-content-between">
                            <span>Total Calls:</span>
                            <span class="badge bg-primary">{{ $smsHealth['total_calls_24h'] }}</span>
                        </div>
                        <div class="d-flex justify-content-between mt-2">
                            <span>Success Rate:</span>
                            <span class="badge bg-{{ $smsHealth['success_rate_24h'] >= 80 ? 'success' : ($smsHealth['success_rate_24h'] >= 50 ? 'warning' : 'danger') }}">
                                {{ $smsHealth['success_rate_24h'] }}%
                            </span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <strong>7 Day Activity:</strong>
                        <div class="d-flex justify-content-between">
                            <span>Total Calls:</span>
                            <span class="badge bg-primary">{{ $smsHealth['total_calls_7d'] }}</span>
                        </div>
                        <div class="d-flex justify-content-between mt-2">
                            <span>Success Rate:</span>
                            <span class="badge bg-{{ $smsHealth['success_rate_7d'] >= 80 ? 'success' : ($smsHealth['success_rate_7d'] >= 50 ? 'warning' : 'danger') }}">
                                {{ $smsHealth['success_rate_7d'] }}%
                            </span>
                        </div>
                    </div>

                    <hr>

                    <div>
                        <small class="text-muted">Last Success:</small><br>
                        {{ $smsHealth['last_success'] ? \Carbon\Carbon::parse($smsHealth['last_success'])->diffForHumans() : 'Never' }}
                    </div>
                    <div class="mt-2">
                        <small class="text-muted">Last Failure:</small><br>
                        {{ $smsHealth['last_failure'] ? \Carbon\Carbon::parse($smsHealth['last_failure'])->diffForHumans() : 'Never' }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Links -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body text-center">
                    <a href="{{ route('admin.webhooks.index') }}" class="btn btn-primary me-2">
                        <i class="bi bi-list-ul"></i> View Webhook Logs
                    </a>
                    <a href="{{ route('admin.activity.index') }}" class="btn btn-secondary">
                        <i class="bi bi-activity"></i> View Activity Feed
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
