@extends('layouts.app')

@section('title', 'Edit Benefit Rule')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <div>
                    <h4 class="mb-0">Edit Future Benefit Rule</h4>
                    <small class="text-muted">{{ $setting->setting_key }}</small>
                </div>

                <a href="{{ route('pensions-administration.settings.general.index') }}" class="btn btn-light">Back</a>
            </div>
        </div>
    </div>

    <div class="alert alert-info">
        Direct editing is only allowed because this rule has not yet taken effect.
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('pensions-administration.settings.general.update', $setting) }}">
                @csrf
                @method('PUT')

                @include('pensions-administration.settings.general._form')

                <div class="text-end">
                    <a href="{{ route('pensions-administration.settings.general.index') }}" class="btn btn-light">Cancel</a>
                    <button type="submit" class="btn btn-primary">Update Benefit Rule</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection