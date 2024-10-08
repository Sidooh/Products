<?php

use App\DTOs\PaymentDTO;
use App\Enums\Description;
use App\Enums\PaymentMethod;
use App\Enums\ProductType;
use App\Models\Transaction;
use App\Services\SidoohAccounts;
use App\Services\SidoohPayments;
use DrH\Tanda\Library\Providers;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use JetBrains\PhpStorm\NoReturn;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

if (! function_exists('object_to_array')) {
    function object_to_array($obj)
    {
        //  only process if it's an object or array being passed to the function
        if (is_object($obj) || is_array($obj)) {
            $ret = (array) $obj;

            foreach ($ret as &$item) {
                //  recursively process EACH element regardless of type
                $item = object_to_array($item);
            }

            return $ret;
        } else {
            //  otherwise, (i.e. for scalar values) return without modification
            return $obj;
        }
    }
}

if (! function_exists('dump_json')) {
    #[NoReturn]
    function dump_json(...$vars)
    {
        echo '<pre>';
        print_r($vars);
        exit;
    }
}

if (! function_exists('base_64_url_encode')) {
    function base_64_url_encode($text): array|string
    {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($text));
    }
}

function getTelcoFromPhone(int $phone): string|null
{
    $safReg = '/^(?:254|\+254|0)?((?:7(?:[0129][0-9]|4[0123568]|5[789]|6[89])|(1([1][0-5])))[0-9]{6})$/';
    $airReg = '/^(?:254|\+254|0)?((?:(7(?:(3[0-9])|(5[0-6])|(6[27])|(8[0-9])))|(1([0][0-6])))[0-9]{6})$/';
    $telReg = '/^(?:254|\+254|0)?(7(7[0-9])[0-9]{6})$/';
//    $equReg = '/^(?:254|\+254|0)?(7(6[3-6])[0-9]{6})$/';
    $faibaReg = '/^(?:254|\+254|0)?(747[0-9]{6})$/';

    return match (1) {
        preg_match($safReg, $phone)   => Providers::SAFARICOM,
        preg_match($airReg, $phone)   => Providers::AIRTEL,
        preg_match($telReg, $phone)   => Providers::TELKOM,
        preg_match($faibaReg, $phone) => Providers::FAIBA,
//            preg_match($equReg, $phone) => Providers::EQUITEL,
        default                       => null,
    };
}

function getProviderFromTransaction(Transaction $transaction): string
{
    $productId = $transaction->product_id;
    $descriptionArray = explode(' ', $transaction->description);

    return $productId === ProductType::AIRTIME->value ? getTelcoFromPhone($transaction->destination)
        : $descriptionArray[0];
}

if (! function_exists('withRelation')) {
    /**
     * @throws AuthenticationException
     */
    function withRelation(string $relation, Collection|array $parentRecords, $parentKey, $childKey)
    {
        $childRecords = collect(match ($relation) {
            'account' => SidoohAccounts::getAll(),
            'payment' => SidoohPayments::getAll(),
            default   => throw new BadRequestException('Invalid relation!')
        });

        if (is_array($parentRecords)) {
            $parentRecords = collect($parentRecords);
        }

        return $parentRecords->transform(function($record) use ($parentKey, $relation, $childKey, $childRecords) {
            $record[$relation] = $childRecords->firstWhere($childKey, $record[$parentKey]);

            return $record;
        });
    }
}

if (! function_exists('paginate')) {
    function paginate($items, $total, $perPage, $currentPage): LengthAwarePaginator
    {
        return new LengthAwarePaginator($items, $total, $perPage, $currentPage);
    }
}

if (! function_exists('admin_contacts')) {
    function admin_contacts(): array
    {
        return explode(',', config('services.sidooh.admin_contacts'));
    }
}

if (! function_exists('credit_voucher')) {
    /**
     * @throws Exception
     */
    function credit_voucher(Transaction $transaction): ?array
    {
        Log::info('...[SRV - PAYMENTS]: Credit Voucher...');

        //  Credit Voucher
        $voucherId = SidoohPayments::findSidoohVoucherIdForAccount($transaction->account_id);
        $paymentData = new PaymentDTO(
            $transaction->account_id,
            $transaction->amount,
            Description::VOUCHER_REFUND,
            $transaction->destination,
            PaymentMethod::FLOAT,
            1
        );
        $paymentData->setDestination(PaymentMethod::VOUCHER, $voucherId);

        SidoohPayments::requestPayment($paymentData);

        return SidoohPayments::findVoucher($voucherId, true);
    }
}
