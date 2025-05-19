@php
    use App\Extensions\SocialMedia\System\Enums\PlatformEnum;
@endphp

@extends('panel.layout.app', ['disable_tblr' => true])
@section('title', __('AI Social Media Suite'))
@section('subtitle', __('AI Social Media Suite'))

@section('titlebar_actions')
    <x-button
        href="{{ route('dashboard.user.social-media.platforms') }}"
        variant="ghost-shadow"
    >
        @lang('Connect Accounts')
    </x-button>

    @include('social-media::components.create-post-dropdown', ['platforms' => $platforms])
@endsection

@section('content')
    <div class="py-10">
        <div class="space-y-12">
            @include('social-media::components.home.banner')

            <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
                @include('social-media::components.home.platform-cards', ['platforms' => $platforms])
                @include('social-media::components.home.published-posts-chart', ['platforms_published_posts' => $platforms_published_posts])
            </div>

            @include('social-media::components.home.overview-grid', ['posts_stats' => $posts_stats])

            @include('social-media::components.home.posts-grid', ['platforms' => $platforms, 'posts' => $posts])

            @include('social-media::components.home.accounts', ['platforms' => $platforms])

            @include('social-media::components.home.tools')
        </div>

        {{-- blade-formatter-disable --}}
        <svg class="absolute h-0 w-0" width="44" height="44" viewBox="0 0 44 44" fill="none" xmlns="http://www.w3.org/2000/svg" > <defs> <linearGradient id="social-posts-overview-gradient" x1="9.16667" y1="15.1507" x2="32.6556" y2="31.9835" gradientUnits="userSpaceOnUse" > <stop stop-color="#82E2F4" /> <stop offset="0.502" stop-color="#8A8AED" /> <stop offset="1" stop-color="#6977DE" /> </linearGradient> <linearGradient id="paint1_linear_48_9" x1="16.5" y1="31.3707" x2="16.706" y2="33.0364" gradientUnits="userSpaceOnUse" > <stop stop-color="#82E2F4" /> <stop offset="0.502" stop-color="#8A8AED" /> <stop offset="1" stop-color="#6977DE" /> </linearGradient> <linearGradient id="paint2_linear_48_9" x1="16.5" y1="17.996" x2="22.6718" y2="24.8005" gradientUnits="userSpaceOnUse" > <stop stop-color="#82E2F4" /> <stop offset="0.502" stop-color="#8A8AED" /> <stop offset="1" stop-color="#6977DE" /> </linearGradient> <linearGradient id="paint3_linear_48_9" x1="27.5" y1="6.248" x2="28.9101" y2="6.58719" gradientUnits="userSpaceOnUse" > <stop stop-color="#82E2F4" /> <stop offset="0.502" stop-color="#8A8AED" /> <stop offset="1" stop-color="#6977DE" /> </linearGradient> <linearGradient id="paint4_linear_48_9" x1="33" y1="8.08133" x2="36.0763" y2="10.7947" gradientUnits="userSpaceOnUse" > <stop stop-color="#82E2F4" /> <stop offset="0.502" stop-color="#8A8AED" /> <stop offset="1" stop-color="#6977DE" /> </linearGradient> <linearGradient id="paint5_linear_48_9" x1="34.8333" y1="16.704" x2="35.3107" y2="18.2477" gradientUnits="userSpaceOnUse" > <stop stop-color="#82E2F4" /> <stop offset="0.502" stop-color="#8A8AED" /> <stop offset="1" stop-color="#6977DE" /> </linearGradient> </defs> </svg>
		{{-- blade-formatter-enable --}}
    </div>
@endsection
