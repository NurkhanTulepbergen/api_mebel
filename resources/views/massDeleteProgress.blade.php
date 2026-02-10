<!-- resources/views/child.blade.php -->

@extends('layouts.main')

@section('title', 'Массовое удаление')
@section('favicon', '/img/bin.png')

@section('content')

    @if ($errors->any())
        <div class="alert alert-danger mb-0 alert-dismissible fade show" id="alertExample" role="alert"
            data-mdb-color="secondary">
            <i class="fas fa-triangle-exclamation"></i>
            {{ $errors->first() }}
        </div>
    @endif

    <div class="d-flex justify-content-center align-items-center">
        <div class="w-50 p-3 translucent-bg custom-container mb-4">
            <p>Текущий статус: <span id="mass-delete-status"></span></p>
            @if ($isJv)
                <p>Удалено JV: <span id="jv_count"></span></p>
            @endif
            @if ($isXl)
                <p>Удалено XL: <span id="xl_count"></span></p>
            @endif
        </div>
    </div>

    <script>
document.addEventListener('DOMContentLoaded', () => {
  const statusEl = document.getElementById('mass-delete-status');
  const jvEl     = document.getElementById('jv_count');
  const xlEl     = document.getElementById('xl_count');

    setInterval(() => {
        fetch('http://xl-mebel-api.test/api/mass-delete')
        .then(res => res.json())
        .then(data => {
            if (statusEl && data.status !== undefined) {
            statusEl.textContent = data.status;
            }

            if (jvEl) {
            if (data.jv_count !== undefined) {
                jvEl.textContent = data.jv_count;
                jvEl.closest('p').style.display = 'block';
            } else {
                jvEl.closest('p').style.display = 'none';
            }
            }

            if (xlEl) {
            if (data.xl_count !== undefined) {
                xlEl.textContent = data.xl_count;
                xlEl.closest('p').style.display = 'block';
            } else {
                xlEl.closest('p').style.display = 'none';
            }
            }
        })
        .catch(console.error);
    }, 500);
    });
    </script>
@endsection
