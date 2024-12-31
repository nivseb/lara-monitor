<?php

namespace Nivseb\LaraMonitor\Contracts\Elastic;

use Nivseb\LaraMonitor\Struct\Transactions\AbstractTransaction;

interface MetaBuilderContract
{
    public function buildMetaRecords(AbstractTransaction $transaction): array;
}
