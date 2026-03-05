<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\UssdService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UssdController extends Controller
{
    protected UssdService $ussdService;

    public function __construct(UssdService $ussdService)
    {
        $this->ussdService = $ussdService;
    }

    /**
     * Handle incoming USSD request from Africa's Talking
     * 
     * Expected parameters:
     * - sessionId: Unique session ID from telco
     * - phoneNumber: User's phone number (with country code)
     * - text: User's input (menu selections separated by *)
     * - serviceCode: USSD service code (e.g., *384*12345#)
     * - networkCode: Telco network code
     */
    public function handleRequest(Request $request)
    {
        try {
            // Log incoming request for debugging
            Log::info('USSD Request', [
                'sessionId' => $request->input('sessionId'),
                'phoneNumber' => $request->input('phoneNumber'),
                'text' => $request->input('text'),
                'serviceCode' => $request->input('serviceCode'),
                'networkCode' => $request->input('networkCode'),
            ]);

            // Validate required parameters
            $validated = $request->validate([
                'sessionId' => 'required|string',
                'phoneNumber' => 'required|string',
                'text' => 'nullable|string',
                'serviceCode' => 'nullable|string',
                'networkCode' => 'nullable|string',
            ]);

            $sessionId = $validated['sessionId'];
            $phoneNumber = $validated['phoneNumber'];
            $text = $validated['text'] ?? '';

            // Process USSD request
            $response = $this->ussdService->handleRequest($sessionId, $phoneNumber, $text);

            // Log response
            Log::info('USSD Response', [
                'sessionId' => $sessionId,
                'type' => $response['type'],
                'response_length' => strlen($response['response']),
            ]);

            // Return response in Africa's Talking format (plain text)
            return response($response['response'], 200)
                ->header('Content-Type', 'text/plain');

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('USSD Validation Error', [
                'errors' => $e->errors(),
                'request' => $request->all(),
            ]);

            return response('END Invalid request. Please try again.', 400)
                ->header('Content-Type', 'text/plain');

        } catch (\Exception $e) {
            Log::error('USSD Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all(),
            ]);

            return response('END System error. Please try again later.', 500)
                ->header('Content-Type', 'text/plain');
        }
    }

    /**
     * Simulate USSD session for testing (development only)
     */
    public function simulate(Request $request)
    {
        if (!app()->environment('local', 'development', 'staging')) {
            abort(403, 'Simulation only available in development');
        }

        $validated = $request->validate([
            'phone' => 'required|string',
            'text' => 'nullable|string',
        ]);

        $sessionId = 'SIM-' . uniqid();
        $phoneNumber = $validated['phone'];
        $text = $validated['text'] ?? '';

        try {
            $response = $this->ussdService->handleRequest($sessionId, $phoneNumber, $text);

            return response()->json([
                'success' => true,
                'sessionId' => $sessionId,
                'type' => $response['type'],
                'response' => $response['response'],
                'displayText' => str_replace(['CON ', 'END '], '', $response['response']),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get USSD session statistics (admin only)
     */
    public function statistics(Request $request)
    {
        // This would typically require admin authentication
        // For now, returning basic stats structure

        return response()->json([
            'total_sessions' => 0, // TODO: Implement session tracking
            'active_sessions' => 0,
            'completed_sessions' => 0,
            'failed_sessions' => 0,
            'avg_session_duration' => 0,
        ]);
    }
}
