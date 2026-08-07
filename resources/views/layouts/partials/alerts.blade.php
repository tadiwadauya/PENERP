@if(session('success'))

    <div class="alert alert-success">

        <div class="alert-icon">
            <i class="bi bi-check-circle"></i>
        </div>

        <div>
            {{ session('success') }}
        </div>

    </div>

@endif


@if(session('warning'))

    <div class="alert alert-warning">

        <div class="alert-icon">
            <i class="bi bi-exclamation-triangle"></i>
        </div>

        <div>
            {{ session('warning') }}
        </div>

    </div>

@endif


@if(session('error'))

    <div class="alert alert-danger">

        <div class="alert-icon">
            <i class="bi bi-x-circle"></i>
        </div>

        <div>
            {{ session('error') }}
        </div>

    </div>

@endif


@if($errors->any())

    <div class="alert alert-danger">

        <div class="alert-icon">
            <i class="bi bi-exclamation-octagon"></i>
        </div>

        <div>

            <strong>
                Please correct the following:
            </strong>

            <ul class="alert-error-list">

                @foreach($errors->all() as $error)

                    <li>
                        {{ $error }}
                    </li>

                @endforeach

            </ul>

        </div>

    </div>

@endif