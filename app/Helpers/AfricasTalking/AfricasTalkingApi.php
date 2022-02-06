<?php


namespace App\Helpers\AfricasTalking;


use App\Enums\EventType;
use App\Enums\Status;
use App\Enums\VoucherType;
use App\Models\Transaction;
use App\Models\Voucher;
use App\Services\SidoohAccounts;
use App\Services\SidoohNotify;
use Illuminate\Support\Facades\DB;
use Throwable;

class AfricasTalkingApi
{
    /**
     * Guzzle client initialization.
     *
     * @var AfricasTalkingSubClass
     */
    protected AfricasTalkingSubClass $AT;

    /**
     * AfricasTalking APIs application username.
     *
     * @var string
     */
    protected string $username;

    /**
     * AfricasTalking APIs application key.
     *
     * @var string
     */
    protected string $apiKey;

    /**
     * Make the initializations required to make calls to the Safaricom MPESA APIs
     * and throw the necessary exception if there are any missing required
     * configurations.
     */
    public function __construct()
    {
        if(config('services.at.env') == 'production') {
            $this->username = config("services.at.airtime.username");
            $this->apiKey = config("services.at.airtime.key");
        } else {
            $this->username = config('services.at.username');
            $this->apiKey = config('services.at.key');
        }

        $this->AT = new AfricasTalkingSubClass($this->username, $this->apiKey);
    }

    /**
     * @throws Throwable
     */
    public static function airtime(Transaction $transaction, $airtimeData)
    {
        $response = (new AfricasTalkingApi)->send($airtimeData['phone'], $airtimeData['amount']);
        $response = object_to_array($response);

        $req = $transaction->airtimeRequest()->create([
            'message'  => $response['data']['errorMessage'],
            'num_sent' => $response['data']['numSent'],
            'amount'   => $response['data']['totalAmount'],
            'discount' => $response['data']['totalDiscount'],
        ]);

        DB::transaction(function() use ($req, $response) {
            $req->save();

            $req->airtimeResponses()->createMany($response['data']['responses']);
        });

        if($response['data']['errorMessage'] != "None") {
            $amount = $transaction->amount;
            $phone = SidoohAccounts::findPhone($transaction->account_id);
            $date = $req->updated_at->timezone('Africa/Nairobi')->format(config("settings.sms_date_time_format"));

            $voucher = Voucher::whereType(VoucherType::SIDOOH)
                ->whereAccountId($transaction->account_id)
                ->firstOrFail();
            $voucher->balance += (double)$amount;
            $voucher->save();

            $transaction->status = Status::REIMBURSED;
            $transaction->save();

            $message = "Sorry! We could not complete your airtime purchase for {$phone} worth {$amount} on {$date}. We have credited your voucher {$amount} and your balance is now {$voucher->balance}.";

            SidoohNotify::notify([$phone], $message, EventType::AIRTIME_PURCHASE_FAILURE);
        }
    }

    public function send(string $to, string $amount, string $currency = 'KES'): array
    {
        // Get airtime service
        $airtime = $this->AT->airtime();

        // Use the service
        return $airtime->send([
            'recipients' => [
                [
                    'phoneNumber'  => $to,
                    'currencyCode' => $currency,
                    'amount'       => $amount
                ],
            ]
        ]);
    }

    public function transactionStatus(string $transactionId): array
    {
        // Get transaction service
        $transaction = $this->AT->transaction();

        // Use the service
        return $transaction->check(['transactionId' => $transactionId,]);
    }
}
