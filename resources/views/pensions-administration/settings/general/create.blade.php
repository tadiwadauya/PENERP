@extends('layouts.app')

@section('title', 'Add Benefit Rule')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="mb-0">Add Benefit Rule</h4>
                <a href="{{ route('pensions-administration.settings.general.index') }}" class="btn btn-light">Back</a>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('pensions-administration.settings.general.store') }}">
                @csrf

                @include('pensions-administration.settings.general._form')

                <div class="text-end">
                    <a href="{{ route('pensions-administration.settings.general.index') }}" class="btn btn-light">Cancel</a>
                    <button type="submit" class="btn btn-primary">Save Benefit Rule</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection