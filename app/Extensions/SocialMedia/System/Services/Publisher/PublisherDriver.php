<?php

namespace App\Extensions\SocialMedia\System\Services\Publisher;

use App\Extensions\SocialMedia\System\Models\SocialMediaPost;
use App\Extensions\SocialMedia\System\Services\Publisher\Contracts\BasePublisherService;

class PublisherDriver
{
    public SocialMediaPost $post;

    public function setPost(SocialMediaPost $post): self
    {
        $this->post = $post;

        return $this;
    }

    public function getPost(): SocialMediaPost
    {
        return $this->post;
    }

    public function getDriver(): BasePublisherService
    {
        $driver = match ($this->post->platform->platform) {
            'instagram'      => app(InstagramService::class),
            'facebook'       => app(FacebookService::class),
            'linkedin'       => app(LinkedinService::class),
            'x'              => app(XService::class),
            'tiktok'         => app(TiktokService::class),
            default          => app(LinkedinService::class),
        };

        $driver
            ->setPost($this->post)
            ->setPlatform($this->post->platform);

        return $driver;
    }
}
