@extends('field.layouts.app')

@section('title', 'Settings')
@section('page-title', 'Settings')

@section('content')
<div class="page-title">
    <i class="bi bi-gear"></i>
    Account Settings
</div>
<p class="page-subtitle">Manage your profile and preferences</p>

<div class="form-section">
    <div class="form-section-title">
        <i class="bi bi-person"></i>
        Profile Information
    </div>
    <form method="POST" action="{{ route('field.settings.update') }}" class="row g-3">
        @csrf
        @method('PUT')
        <div class="col-md-6">
            <label class="form-label">Full Name</label>
            <input type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name', $user->name) }}" required>
            @error('name')<span class="invalid-feedback">{{ $message }}</span>@enderror
        </div>
        <div class="col-md-6">
            <label class="form-label">Phone Number</label>
            <input type="tel" class="form-control @error('phone') is-invalid @enderror" name="phone" value="{{ old('phone', $user->phone) }}" required>
            @error('phone')<span class="invalid-feedback">{{ $message }}</span>@enderror
        </div>
        <div class="col-md-6">
            <label class="form-label">Email Address</label>
            <input type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email', $user->email) }}">
            @error('email')<span class="invalid-feedback">{{ $message }}</span>@enderror
        </div>
        <div class="col-md-6">
            <label class="form-label">Timezone</label>
            <select class="form-select @error('timezone') is-invalid @enderror" name="timezone">
                <option value="">Select timezone...</option>
                <option value="Africa/Nairobi" {{ old('timezone', $user->timezone) === 'Africa/Nairobi' ? 'selected' : '' }}>Africa/Nairobi (EAT)</option>
                <option value="Africa/Lagos" {{ old('timezone', $user->timezone) === 'Africa/Lagos' ? 'selected' : '' }}>Africa/Lagos (WAT)</option>
                <option value="Africa/Cairo" {{ old('timezone', $user->timezone) === 'Africa/Cairo' ? 'selected' : '' }}>Africa/Cairo (EET)</option>
            </select>
            @error('timezone')<span class="invalid-feedback">{{ $message }}</span>@enderror
        </div>
        <div class="col-12">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-check-lg"></i> Save Changes
            </button>
        </div>
    </form>
</div>

<div class="form-section">
    <div class="form-section-title">
        <i class="bi bi-bell"></i>
        Notification Preferences
    </div>
    <form method="POST" action="{{ route('field.settings.update') }}" class="row g-3">
        @csrf
        @method('PUT')
        <div class="col-12">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="notification_preferences[]" value="email" id="emailNotif" checked>
                <label class="form-check-label" for="emailNotif">
                    Email notifications for withdrawals and payouts
                </label>
            </div>
        </div>
        <div class="col-12">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="notification_preferences[]" value="sms" id="smsNotif" checked>
                <label class="form-check-label" for="smsNotif">
                    SMS notifications for important updates
                </label>
            </div>
        </div>
        <div class="col-12">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-check-lg"></i> Update Preferences
            </button>
        </div>
    </form>
</div>
@endsection
