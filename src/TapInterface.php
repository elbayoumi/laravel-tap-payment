<?php

namespace Ashraf\LaravelTapPayment;

interface TapInterface
{
    public function charge(array $data): mixed;

    public function getCharge(string $charge_id): mixed;

    public function chargesList(array $options): mixed;

    public function refund(array $data = []): mixed;

    public function getRefund(string $refund_id): mixed;

    public function refundList(array $options): mixed;
}
