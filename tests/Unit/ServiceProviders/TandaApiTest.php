<?php

namespace Tests\Unit\ServiceProviders;

use App\Enums\Initiator;
use App\Enums\ProductType;
use App\Enums\Status;
use App\Enums\TransactionType;
use App\Helpers\Tanda\TandaApi;
use App\Listeners\TandaRequestFailed;
use App\Listeners\TandaRequestSuccess;
use App\Models\Transaction;
use Database\Seeders\ProductSeeder;
use DrH\Tanda\Events\TandaRequestSuccessEvent;
use DrH\Tanda\Library\BaseClient;
use DrH\Tanda\Models\TandaRequest;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class TandaApiTest extends TestCase
{
    protected BaseClient $_client;

    protected MockHandler $mock;

    private function mockSuccessResponse()
    {
        $this->mock->append(
            new Response(200, ['Content_type' => 'application/json'],
                json_encode(
                    [
                        'access_token' => 'token',
                        'expires_in'   => 3599,
                    ],
                )));

        $this->mock->append(
            new Response(200, ['Content_type' => 'application/json'],
                json_encode(
                    [
                        'id'                   => 'b2eda99c-5c32-4a5a-a28e-7a9497d131fa',
                        'status'               => '000000',
                        'message'              => 'Payment completed successfully.',
                        'receiptNumber'        => '01222081809FJHGM',
                        'commandId'            => 'TopupFlexi',
                        'serviceProviderId'    => 'SAFARICOM',
                        'datetimeCreated'      => '2022-08-18 09:48:13.797 +0200',
                        'datetimeLastModified' => '2022-08-18 09:48:16.081 +0200',
                        'datetimeCompleted'    => '2022-08-18 09:48:16.081 +0200',
                        'requestParameters'    => [
                            [
                                'id'    => 'accountNumber',
                                'value' => '254712345678',
                                'label' => "Customer's phone number",
                            ],
                            [
                                'id'    => 'amount',
                                'value' => '100',
                                'label' => 'Amount',
                            ],
                        ],
                    ],
                )));
    }

    private function mockFailedResponse()
    {
        $this->mock->append(
            new Response(200, ['Content_type' => 'application/json'],
                json_encode(
                    [
                        'access_token' => 'token',
                        'expires_in'   => 3599,
                    ],
                )));

        $this->mock->append(
            new Response(200, ['Content_type' => 'application/json'],
                json_encode(
                    [
                        'id'                   => '2dc0a2c8-d616-4650-b105-15eaa1adcba3',
                        'status'               => '500000',
                        'message'              => 'Unexpected error occurred.',
                        'receiptNumber'        => '01222081618ECCHR',
                        'commandId'            => 'TopupFlexi',
                        'serviceProviderId'    => 'SAFARICOM',
                        'datetimeCreated'      => '2022-08-18 09:48:13.797 +0200',
                        'datetimeLastModified' => '2022-08-18 09:48:16.081 +0200',
                        'datetimeCompleted'    => '2022-08-18 09:48:16.081 +0200',
                        'requestParameters'    => [
                            [
                                'id'    => 'accountNumber',
                                'value' => '254712345678',
                                'label' => "Customer's phone number",
                            ],
                            [
                                'id'    => 'amount',
                                'value' => '100',
                                'label' => 'Amount',
                            ],
                        ],
                    ],
                )
            )
        );
    }

    private function createSampleTransaction(int $amount = 100, string $destination = '254712345678', Status $status = Status::PENDING): Transaction
    {
        return Transaction::create([
            'initiator'   => Initiator::CONSUMER,
            'type'        => TransactionType::PAYMENT,
            'amount'      => $amount,
            'destination' => $destination,
            'description' => 'TEST',
            'status'      => $status,
            'account_id'  => 0,
            'product_id'  => ProductType::AIRTIME,
        ]);
    }

    private function createSampleTandaRequest(Transaction $transaction, string $requestId, bool $successful = true): TandaRequest
    {
        return TandaRequest::create([
            'request_id'    => $requestId,
            'status'        => $successful ? '000000' : '500000',
            'message'       => 'message',
            'command_id'    => 'TopupFlexi',
            'provider'      => 'SAFARICOM',
            'destination'   => $transaction->destination,
            'amount'        => $transaction->amount,
            'last_modified' => now(),
            'relation_id'   => $transaction->id,
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();
        Event::fake();

        config(['TANDA_SANDBOX' => true]);

        $this->mock = new MockHandler();

        $handlerStack = HandlerStack::create($this->mock);

        $this->app->singleton(BaseClient::class, function() use ($handlerStack) {
            return new BaseClient(new Client(['handler' => $handlerStack]));
        });
    }

    protected function afterRefreshingDatabase()
    {
        $this->seed(ProductSeeder::class);
    }

    public function test_query_status_success_tx()
    {
        // 1. Completed transaction // has TandaReq
        $tx = $this->createSampleTransaction(status: Status::COMPLETED);
        $tx->save();

        $this->createSampleTandaRequest($tx, 'b2eda99c-5c32-4a5a-a28e-7a9497d131fa');

        $this->assertModelExists($tx->tandaRequests->first());

        $this->mockSuccessResponse();

        TandaApi::queryStatus($tx, 'b2eda99c-5c32-4a5a-a28e-7a9497d131fa');

        Event::assertNotDispatched(TandaRequest::class);
        Event::assertNotDispatched(TandaRequestSuccessEvent::class);
        Event::assertNotDispatched(TandaRequestFailed::class);
    }

    public function test_query_status_failed_tx()
    {
        // 2. Failed transaction // has TandaReq
        $tx = $this->createSampleTransaction();
        $tx->status = Status::FAILED;
        $tx->save();

        $this->createSampleTandaRequest($tx, '2dc0a2c8-d616-4650-b105-15eaa1adcba3', false);

        $this->assertModelExists($tx->tandaRequests->first());

        $this->mockFailedResponse();

        TandaApi::queryStatus($tx, '2dc0a2c8-d616-4650-b105-15eaa1adcba3');

        Event::assertNotDispatched(TandaRequest::class);
        Event::assertNotDispatched(TandaRequestSuccessEvent::class);
        Event::assertNotDispatched(TandaRequestFailed::class);

        // 3. Pending transaction // no TandaReq
        // 4. Mismatch transaction // no TandaReq
    }

    public function test_query_status_pending_tx()
    {
        // 3. Pending transaction // no TandaReq
        $tx = $this->createSampleTransaction();

        $this->assertNull($tx->tandaRequests->first());

        $this->mockSuccessResponse();

        TandaApi::queryStatus($tx, 'b2eda99c-5c32-4a5a-a28e-7a9497d131fa');

        Event::assertDispatched(TandaRequestSuccessEvent::class);

        Event::assertNotDispatched(TandaRequest::class);
        Event::assertNotDispatched(TandaRequestFailed::class);

        Event::assertListening(TandaRequestSuccessEvent::class, TandaRequestSuccess::class);

        $tx->refresh();
        $this->assertModelExists($tx->tandaRequests->first());
    }

    public function test_query_status_mismatch_tx_amount()
    {
        // 4. Mismatch transaction // no TandaReq
        $tx = $this->createSampleTransaction(200);

        $this->assertNull($tx->tandaRequests->first());

        $this->mockSuccessResponse();

        TandaApi::queryStatus($tx, 'b2eda99c-5c32-4a5a-a28e-7a9497d131fa');

        Event::assertNotDispatched(TandaRequest::class);
        Event::assertNotDispatched(TandaRequestSuccessEvent::class);
        Event::assertNotDispatched(TandaRequestFailed::class);

        $tx->refresh();
        $this->assertNull($tx->tandaRequests->first());
    }

    public function test_query_status_mismatch_tx_destination()
    {
        // 4. Mismatch transaction // no TandaReq
        $tx = $this->createSampleTransaction(destination: '12');

        $this->assertNull($tx->tandaRequests->first());

        $this->mockSuccessResponse();

        TandaApi::queryStatus($tx, 'b2eda99c-5c32-4a5a-a28e-7a9497d131fa');

        Event::assertNotDispatched(TandaRequest::class);
        Event::assertNotDispatched(TandaRequestSuccessEvent::class);
        Event::assertNotDispatched(TandaRequestFailed::class);

        $tx->refresh();
        $this->assertNull($tx->tandaRequests->first());
    }
}
