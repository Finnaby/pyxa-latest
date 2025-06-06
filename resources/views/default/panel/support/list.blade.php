@extends('panel.layout.app')
@section('title', __('Support Requests'))
@section('titlebar_actions')
    @if (!Auth::user()->isAdmin())
        <x-button href="{{ route('dashboard.support.new') }}">
            {{ __('Create New Support Request') }}
            <x-tabler-plus class="size-4" />
        </x-button>
    @endif
@endsection
@section('content')
    <div class="py-10">
        <x-table>
            <x-slot:head>
                <tr>
                    <th>
                        {{ __('Ticked ID') }}
                    </th>
                    <th>
                        Name
                    </th>
                    <th>
                        {{ __('Status') }}
                    </th>
                    <th>
                        {{ __('Category') }}
                    </th>
                    <th>
                        {{ __('Subject') }}
                    </th>
                    <th>
                        {{ __('Priority') }}
                    </th>
                     @if (Auth::user()->isAdmin())
                    <th>
                        {{ __('Username') }}
                    </th>
                    <th>
                        {{ __('Password') }}
                    </th>
                    <th>
                        {{ __('Created At') }}
                    </th>
                    @endif
                    <th>
                        {{ __('Last Updated') }}
                    </th>
                    <th>{{ __('Attachment') }}</th>
                    <th class="text-end">
                        {{ __('Actions') }}
                    </th>
                </tr>
            </x-slot:head>
            <x-slot:body>
                @foreach ($items as $entry)
                    <tr>
                        <td>
                            {{ $entry->ticket_id }}
                        </td>
                         <td>
                            {{ $entry->user->name ?? '' }} {{ $entry->user->surname ?? '' }}
                        </td>
                        <td>
                            <x-badge
                                class="whitespace-nowrap text-2xs"
                                variant="{{ $entry->status === 'Answered' ? 'success' : 'secondary' }}"
                            >
                                {{ __($entry->status) }}
                            </x-badge>
                        </td>
                        <td>
                            {{ __($entry->category) }}
                        </td>
                        <td>
                            {{ __($entry->subject) }}
                        </td>
                        <td>
                            {{ __($entry->priority) }}
                        </td>
                         @if (Auth::user()->isAdmin())
                        <td>
                            {{ __($entry->username) }}
                        </td>
                         <td>
                            {{ __($entry->password) }}
                        </td>
                        @endif
                        <td>
                            {{ $entry->created_at }}
                        </td>
                        <td>
                            {{ $entry->updated_at }}
                        </td>
                        <td class="text-center">
                            @if ($entry->attachment)
                                <a href="{{ asset('uploads/' . $entry->attachment) }}" download>
                                    <x-tabler-download class="size-5 text-blue-600 cursor-pointer" title="{{ __('Download Attachment') }}" />
                                </a>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap text-end">
                            <x-button
                                size="sm"
                                href="{{ route('dashboard.support.view', $entry->ticket_id) }}"
                            >
                                {{ __('View') }}
                            </x-button>
                        </td>
                    </tr>
                @endforeach

            </x-slot:body>
        </x-table>
    </div>
@endsection

@push('script')
    <script src="{{ custom_theme_url('/assets/libs/tom-select/dist/js/tom-select.base.min.js') }}"></script>
    <script src="{{ custom_theme_url('/assets/js/panel/support.js') }}"></script>
@endpush
