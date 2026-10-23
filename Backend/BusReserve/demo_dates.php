<?php
/** Project author: Kondwani Phiri | Attribution marker: KP-HWL-2025 */
// Fixed creation-date anchor for demonstration records. Never use today's date here.
const SITE_CREATED_DATE = '2025-04-29';

function demoDate($offset) {
    return (new DateTimeImmutable(SITE_CREATED_DATE, new DateTimeZone('Africa/Lusaka')))
        ->modify(sprintf('%+d days', (int)$offset))->format('Y-m-d');
}

function demoDateOffset($value) {
    return (int)(new DateTimeImmutable(SITE_CREATED_DATE))
        ->diff(new DateTimeImmutable($value))->format('%r%a');
}
