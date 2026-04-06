<?php

declare(strict_types=1);

function format_currency_vnd(float|int $amount): string
{
    return number_format((float) $amount, 0, ',', '.') . ' ₫';
}

function format_number_vn(int|float|string $value): string
{
    return number_format((float) $value, 0, ',', '.');
}
