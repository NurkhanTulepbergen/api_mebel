@extends('layouts.main')

@section('title', 'Массовое удаление')
@section('favicon', '/img/bin.png')

@section('content')

    <a href="/" style="position: absolute; left: 10px; top: 10px;" class="btn btn-outline-light">Вернуться на главную</a>

    @if ($errors->any())
        <div class="alert alert-danger mb-0 alert-dismissible fade show" id="alertExample" role="alert"
            data-mdb-color="secondary">
            <i class="fas fa-triangle-exclamation"></i>
            {{ $errors->first() }}
        </div>
    @endif

    <div class="d-flex justify-content-center align-items-center">
        <div class="w-75 p-3 translucent-bg custom-container mb-4">
            <form action="{{ route('changePrice.push') }}" method="post" enctype="multipart/form-data">
                @csrf
                <div class="d-flex flex-column align-items-center mb-4">
                    <label for="formFileLg" class="pointer mb-2">Вставьте Excel Файл с ean и ценами которые вы хотите изменить</label>
                    <input class="w-50 pointer" id="formFileLg" type="file" name="excel" />
                </div>
                <div class="h-100 d-flex flex-column justify-content-center align-items-center">
                    <p class="">Из каких баз JV нужно менять?</p>
                    <button type="button" class="btn btn-outline-danger mb-2 btn-rounded w-25" data-mdb-ripple-init
                        data-mdb-ripple-color="dark" id="jvSelectAllButton" onclick="changeCheckboxesJv()">Все</button>
                    <div class="w-100 d-flex justify-content-between">
                        @foreach ($domains['jv'] as $domain)
                            <div class="checkboxes__item">
                                <label class="checkbox style-d">
                                    <input type="checkbox" value="{{ $domain->id }}" name="baseIds[]"
                                        id="jvCheck{{ $domain->id }}" />
                                    <div class="checkbox__checkmark"></div>
                                    <div class="checkbox__body">{{ $domain->link }}</div>
                                </label>
                            </div>
                        @endforeach
                    </div>
                    <div class="xl-columns justify-content-center align-items-center">
                        <p class="mt-2 mb-0">Из каких баз XL нужно менять?</p>
                        <div class="d-flex justify-content-center">
                            <button type="button" class="btn btn-outline-danger mb-2 btn-rounded w-25" data-mdb-ripple-init
                                data-mdb-ripple-color="dark" id="xlSelectAllButton"
                                onclick="changeCheckboxesXl()">Все</button>
                        </div>
                        @foreach ($domains['xl'] as $domain)
                            <div class="checkboxes__item">
                                <label class="checkbox style-d">
                                    <input type="checkbox" value="{{ $domain->id }}" name="baseIds[]"
                                        id="xlCheck{{ $domain->id }}" />
                                    <div class="checkbox__checkmark"></div>
                                    <div class="checkbox__body">{{ $domain->link }}</div>
                                </label>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="d-flex justify-content-center">
                    <button type="submit" class="btn btn-outline-danger w-25 mt-4">Удалить</button>
                </div>
            </form>
        </div>
    </div>
    <script>
        let isXlActive = false
        let isJvActive = false

        function changeCheckboxesXl() {
            isXlActive = !isXlActive
            xlCheckboxes = document.querySelectorAll('[id ^= "xlCheck"]');
            xlCheckboxes.forEach(element => {
                element.checked = isXlActive
            });
        }

        function changeCheckboxesJv() {
            isJvActive = !isJvActive
            jvCheckboxes = document.querySelectorAll('[id ^= "jvCheck"]');
            jvCheckboxes.forEach(element => {
                element.checked = isJvActive
            });
        }
    </script>
@endsection
