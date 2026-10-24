{{-- EduBridge Automatic Flash Session to Glassmorphic Toast Bridge --}}
@if(session('success') || session('status') || session('error') || session('warning') || session('info'))
    <div id="laravel-flash-data"
         data-success="{{ session('success') ?? session('status') }}"
         data-error="{{ session('error') }}"
         data-warning="{{ session('warning') }}"
         data-info="{{ session('info') }}"
         style="display:none;"
         aria-hidden="true"></div>
@endif
