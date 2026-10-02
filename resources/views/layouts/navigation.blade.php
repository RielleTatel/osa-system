@php
    $user = Auth::user();
    $home = match ($user->role) {
        \App\Enums\Role::OrgOfficer => 'org.dashboard',
        \App\Enums\Role::Moderator => 'moderator.queue',
        \App\Enums\Role::OsaAdmin => 'osa-admin.queue',
        \App\Enums\Role::OsaDirector => 'osa-director.queue',
    };

    // [route name => label]; only rendered once the route exists (later tasks add them).
    $links = match ($user->role) {
        \App\Enums\Role::OrgOfficer => [
            'org.dashboard' => 'Dashboard',
            'org.requests.create' => 'New request',
        ],
        \App\Enums\Role::Moderator => [
            'moderator.queue' => 'Endorsement queue',
            'moderator.osa-form-3.index' => 'OSA Form 3',
        ],
        \App\Enums\Role::OsaAdmin => [
            'osa-admin.queue' => 'Review queue',
            'osa-admin.organizations.index' => 'Organizations',
            'osa-admin.users.index' => 'Accounts',
            'osa-admin.password-resets.index' => 'Password resets',
            'archive.index' => 'Archive',
            'tracker.index' => 'Tracker',
        ],
        \App\Enums\Role::OsaDirector => [
            'osa-director.queue' => 'Notation queue',
            'tracker.index' => 'Tracker',
            'archive.index' => 'Archive',
        ],
    };
    $links = array_filter($links, fn ($label, $name) => Route::has($name), ARRAY_FILTER_USE_BOTH);
@endphp

<nav x-data="{ open: false }" class="bg-navy-900 text-white border-b border-navy-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo / wordmark -->
                <a href="{{ route($home) }}" class="shrink-0 flex items-center gap-2">
                    <span class="w-8 h-8 rounded-full border-2 border-gold-500 flex items-center justify-center">
                        <x-heroicon-o-pencil class="w-4 h-4 text-gold-500" />
                    </span>
                    <span class="hidden sm:block leading-tight">
                        <span class="block text-sm font-semibold tracking-wide">OSA</span>
                        <span class="block text-[10px] uppercase tracking-widest text-slate-200">Activity Requests</span>
                    </span>
                </a>

                <div class="hidden space-x-6 sm:-my-px sm:ms-10 sm:flex">
                    @foreach($links as $name => $label)
                        <a href="{{ route($name) }}"
                           @class([
                               'inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium transition',
                               'border-gold-500 text-white' => request()->routeIs($name),
                               'border-transparent text-slate-200 hover:text-white hover:border-slate-500' => ! request()->routeIs($name),
                           ])>
                            {{ $label }}
                        </a>
                    @endforeach
                </div>
            </div>

            <div class="hidden sm:flex sm:items-center sm:gap-3 sm:ms-6">
                @if(Route::has('notifications.index'))
                    <a href="{{ route('notifications.index') }}" class="relative p-2 text-slate-200 hover:text-white" title="Notifications">
                        <x-heroicon-o-bell class="w-5 h-5" />
                        @if($user->unreadNotifications()->count())
                            <span class="absolute top-1.5 right-1.5 w-2 h-2 rounded-full bg-gold-500"></span>
                        @endif
                    </a>
                @endif

                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium rounded-md text-slate-200 hover:text-white focus:outline-none transition">
                            <span>{{ $user->name }}</span>
                            <x-eyebrow-badge class="hidden lg:inline-block">{{ $user->role->label() }}</x-eyebrow-badge>
                            <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">{{ __('Profile') }}</x-dropdown-link>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault(); this.closest('form').submit();">
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-slate-200 hover:text-white hover:bg-navy-800 focus:outline-none transition">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden bg-navy-800">
        <div class="pt-2 pb-3 space-y-1">
            @foreach($links as $name => $label)
                <a href="{{ route($name) }}"
                   @class([
                       'block ps-3 pe-4 py-2 border-l-4 text-base font-medium transition',
                       'border-gold-500 text-white bg-navy-900' => request()->routeIs($name),
                       'border-transparent text-slate-200 hover:text-white hover:border-slate-500' => ! request()->routeIs($name),
                   ])>
                    {{ $label }}
                </a>
            @endforeach
        </div>
        <div class="pt-4 pb-1 border-t border-navy-700">
            <div class="px-4">
                <div class="font-medium text-base text-white">{{ $user->name }}</div>
                <div class="font-medium text-sm text-slate-200">{{ $user->email }}</div>
            </div>
            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">{{ __('Profile') }}</x-responsive-nav-link>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault(); this.closest('form').submit();">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
