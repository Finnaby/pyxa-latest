<?php

namespace App\Extensions\AIChatPro\System\Http\Controllers;

use App\Helpers\Classes\Helper;
use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\Common\MenuService;
use Illuminate\Http\Request;

class AIChatProSettingsController extends Controller
{
    public function index()
    {
        return view('ai-chat-pro::settings.index');
    }

    public function update(Request $request): \Illuminate\Http\RedirectResponse
    {
        if (Helper::appIsNotDemo()) {
            $request->validate([
                'ai_chat_display_type'           => 'required|in:menu,ai_chat,both_fm,frontend',
                'guest_user_daily_message_limit' => 'required',
            ]);

            $suggestions = collect($request->input('input_name'))
                ->zip($request->input('input_prompt'))
                ->map(function ($pair) {
                    return [
                        'name'   => trim($pair[0]),
                        'prompt' => trim($pair[1]),
                    ];
                })
                ->filter(fn ($item) => $item['name'] && $item['prompt']) // Safety net
                ->values()
                ->all();
            $suggestions = json_encode($suggestions, JSON_THROW_ON_ERROR);

            setting([
                'ai_chat_display_type'           => $request->ai_chat_display_type,
                'guest_user_daily_message_limit' => $request->guest_user_daily_message_limit,
                'ai_chat_pro_suggestions'        => $suggestions,
            ])->save();

            $setting = Setting::getCache();
            if (in_array($request->ai_chat_display_type, ['both_fm', 'frontend'])) {
                $setting->frontend_additional_url = '/chat';
            } else {
                $setting->frontend_additional_url = null;
            }
            $setting->save();

            Setting::forgetCache();
            app(MenuService::class)->regenerate();
        }

        return back()->with(['message' => __('Updated Successfully'), 'type' => 'success']);
    }
}
