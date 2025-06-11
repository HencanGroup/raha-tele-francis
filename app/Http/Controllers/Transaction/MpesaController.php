<?php

namespace App\Http\Controllers\Transaction;

use App\Http\Controllers\Controller;
use App\Models\MpesaPayment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\MpesaService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * MpesaController handles all M-Pesa related transactions including:
 * - STK push payments
 * - Transaction validation and confirmation
 * - Transaction data storage and processing
 */
class MpesaController extends Controller
{
    /**
     * M-Pesa API configuration properties
     */
    protected string $baseUrl;
    protected string $shortcode;
    protected string $passkey;
    protected string $key;
    protected string $secret;
    protected string $stkCallbackUrl;
    protected string $c2bConfirmUrl;
    protected string $c2bValidateUrl;
    protected MpesaService $service;

    /**
     * Initialize M-Pesa configuration from environment
     */
    public function __construct(MpesaService $service)
    {
        $this->service = $service;
        $this->baseUrl = config('services.mpesa.env') === 'production'
            ? 'https://api.safaricom.co.ke'
            : 'https://sandbox.safaricom.co.ke';
        $this->shortcode = config('services.mpesa.shortcode');
        $this->passkey = config('services.mpesa.passkey');
        $this->key = config('services.mpesa.consumer_key');
        $this->secret = config('services.mpesa.consumer_secret');
        $this->stkCallbackUrl = config('services.mpesa.callback_url');
        $this->c2bConfirmUrl = config('services.mpesa.confirmation_url');
        $this->c2bValidateUrl = config('services.mpesa.validation_url');
    }

    /**
     * Generate M-Pesa API authentication token
     */
    protected function generateToken(): ?string
    {
        try {
            $credentials = base64_encode($this->key . ':' . $this->secret);

            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . $credentials,
            ])->get("$this->baseUrl/oauth/v1/generate?grant_type=client_credentials");

