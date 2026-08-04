<?php

namespace App\Http\Controllers;

use App\Models\MpesaPayment;
use App\Services\MpesaService;
use App\Services\Withdrawal\WithdrawalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class MpesaController extends Controller
{
    protected ?string $shortcode;

    protected ?string $passkey;

    protected ?string $stkCallbackUrl;

    protected ?string $c2bConfirmUrl;

    protected ?string $c2bValidateUrl;

    protected MpesaService $service;

    protected WithdrawalService $withdrawalService;

    public function __construct(MpesaService $service, WithdrawalService $withdrawalService)
    {
        $this->service = $service;
        $this->withdrawalService = $withdrawalService;

        $this->shortcode = config('services.mpesa.shortcode');
        $this->passkey = config('services.mpesa.passkey');
        $this->stkCallbackUrl = config('services.mpesa.callback_url');
        $this->c2bConfirmUrl = config('services.mpesa.confirmation_url');
        $this->c2bValidateUrl = config('services.mpesa.validation_url');
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
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        try {
            $timestamp = now()->format('YmdHis');
            $password = base64_encode($this->shortcode.$this->passkey.$timestamp);
            $token = $this->service->generateToken();

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
                ->post("{$this->service->baseUrl()}/mpesa/stkpush/v1/processrequest", $payload);

            if (! $response->successful()) {
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
        if (! $callback) {
            return response()->json(['ResultCode' => 0]);
        }

        $reference = data_get($callback, 'CallbackMetadata.Item.1.Value');
        $receipt = data_get($callback, 'CallbackMetadata.Item.0.Value');
        $amount = data_get($callback, 'CallbackMetadata.Item.2.Value');

        $payment = MpesaPayment::where('reference', $reference)->first();
        if (! $payment || $payment->status === 'completed') {
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
     | B2C RESULT CALLBACK (escort payouts)
     |-----------------------------------------------------------------*/
    public function b2cResult(Request $request)
    {
        $payload = json_decode($request->getContent(), true);
        storeLog('mpesa_logs/b2c_result', $payload);

        $result = data_get($payload, 'Result');
        if (! $result) {
            return response()->json(['ResultCode' => 1, 'ResultDesc' => 'Malformed payload']);
        }

        $this->withdrawalService->processB2CResult(
            (string) data_get($result, 'OriginatorConversationID'),
            (int) data_get($result, 'ResultCode', 1),
            data_get($result, 'TransactionID'),
            data_get($result, 'ResultDesc'),
        );

        return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
    }

    /* -----------------------------------------------------------------
     | B2C QUEUE-TIMEOUT CALLBACK (escort payouts)
     |-----------------------------------------------------------------*/
    public function b2cTimeout(Request $request)
    {
        $payload = json_decode($request->getContent(), true);
        storeLog('mpesa_logs/b2c_timeout', $payload);

        $result = data_get($payload, 'Result');
        if (! $result) {
            return response()->json(['ResultCode' => 1, 'ResultDesc' => 'Malformed payload']);
        }

        // Timeouts always mean the request was not fulfilled — refund the escrow.
        $this->withdrawalService->processB2CResult(
            (string) data_get($result, 'OriginatorConversationID'),
            (int) data_get($result, 'ResultCode', 1),
            data_get($result, 'TransactionID'),
            data_get($result, 'ResultDesc') ?? 'M-Pesa queue timeout',
        );

        return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
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

        $payment = MpesaPayment::where(
            'reference',
            $data['BillRefNumber'] ?? null
        )->first();

        if (! $payment || $payment->status === 'completed') {
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
