@php
    $conversion = session('google_ads_conversion');
    $sendTo = config('services.google_ads.purchase_send_to');
@endphp
@if ($conversion && $sendTo && (config('services.google_analytics.measurement_id') || config('services.google_ads.id')))
<!-- Event snippet for Покупка conversion page -->
<script>
    gtag('event', 'conversion', {
        'send_to': @json($sendTo),
        'value': {{ json_encode((float) ($conversion['value'] ?? 1.0)) }},
        'currency': @json($conversion['currency'] ?? 'USD'),
        'transaction_id': @json($conversion['transaction_id'] ?? ''),
    });
</script>
@endif