            return $response->successful() ? $response->json()['access_token'] : null;
        } catch (\Throwable $e) {
            Log::error('Token Generation Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Initiate STK Push payment request
     */
    public function stk(Request $request)
    {
        try {
            $validated = $request->validate([
                'phone'  => 'required|string|regex:/^\+?[0-9]{10,15}$/',
                'amount' => 'required|numeric|min:1',
                'plan'   => 'required|string',
            ]);

            $user = Auth::user();
            if (!$user) {
                return response()->json(['error' => 'You MUST be logged in to initiate this transaction!'], 401);
            }

            $amount = $validated['amount'];
            $phone = $validated['phone'];
            $timestamp = now()->format('YmdHis');
            $password = base64_encode($this->shortcode . $this->passkey . $timestamp);
            $token = $this->generateToken();
            $invoiceNumber = $this->generateInvoiceNumber();

            if (!$token) {
                throw new \Exception('Failed to generate authentication token');
            }

            $payload = [
                'BusinessShortCode' => $this->shortcode,
                'Password'          => $password,
                'Timestamp'         => $timestamp,
                'TransactionType'   => 'CustomerPayBillOnline',
                'Amount'            => $amount,
                'PartyA'            => $phone,
                'PartyB'            => $this->shortcode,
                'PhoneNumber'       => $phone,
                'CallBackURL'       => $this->stkCallbackUrl,
                'AccountReference'  => $invoiceNumber,
                'TransactionDesc'   => 'STK Push',
            ];

            $response = Http::withToken($token)
                ->timeout(30)
                ->post($this->baseUrl . '/mpesa/stkpush/v1/processrequest', $payload);

            if ($response->successful()) {
                // get plan details
                $plan = Plan::where('slug', $validated['plan'])->first();

                if (!$plan) {
                    return response()->json(['error' => 'Invalid subscription plan selected.'], 400);
                }

                // save the subscription plan and amount to the database
                Subscription::create([
                    'invoice_id' => $invoiceNumber,
                    'user_id'    => $user->id,
                    'plan_id'    => $plan->id,
                    'status'     => Subscription::STATUS_PENDING,
                ]);

                return response()->json(['success' => 'Payment initiated successfully. Please complete the payment via M-Pesa.'], 200);
            } else {
                Log::error('STK Push Error: ' . $response->body());
                return response()->json(['error' => $response['errorMessage']], 500);
            }
        } catch (\Throwable $th) {
            Log::error('STK Initiation Error: ' . $th->getMessage());
            return response()->json(['error' => 'An error occurred while processing your request'], 500);
        }
    }

    /**
     * Handle M-Pesa validation request
     */
    public function validation(Request $request)
    {
        try {
            $rawInput = file_get_contents('php://input');
            $decodedResponse = json_decode($rawInput, true);
            // storeLog('mpesa_logs/validation', $decodedResponse);
        } catch (\Exception $e) {
            Log::error('Validation Error: ' . $e->getMessage());
            return response()->json(['ResultCode' => 1, 'ResultDesc' => 'Error'], 500);
        }
    }

    /**
     * Handle M-Pesa callback request
     */
    public function callback(Request $request)
    {
        try {
            $rawInput = file_get_contents('php://input');
            $decodedResponse = json_decode($rawInput, true);
            // storeLog('mpesa_logs/callback', $decodedResponse);
        } catch (\Exception $e) {
            Log::error('Callback Error: ' . $e->getMessage());
            return response()->json(['error' => 'An error occurred'], 500);
        }
    }

    /**
     * Handle M-Pesa confirmation request
     */
    public function confirmation(Request $request)
    {
        try {
            $rawInput = file_get_contents('php://input');
            $decodedResponse = json_decode($rawInput, true);

            // Validate required fields
            if (!isset(
                $decodedResponse['TransID'],
                $decodedResponse['TransAmount'],
                $decodedResponse['MSISDN'],
                $decodedResponse['FirstName'],
                $decodedResponse['BillRefNumber']
            )) {
                Log::error('Invalid confirmation data.', ['data' => $decodedResponse]);
                return response()->json(['status' => 'error', 'message' => 'Invalid data received'], 400);
            }

            // Store transaction
            $this->handleSubscriptionPayment([
                'transaction_id'   => $decodedResponse['TransID'],
                'name'             => $decodedResponse['FirstName'],
                'amount'           => $decodedResponse['TransAmount'],
                'phone_number'     => $decodedResponse['MSISDN'],
                'reference'        => $decodedResponse['BillRefNumber'],
                'status'           => 'success',
            ]);
        } catch (\Exception $e) {
            Log::error('Confirmation Error: ' . $e->getMessage());
            return response()->json(['ResultCode' => 1, 'ResultDesc' => 'Error'], 500);
        }
    }

    public function handleSubscriptionPayment(array $data)
    {
        DB::beginTransaction();

        try {
            // Lock the subscription record for update to prevent race conditions
            $subscription = Subscription::with(['user', 'plan'])
                ->where('invoice_id', $data['reference'])
                ->lockForUpdate()
                ->firstOrFail();

            // Create the payment record
            $mpesaPayment = MpesaPayment::create([
                'user_id'         => $subscription->user->id,
                'subscription_id' => $subscription->id,
                'transaction_id'  => $data['transaction_id'],
                'reference'       => $data['reference'],
                'phone_number'    => $data['phone_number'],
                'name'            => $data['name'],
                'amount'          => $data['amount'],
                'status'          => $data['status'],
            ]);

            // Calculate the new total paid amount
            $totalPaid = MpesaPayment::where('subscription_id', $subscription->id)
                ->where('status', 'success')
                ->sum('amount');

            // Check if payment meets or exceeds plan amount
            if ($totalPaid >= $subscription->plan->amount) {
                $subscription->update([
                    'status'    => 'active',
                    'starts_at' => now(),
                    'ends_at'   => now()->addDays((int) $subscription->plan->billing_period),
                ]);
            }

            DB::commit();
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
        } catch (\Exception $e) {
            DB::rollBack();
        }
    }
    private function generateInvoiceNumber()
    {
        $letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        do {
            $invoiceId = '';
            for ($i = 0; $i < 6; $i++) {
                $invoiceId .= $letters[rand(0, 25)];
            }
        } while (Subscription::where('invoice_id', $invoiceId)->exists());

        return $invoiceId;
    }
}
