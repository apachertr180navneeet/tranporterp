<!DOCTYPE html>
<html ng-app="{{ config('app.name') }}" lang="en" class="light-style layout-menu-fixed layout-compact" dir="ltr" data-theme="theme-default"
    data-assets-path="../assets/" data-template="vertical-menu-template-free">
    <head>
        <meta charset="utf-8" />
        <title>{{ config('app.name') }}</title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <meta name="description" content="" />
        <meta name="csrf-token" content="{{ csrf_token() }}" />
        <meta name="ws_url" content="{{ env('WS_URL') }}">
        <meta name="user_id" content="{{ Auth::id() }}">
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
        <link href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet"/>
        <link rel="stylesheet" href="{{asset('assets/admin/vendor/fonts/boxicons.css')}}" />
        <link rel="stylesheet" href="{{asset('assets/admin/vendor/css/core.css')}}" class="template-customizer-core-css" />
        <link rel="stylesheet" href="{{asset('assets/admin/vendor/css/theme-default.css')}}" class="template-customizer-theme-css" />
        <link rel="stylesheet" href="{{asset('assets/admin/css/demo.css')}}" />
        <link rel="stylesheet" href="{{asset('assets/admin/css/custom.css')}}" />
        <link rel="stylesheet" href="{{asset('assets/admin/css/bootstrapDataTable.css')}}" />
        <link rel="stylesheet" href="{{asset('assets/admin/vendor/libs/perfect-scrollbar/perfect-scrollbar.css')}}" />
        <link rel="stylesheet" href="{{asset('assets/admin/vendor/libs/apex-charts/apex-charts.css')}}" />
        <script src="{{asset('assets/admin/vendor/js/helpers.js')}}"></script>
        <script src="{{asset('assets/admin/js/config.js')}}"></script>
                <script src="{{asset('assets/admin/js/bootstrapDataTable.js')}}"></script>
                <script src="{{asset('assets/admin/js/dashboards-analytics.js')}}"></script>
                <script src="{{asset('assets/admin/js/moment.min.js')}}"></script>
                <script async defer src="https://buttons.github.io/buttons.js"></script>
                @yield('script')
                @include('admin.layouts.elements.sweet_alerts')
            </div>
            <div class="layout-overlay mobile-menu-overlay"></div>
        </div>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                var menu = document.getElementById('layout-menu');
                var overlay = document.querySelector('.mobile-menu-overlay');
                var closeBtn = document.querySelector('.menu-close-btn');

                if (!menu) return;

                function openMobileMenu() {
                    if (!menu || window.innerWidth > 992) return;
                    menu.classList.add('show-menu');
                    if (overlay) overlay.classList.add('active');
                    document.body.style.overflow = 'hidden';
                }

                function closeMobileMenu() {
                    if (!menu) return;
                    menu.classList.remove('show-menu');
                    if (overlay) overlay.classList.remove('active');
                    document.body.style.overflow = '';
                }

                document.querySelectorAll('.layout-menu-toggle a').forEach(function(toggle) {
                    toggle.addEventListener('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        if (menu.classList.contains('show-menu')) {
                            closeMobileMenu();
                        } else {
                            openMobileMenu();
                        }
                    });
                });

                if (closeBtn) {
                    closeBtn.addEventListener('click', function(e) {
                        e.preventDefault();
                        closeMobileMenu();
                    });
                }

                if (overlay) {
                    overlay.addEventListener('click', function() {
                        closeMobileMenu();
                    });
                }

                var menuLinks = menu.querySelectorAll('.menu-link');
                menuLinks.forEach(function(link) {
                    link.addEventListener('click', function() {
                        if (window.innerWidth <= 992) {
                            setTimeout(closeMobileMenu, 200);
                        }
                    });
                });

                var resizeTimer;
                window.addEventListener('resize', function() {
                    clearTimeout(resizeTimer);
                    resizeTimer = setTimeout(function() {
                        if (window.innerWidth > 992) {
                            closeMobileMenu();
                        }
                    }, 100);
                });

                window.closeMobileMenu = closeMobileMenu;
            });
        </script>
    </body>
</html>