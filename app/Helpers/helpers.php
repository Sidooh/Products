<?php

use App\Enums\ProductType;
use App\Models\Transaction;
use App\Services\SidoohAccounts;
use App\Services\SidoohPayments;
use DrH\Tanda\Library\Providers;
use JetBrains\PhpStorm\NoReturn;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

if (!function_exists('object_to_array')) {
    function object_to_array($obj)
    {
        //  only process if it's an object or array being passed to the function
        if(is_object($obj) || is_array($obj)) {
            $ret = (array)$obj;

            foreach($ret as &$item) {
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

if(!function_exists('dump_json')) {
    #[NoReturn]
    function dump_json(...$vars)
    {
        echo "<pre>";
        print_r($vars);
        die;
    }
}

if(!function_exists('base_64_url_encode')) {
    function base_64_url_encode($text): array|string
    {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($text));
    }
}

function getTelcoFromPhone(int $phone): string
{
    $safReg = '/^(?:254|\+254|0)?((?:7(?:[0129][0-9]|4[0123568]|5[789]|6[89])|(1([1][0-5])))[0-9]{6})$/';
    $airReg = '/^(?:254|\+254|0)?((?:(7(?:(3[0-9])|(5[0-6])|(6[27])|(8[0-9])))|(1([0][0-6])))[0-9]{6})$/';
    $telReg = '/^(?:254|\+254|0)?(7(7[0-9])[0-9]{6})$/';
//    $equReg = '/^(?:254|\+254|0)?(7(6[3-6])[0-9]{6})$/';
    $faibaReg = '/^(?:254|\+254|0)?(747[0-9]{6})$/';

    return match (1) {
        preg_match($safReg, $phone) => Providers::SAFARICOM,
        preg_match($airReg, $phone) => Providers::AIRTEL,
        preg_match($telReg, $phone) => Providers::TELKOM,
        preg_match($faibaReg, $phone) => Providers::FAIBA,
//            preg_match($equReg, $phone) => Providers::EQUITEL,
        default => null,
    };
}

function getProviderFromTransaction(Transaction $transaction): string
{
    $productId = $transaction->product_id;
    $descriptionArray = explode(" ", $transaction->description);

    return $productId === ProductType::AIRTIME->value ? getTelcoFromPhone($transaction->destination)
        : $descriptionArray[0];
}

if (!function_exists('withRelation')) {
    /**
     * @throws \Illuminate\Auth\AuthenticationException
     */
    function withRelation($relation, $parentRecords, $parentKey, $childKey)
    {
        $childRecords = match ($relation) {
            "account" => SidoohAccounts::getAll(),
            "payment" => SidoohPayments::getAll(),
            default => throw new BadRequestException("Invalid relation!")
        };

        $childRecords = collect($childRecords);

        return $parentRecords->transform(function($record) use ($parentKey, $relation, $childKey, $childRecords) {
            $record[$relation] = $childRecords->firstWhere($childKey, $record[$parentKey]);
            return $record;
        });
    }
}
