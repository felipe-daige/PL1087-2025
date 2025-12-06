<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Simulador IRPF 2026 - Lei 15.270')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            background-color: #eff6ff;
            color: #0f172a;
        }
        .chart-container {
            position: relative;
            width: 100%;
            max-width: 100%;
            height: 250px;
            max-height: 300px;
            margin-left: auto;
            margin-right: auto;
        }
        @media (min-width: 768px) {
            .chart-container {
                height: 300px;
            }
        }
        .step-content {
            display: none;
            animation: fadeIn 0.3s ease-in-out;
        }
        .step-content.active {
            display: block;
        }
        /* Fix select styling for Safari */
        select.form-select {
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            background-color: #fff;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20' fill='%236b7280'%3e%3cpath fill-rule='evenodd' d='M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z' clip-rule='evenodd'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 0.5rem center;
            background-size: 1.25rem 1.25rem;
            padding-right: 2rem;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .dashboard-scroll::-webkit-scrollbar {
            width: 6px;
        }
        .dashboard-scroll::-webkit-scrollbar-track {
            background: #dbeafe;
        }
        .dashboard-scroll::-webkit-scrollbar-thumb {
            background-color: #93c5fd;
            border-radius: 20px;
        }
        .nav-tab.active {
            color: #1d4ed8;
            border-bottom-color: #2563eb;
        }
        html {
            scroll-behavior: smooth;
        }
        .doc-section {
            scroll-margin-top: 120px;
        }
    </style>
    @stack('styles')
</head>
<body class="flex flex-col min-h-screen">
    <x-layout.header :submitted="$submitted ?? false">
        <x-slot name="nav">
            @hasSection('header-nav')
                @yield('header-nav')
            @endif
        </x-slot>
    </x-layout.header>

    @yield('content')

    <x-layout.footer />

    @stack('scripts')
</body>
</html>
