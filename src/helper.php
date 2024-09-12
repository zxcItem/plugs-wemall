<?php

declare (strict_types=1);

if (!function_exists('show_gspec')) {
    /**
     * 商品规格过滤显示
     * @param string $spec 原规格内容
     * @return string
     */
    function show_gspec(string $spec): string
    {
        $specs = [];
        foreach (explode(';;', $spec) as $sp) {
            $specs[] = explode('::', $sp)[1];
        }
        return join(' ', $specs);
    }
}


if (!function_exists('formatdate')) {
    /**
     * 日期格式过滤
     * @param string|null $value
     * @return string|null
     */
    function formatdate(?string $value): ?string
    {
        return is_string($value) ? str_replace(['年', '月', '日'], ['-', '-', ''], $value) : $value;
    }
}