<?php

namespace App\Extensions\AIPlagiarism\System\Http\Controllers;

use App\Helpers\Classes\Helper;
use App\Http\Controllers\Controller;
use App\Models\OpenAIGenerator;
use App\Models\SettingTwo;
use App\Models\UserOpenai;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Domains\Engine\Enums\EngineEnum;
use App\Domains\Entity\Enums\EntityEnum;
use App\Domains\Entity\Facades\Entity;
use Throwable;
use Illuminate\Http\JsonResponse;

class PlagiarismController extends Controller
{
    
    public function plagiarismCheck(Request $request)
{
   
    ini_set('max_execution_time', 240);
    
    $settings = SettingTwo::first();
    
    $plagi = EngineEnum::PLAGIARISM_CHECK;
    
    $wordCount = str_word_count($request->text);
    
    $model = $this->getAIModel($plagi->value);
    
    $driver = Entity::driver($model)->input($request->text)->calculateCredit();
   

    if (! $driver->hasCreditBalanceForInput()) {
        throw new Exception(__('Insufficient credits to generate output.'));
        // return $this->sendErrorResponse(__('Insufficient credits to generate output.'));
    }
            
    if($settings->plagiarism_key == ""){
        return response()->json(['message' => 'Please input plagiarism api key'], 401);
    }

    try {
        $client = new Client([
            'base_uri' => 'https://api.gowinston.ai/v2/plagiarism/' // Ensure correct base URI
        ]);
        

        $headers = [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $settings->plagiarism_key,
        ];
        
        $data = [
            'language' => 'en',
            'text' => $request->text
        ];

        // POST request to the plagiarism check API
        $response = $client->post('', [
            'headers' => $headers,
            'json' => $data
        ]);
        
        $result = json_decode($response->getBody()->getContents());
        
        
        
        if($result->status == 200 )
        {
           
            $driver->decreaseCredit();
            
            return response()->json(['$result' => $result]);
        }
         else {
            return response()->json(['message' => 'Error in plagiarism api'], 401);
        }
    } catch (Exception $e) {
       
        
        if ($e->hasResponse()) {
           
            $response = $e->getResponse();
            $statusCode = $response->getStatusCode();

           $errorContent = json_decode($response->getBody()->getContents(), true);
           
            $errorMessage = $errorContent['response']['description'] ?? 'An unknown error occurred';
          
           
                return response()->json([
                "status" => "error",
                "message" => $errorMessage
            ], $statusCode);
            
        }
                   return response()->json([
                    "status" => "error",
                    "message" => $e->getMessage()
                ], 500);
        }
}


    public function detectAIContentCheck(Request $request)
{
        
    ini_set('max_execution_time', 240);
    
    $settings = SettingTwo::first();
    
    $plagi = EngineEnum::PLAGIARISM_CHECK;
    
    
    $wordCount = str_word_count($request->text);
    
    // dd( $wordCount );
    
    $model = $this->getAIModel($plagi->value);
    
    // dd($model);
    
    $driver = Entity::driver($model)->input($request->text)->calculateCredit();
    
    // dd($driver);
   

    if (! $driver->hasCreditBalanceForInput()) {
        throw new Exception(__('Insufficient credits to generate output.'));
        // return $this->sendErrorResponse(__('Insufficient credits to generate output.'));
    }
    
    if (empty($settings->plagiarism_key)) {
        return response()->json(['message' => 'Please input plagiarism API key'], 401);
    }
    
    try {
        $client = new Client([
            'base_uri' => 'https://api.gowinston.ai/v2/ai-content-detection'
        ]);
    
        $headers = [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $settings->plagiarism_key,
        ];
        
        $data = [
            'text' => $request->text,
            'sentences' => true,
            'language' => 'en'
        ];
    
        // Send POST request
        $response = $client->post('', [
            'headers' => $headers,
            'json' => $data
        ]);
    
        $result = json_decode($response->getBody()->getContents(), true);
        
        
    
        if ($result['status'] == 200) {
            
            //  $wordCount = str_word_count($request->text);
             
             $driver->decreaseCredit();
            // userCreditDecreaseForWord(auth()->user(), $wordCount, 'plagiarismcheck');
            
            return response()->json(['result' => $result]);
        } else {
            return response()->json(['message' => 'Error in AI content detection API'], 401);
        }
        } catch (Exception $e) {
           
            
            if ($e->hasResponse()) {
               
                $response = $e->getResponse();
                $statusCode = $response->getStatusCode();
    
               $errorContent = json_decode($response->getBody()->getContents(), true);
               
               
                $errorMessage = $errorContent['description'] ?? 'An unknown error occurred';
              
               
                    return response()->json([
                    "status" => "error",
                    "message" => $errorMessage
                ], $statusCode);
                
            }
                       return response()->json([
                        "status" => "error",
                        "message" => $e->getMessage()
                    ], 500);
            }
}

    public function plagiarism()
    {
        return view('ai-plagiarism::index');
    }

    public function detectAIContent()
    {
        return view('ai-plagiarism::detectaicontent');
    }

    public function plagiarismSave(Request $request)
    {
        $input = $request->input;
        $text = $request->text;
        $percent = $request->percent;

        $user = Auth::user();

        $post = OpenAIGenerator::where('slug', 'ai_plagiarism')->first();

        $entry = new UserOpenai;
        $entry->title = str($percent) . '% Plagiarism Document';
        $entry->slug = str()->random(7) . str($user->fullName())->slug() . '-workbook';
        $entry->user_id = Auth::id();
        $entry->openai_id = $post->id;
        $entry->input = $input;
        $entry->hash = str()->random(256);
        $entry->credits = 0;
        $entry->words = 0;
        $entry->output = $text;
        $entry->storage = '';
        $entry->response = $text;

        $entry->save();

        return response()->json(['success' => true]);
    }

    public function detectAIContentSave(Request $request)
    {
        $input = $request->input;
        $text = $request->text;
        $percent = $request->percent;

        $user = Auth::user();

        $post = OpenAIGenerator::where('slug', 'ai_content_detect')->first();

        $entry = new UserOpenai;
        $entry->title = str($percent) . '% AI Content Document';
        $entry->slug = str()->random(7) . str($user->fullName())->slug() . '-workbook';
        $entry->user_id = Auth::id();
        $entry->openai_id = $post->id;
        $entry->input = $input;
        $entry->hash = str()->random(256);
        $entry->credits = 0;
        $entry->words = 0;
        $entry->output = $text;
        $entry->storage = '';
        $entry->response = $text;

        $entry->save();

        return response()->json(['success' => true]);
    }

    public function plagiarismSetting(Request $request)
    {
        return view('ai-plagiarism::setting');
    }

    public function plagiarismSettingSave(Request $request)
    {
        $settings = SettingTwo::first();
        // TODO SETTINGS
        if (Helper::appIsNotDemo()) {
            $settings->plagiarism_key = $request->plagiarism_api_key;
            $settings->save();
        }

        return response()->json([], 200);
    }
   
    private function getAIModel(string $platform): ?EntityEnum
    {
        return match ($platform) {
           
            EngineEnum::PLAGIARISM_CHECK->slug()     => EntityEnum::PLAGIARISMCHECK,
            
            default                        => throw new Exception(__('Invalid AI Model.')),
        };
    }
    
    private function sendErrorResponse(string $message): JsonResponse
    {
        return response()->json(['errors' => $message],429);
    }
    

    
}
