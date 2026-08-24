@if(config('services.turnstile.enabled') && config('services.turnstile.site_key'))
    <div class="my-3.5 flex flex-col items-center justify-center">
        <div class="cf-turnstile" data-sitekey="{{ config('services.turnstile.site_key') }}" data-theme="dark"></div>
        @if(isset($errors) && $errors->has('cf-turnstile-response'))
            <p class="text-xs text-rose-400 mt-1.5 text-center font-semibold font-sans">{{ $errors->first('cf-turnstile-response') }}</p>
        @endif
    </div>
@endif
