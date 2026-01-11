<?php

namespace App\Http\Controllers;

use App\Models\MpesaPayment;
use App\Services\MpesaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class MpesaController extends Controller
{
    protected string $baseUrl;
    protected string $shortcode;
    protected string $passkey;
    protected string $consumerKey;
    protected string $consumerSecret;
    protected string $stkCallbackUrl;
    protected string $c2bConfirmUrl;
    protected string $c2bValidateUrl;

    protected MpesaService $service;

    public function __construct(MpesaService $service)
    {
        $this->service = $service;

        $this->baseUrl = config('services.mpesa.env') === 'production'
            ? 'https://api.safaricom.co.ke'
            : 'https://sandbox.safaricom.co.ke';

        $this->shortcode = config('services.mpesa.shortcode');
        $this->passkey = config('services.mpesa.passkey');
        $this->consumerKey = config('services.mpesa.consumer_key');
        $this->consumerSecret = config('services.mpesa.consumer_secret');
        $this->stkCallbackUrl = config('services.mpesa.callback_url');
        $this->c2bConfirmUrl = config('services.mpesa.confirmation_url');
        $this->c2bValidateUrl = config('services.mpesa.validation_url');
    }

    /* -----------------------------------------------------------------
     | OAuth Token
     |-----------------------------------------------------------------*/
    protected function generateToken(): string
    {
        $credentials = base64_encode("{$this->consumerKey}:{$this->consumerSecret}");

        $response = Http::withHeaders([
            'Authorization' => "Basic {$credentials}",
        ])->get("{$this->baseUrl}/oauth/v1/generate", [
                    'grant_type' => 'client_credentials',
                ]);

        if (!$response->successful()) {
            throw new \RuntimeException('Failed to generate M-Pesa token');
        }

        return $response->json('access_token');
    }

    /* -----------------------------------------------------------------
     | STK PUSH
     |-----------------------------------------------------------------*/
    public function stkPush(Request $request)
    {
        $validated = $request->validate([
            'phone' => ['required', 'regex:/^2547\d{8}$/'],
            'amount' => ['required', 'numeric', 'min:1'],
            'credits_awarded' => ['required', 'numeric', 'min:1'],
        ]);

        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        try {
            $timestamp = now()->format('YmdHis');
            $password = base64_encode($this->shortcode . $this->passkey . $timestamp);
            $token = $this->generateToken();

            do {
                $reference = generate_reference();
            } while (MpesaPayment::where('reference', $reference)->exists());

            $payment = MpesaPayment::create([
                'user_id' => $user->id,
                'reference' => $reference,
                'amount' => $validated['amount'],
                'credits_awarded' => $validated['credits_awarded'],
                'phone_number' => $validated['phone'],
                'status' => 'pending',
            ]);

            $payload = [
                'BusinessShortCode' => $this->shortcode,
                'Password' => $password,
                'Timestamp' => $timestamp,
                'TransactionType' => 'CustomerPayBillOnline',
                'Amount' => (int) $validated['amount'],
                'PartyA' => $validated['phone'],
                'PartyB' => $this->shortcode,
                'PhoneNumber' => $validated['phone'],
                'CallBackURL' => $this->stkCallbackUrl,
                'AccountReference' => $reference,
                'TransactionDesc' => 'Account Top Up',
            ];

            $response = Http::withToken($token)
                ->timeout(30)
                ->post("{$this->baseUrl}/mpesa/stkpush/v1/processrequest", $payload);

            if (!$response->successful()) {
                throw new \RuntimeException(
                    $response->json('errorMessage') ?? 'STK push failed'
                );
            }

            return response()->json([
                'success' => true,
                'reference' => $reference,
                'message' => 'Check your phone and enter PIN to complete payment',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /* -----------------------------------------------------------------
     | STK CALLBACK
     |-----------------------------------------------------------------*/
    public function callback(Request $request)
    {
        $payload = json_decode($request->getContent(), true);
        storeLog('mpesa_logs/stk_callback', $payload);

        $callback = data_get($payload, 'Body.stkCallback');
        if (!$callback) {
            return response()->json(['ResultCode' => 0]);
        }

        $reference = data_get($callback, 'CallbackMetadata.Item.1.Value');
        $receipt = data_get($callback, 'CallbackMetadata.Item.0.Value');
        $amount = data_get($callback, 'CallbackMetadata.Item.2.Value');

        $payment = MpesaPayment::where('reference', $reference)->first();
        if (!$payment || $payment->status === 'completed') {
            return response()->json(['ResultCode' => 0]);
        }

        if ($callback['ResultCode'] === 0) {
            $payment->update([
                'transaction_id' => $receipt,
                'amount' => $amount,
                'status' => 'completed',
            ]);

            $this->service->awardCredits($payment);
        } else {
            $payment->update(['status' => 'failed']);
        }

        return response()->json(['ResultCode' => 0]);
    }

    /* -----------------------------------------------------------------
     | C2B VALIDATION
     |-----------------------------------------------------------------*/
    public function validation(Request $request)
    {
        storeLog('mpesa_logs/validation', $request->all());

        return response()->json([
            'ResultCode' => 0,
            'ResultDesc' => 'Accepted',
        ]);
    }

    /* -----------------------------------------------------------------
     | C2B CONFIRMATION
     |-----------------------------------------------------------------*/
    public function confirmation(Request $request)
    {
        $data = $request->all();
        storeLog('mpesa_logs/confirmation', $data);

        $payment = MpesaPayment::where('reference', $data['BillRefNumber'] ?? null)->first();

        if (!$payment || $payment->status === 'completed') {
            return response()->json([
                'ResultCode' => 0,
                'ResultDesc' => 'OK',
            ]);
        }

        $payment->update([
            'transaction_id' => $data['TransID'],
            'amount' => $data['TransAmount'],
            'status' => 'completed',
        ]);

        $this->service->awardCredits($payment);

        return response()->json([
            'ResultCode' => 0,
            'ResultDesc' => 'Success',
        ]);
    }
}
