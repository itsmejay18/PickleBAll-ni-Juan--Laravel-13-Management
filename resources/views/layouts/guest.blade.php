<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Pickle Ballan ni Juan') }}</title>
        <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/branding.png') }}">
        <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700,800" rel="stylesheet">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body>
        <main class="main-content mt-0">
            <section>
                <div class="page-header min-vh-100">
                    <div class="container">
                        <div class="row">
                            <div class="col-xl-4 col-lg-5 col-md-7 d-flex flex-column mx-auto">
                                <div class="card card-plain mt-8">
                                    <div class="card-header pb-0 text-left bg-transparent">
                                        <div class="d-flex align-items-center mb-2">
                                            <h3 class="font-weight-bolder text-info text-gradient mb-0">Pickle Ballan ni Juan</h3>
                                        </div>
                                        <p class="mb-0">Court reservation and management system</p>
                                    </div>
                                    <div class="card-body">
                                        {{ $slot }}
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="oblique position-absolute top-0 h-100 d-md-block d-none me-n8">
                                    <div class="oblique-image bg-cover position-absolute fixed-top ms-auto h-100 z-index-0 ms-n6" style="background-image:linear-gradient(135deg, rgba(15, 23, 42, 0.16), rgba(15, 23, 42, 0.02)), url('{{ asset('soft-ui-dashboard-main/assets/img/curved-images/curved6.jpg') }}'); background-position:center;"></div>
                                    <div style="position:absolute; inset:0; z-index:1; display:flex; align-items:center; justify-content:center; pointer-events:none; transform:skewX(10deg);">
                                        <img src="{{ asset('images/branding.png') }}" alt="Pickle Ballan ni Juan" style="width:clamp(240px, 23vw, 380px); height:auto; margin-left:-5rem; clip-path:circle(49% at 50% 50%); filter:drop-shadow(0 24px 32px rgba(15, 23, 42, 0.3));">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </body>
</html>
