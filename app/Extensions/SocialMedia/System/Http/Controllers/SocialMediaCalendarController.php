<?php

namespace App\Extensions\SocialMedia\System\Http\Controllers;

use App\Extensions\SocialMedia\System\Models\SocialMediaPost;
use App\Http\Controllers\Controller;

class SocialMediaCalendarController extends Controller
{
    public function __invoke()
    {
        $items = SocialMediaPost::query()
            ->whereDate('scheduled_at', '>=', now()->startOfMonth()->format('Y-m-d'))
            ->whereDate('scheduled_at', '<=', now()->endOfMonth()->format('Y-m-d'))
            ->get();

        return view('social-media::calendar', [
            'items' => $items,
        ]);
    }
}
