<?php

namespace App\Extensions\AiPersona\System\Http\Controllers;

use App\Domains\Entity\Enums\EntityEnum;
use App\Domains\Entity\Facades\Entity;
use App\Extensions\AiPersona\System\Models\AiPersona;
use App\Extensions\AiPersona\System\Services\AiPersonaService;
use App\Helpers\Classes\Helper;
use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Pagination\LengthAwarePaginator;

class AiPersonaController extends Controller
{
    public function __construct(
        public AiPersonaService $service
    ) {}

    public function index(): View
    {
        $perPage = 10;
        $page = request()->get('page', 1);

        // Get paginated AiPersona entries ordered by in_progress first
        $avatars = AiPersona::query()
            ->where('user_id', auth()->id())
            ->orderByRaw("FIELD(status, 'in_progress') DESC")
            ->orderBy('created_at', 'desc')                
            ->paginate($perPage, ['*'], 'page', $page);

        // Map over the paginated items and fetch video details
        $detailedVideos = $avatars->getCollection()->map(function ($persona) {
            $detail = $this->service->retrieveVideo($persona->avatar_id)['data'];
            return array_merge($detail, ['video_id' => $persona->avatar_id]);
        });

        // Wrap the mapped collection back into paginator
        $paginatedVideos = new LengthAwarePaginator(
            $detailedVideos,
            $avatars->total(),
            $avatars->perPage(),
            $avatars->currentPage(),
            ['path' => request()->url(), 'query' => request()->query()]
        );

        $inProgress = AiPersona::query()
            ->where('user_id', auth()->id())
            ->where('status', 'in_progress')
            ->pluck('avatar_id')
            ->toArray();

      return view('ai-persona::index', [
            'list'       => $paginatedVideos,
            'inProgress' => $inProgress,
        ] );
    }

    public function create(): View
    {
        return view('ai-persona::create', [
            'avatars' => $this->service->listAvatars()['data']['avatars'] ?? [],
            'voices'  => $this->service->listVoices()['data']['voices'] ?? [],
        ]);
    }

    public function store(Request $request)
    {
        if (Helper::appIsDemo()) {
            return response()->json([
                'status'  => 'error',
                'message' => trans('This feature is disabled in demo mode.'),
            ]);
        }
        
        if (strlen($request->get('input_text')) > 2000) {
             return redirect()->back()->with([
                'message' => trans('Speech content cannot exceed 2000 characters.'),
                'type'    => 'error',
            ]);
        }

        $avatarSettings = [
            'type'         => 'avatar',
            'avatar_id'    => $request->get('avatar_id'),
            'avatar_style' => $request->get('avatar_style'),
        ];

        $voiceSettings = [
            'type'       => 'text',
            'input_text' => $request->get('input_text'),
            'voice_id'   => $request->get('voice_id'),
        ];

        $videoInputs = [
            'character' => $avatarSettings,
            'voice'     => $voiceSettings,
        ];

        $body = [
            'video_inputs' => [$videoInputs],
            'caption'      => $request->get('caption'),
            'dimension'    => [
                'width'  => 1920,
                'height' => 1080,
            ],
        ];

        $driver = Entity::driver(EntityEnum::HEYGEN)->inputVideoCount(1)->calculateCredit();

        try {
            $driver->redirectIfNoCreditBalance();
        } catch (Exception $e) {
            return redirect()->back()->with([
                'message' => 'You have no credits left. Please consider upgrading your plan.',
                'type'    => 'error',
            ]);
        }

        $service = new AiPersonaService;
        $response = $service->createVideo($body);

        if ($response['error'] == null) {

            AiPersona::query()->create([
                'user_id'   => auth()->user()->id,
                'avatar_id' => $response['data']['video_id'],
                'status'    => 'in_progress',
            ]);

            $driver->decreaseCredit();

            return redirect()->route('dashboard.user.ai-persona.index')->with([
                'message' => __('Video Created Successfully'),
                'type'    => 'success',
            ]);
        } else {
            return redirect()->back()->with([
                'message' => $response['error']['message'],
                'type'    => 'error',
            ]);
        }

    }

    /**
     * Remove the specified resource from storage.
     */
    public function delete(string $id)
    {
        if (Helper::appIsDemo()) {
            return response()->json([
                'status'  => 'error',
                'message' => trans('This feature is disabled in demo mode.'),
            ]);
        }

        $model = $this->service->deleteVideo($id);

        if ($model->getStatusCode() === 200 || $model->getStatusCode() === 204) {

            $builder = AiPersona::query()->where('avatar_id', $id)->first();

            $builder->delete();

            return back()->with(['message' => __('Deleted Successfully'), 'type' => 'success']);
        } else {
            return back()->with(['message' => __('Delete Failed'), 'type' => 'danger']);
        }
    }

    public function checkVideoStatus(Request $request): \Illuminate\Http\JsonResponse
    {
        $ids = $request->get('ids');

        if (! $ids) {
            return response()->json(['data' => []]);
        }

        $service = new AiPersonaService;

        $list = $service->listVideos()['data']['videos'];

        $data = [];

        foreach ($list as $entry) {
            if (in_array($entry['video_id'], $ids) && $entry['status'] === 'completed') {
                $detail = $this->service->retrieveVideo($entry['video_id'])['data'];
                $data = array_merge($detail, $entry);

                $data[] = [
                    'divId' => 'video-' . $data['video_id'],
                    'html'  => view('ai-persona::video-item', ['entry' => $data])->render(),
                ];

                AiPersona::query()->where('avatar_id', $entry['video_id'])->update([
                    'status' => 'completed',
                ]);
            }
        }

        return response()->json(['data' => $data]);
    }
}
