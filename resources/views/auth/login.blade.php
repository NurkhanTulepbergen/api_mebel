@extends('layouts.main')

@section('title', 'Login')
@section('favicon', '/img/bin.png')

@section('content')
    <div class="d-flex justify-content-center">
        <div class="card shadow-5-strong p-4" style="width: 100%; max-width: 400px; border-radius: 16px;">
            <form method="POST" action="{{ route('login.submit') }}">
                @csrf
                <div data-mdb-input-init class="form-outline mb-4">
                    <input type="email" class="form-control" name="email" value="{{ old('email') }}" required/>
                    <label class="form-label" for="form1Example1">Email address</label>
                </div>
                <div data-mdb-input-init class="form-outline mb-4">
                    <input type="password" id="form1Example2" name="password" class="form-control" required/>
                    <label class="form-label" for="form1Example2">Password</label>
                </div>

                @error('email')
                    <div>{{ $message }}</div>
                @enderror

                <button data-mdb-ripple-init type="submit" class="btn btn-primary btn-block">Sign in</button>
            </form>
        </div>
    </div>
</body>
</html>

@endsection
