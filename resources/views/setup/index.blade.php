<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('setup.title') }} · {{ config('app.name', 'VersionTracker') }}</title>
    <style>
        :root { color-scheme: light; font-family: Inter, ui-sans-serif, system-ui, sans-serif; }
        body { margin: 0; min-height: 100vh; background: #f3f5f1; color: #172019; }
        main { width: min(42rem, calc(100% - 2rem)); margin: 4rem auto; }
        section { padding: 2rem; border: 1px solid #dce3dc; border-radius: 1rem; background: white; box-shadow: 0 1rem 3rem rgb(23 32 25 / 8%); }
        h1 { margin: 0; font-size: 1.75rem; }
        p { color: #536158; line-height: 1.6; }
        .grid { display: grid; gap: 1rem; grid-template-columns: repeat(2, minmax(0, 1fr)); }
        label { display: grid; gap: .4rem; font-size: .875rem; font-weight: 600; }
        label.full { grid-column: 1 / -1; }
        input, select { width: 100%; box-sizing: border-box; border: 1px solid #b9c6ba; border-radius: .5rem; padding: .7rem .75rem; font: inherit; }
        button { border: 0; border-radius: .5rem; padding: .75rem 1rem; background: #1e5631; color: white; font: inherit; font-weight: 700; cursor: pointer; }
        .errors { margin: 1rem 0; padding: .75rem 1rem; border-radius: .5rem; background: #fff1f0; color: #9b1c1c; }
        .actions { display: flex; justify-content: flex-end; margin-top: 1.5rem; }
        @media (max-width: 640px) { .grid { grid-template-columns: 1fr; } label.full { grid-column: auto; } main { margin: 1rem auto; } section { padding: 1.25rem; } }
    </style>
</head>
<body>
<main>
    <section>
        <h1>{{ __('setup.title') }}</h1>
        <p>{{ __('setup.description') }}</p>

        @if ($errors->any())
            <div class="errors" role="alert">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('setup.store') }}">
            @csrf
            <div class="grid">
                <label class="full">{{ __('setup.fields.token') }}
                    <input type="password" name="token" required autocomplete="off">
                </label>
                <label>{{ __('setup.fields.name') }}
                    <input type="text" name="admin_name" value="{{ old('admin_name') }}" required autocomplete="name">
                </label>
                <label>{{ __('setup.fields.email') }}
                    <input type="email" name="admin_email" value="{{ old('admin_email') }}" required autocomplete="email">
                </label>
                <label>{{ __('setup.fields.password') }}
                    <input type="password" name="password" required autocomplete="new-password">
                </label>
                <label>{{ __('setup.fields.password_confirmation') }}
                    <input type="password" name="password_confirmation" required autocomplete="new-password">
                </label>
                <label>{{ __('setup.fields.application_name') }}
                    <input type="text" name="application_name" value="{{ old('application_name', 'VersionTracker') }}">
                </label>
                <label>{{ __('setup.fields.support_url') }}
                    <input type="url" name="support_url" value="{{ old('support_url') }}">
                </label>
                <label>{{ __('setup.fields.default_locale') }}
                    <select name="default_locale">
                        <option value="de" @selected(old('default_locale', 'de') === 'de')>Deutsch</option>
                        <option value="en" @selected(old('default_locale') === 'en')>English</option>
                    </select>
                </label>
                <label>{{ __('setup.fields.fallback_locale') }}
                    <select name="fallback_locale">
                        <option value="de" @selected(old('fallback_locale', 'en') === 'de')>Deutsch</option>
                        <option value="en" @selected(old('fallback_locale', 'en') === 'en')>English</option>
                    </select>
                </label>
                <label>{{ __('setup.fields.mail_from_address') }}
                    <input type="email" name="mail_from_address" value="{{ old('mail_from_address') }}">
                </label>
                <label>{{ __('setup.fields.mail_from_name') }}
                    <input type="text" name="mail_from_name" value="{{ old('mail_from_name', 'VersionTracker') }}">
                </label>
            </div>
            <div class="actions">
                <button type="submit">{{ __('setup.actions.complete') }}</button>
            </div>
        </form>
    </section>
</main>
</body>
</html>
