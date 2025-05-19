<?php

namespace App\Extensions\SocialMedia\System\Console\Commands;

use App\Extensions\SocialMedia\System\Models\SocialMediaPlatform;
use App\Extensions\SocialMedia\System\Services\Token\XRefreshAccessToken;
use Illuminate\Console\Command;

class XRefreshTokenCommand extends Command
{
    protected $signature = 'app:social-media-x-refresh';

    protected $description = 'Refresh token for X (formerly Twitter)';

    public function handle(): void
    {
        $tokens = SocialMediaPlatform::query()
            ->where('platform', 'x')
            ->where('expires_at', '<', now()->subMinutes(10)->format('Y-m-d h:i:s'))
            ->get();

        $service = app(XRefreshAccessToken::class);

        foreach ($tokens as $token) {
            $service->setPlatform($token)->generate();
        }
    }
}
