<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() == 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ app()->getLocale() == 'ar' ? 'تم إلغاء الدفع' : 'Payment Cancelled' }}</title>
    <link href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet">
    <style>
        body { font-family: Arial, sans-serif; background-color: #f8f9fa; margin: 0; padding: 0; direction: {{ app()->getLocale() == 'ar' ? 'rtl' : 'ltr' }}; }
        .container { max-width: 600px; margin: auto; padding: 40px 20px; }
        ._cancelled { box-shadow: 0 15px 25px #00000019; padding: 45px; width: 100%; text-align: center; margin: 40px auto; border-bottom: solid 4px #ffc107; }
        ._cancelled i { font-size: 55px; color: #ffc107; }
        ._cancelled h2 { margin-bottom: 12px; font-size: 40px; font-weight: 500; line-height: 1.2; margin-top: 10px; }
        ._cancelled p { font-size: 18px; color: #495057; font-weight: 500; margin-bottom: 0; }
        ._cancelled a { display: inline-block; margin-top: 16px; color: #ffc107; font-weight: 500; }
    </style>
</head>
<body>
<div class="container">
    <div class="message-box _cancelled">
        <i class="fa fa-times-circle" aria-hidden="true"></i>
        <h2>{{ app()->getLocale() == 'ar' ? 'تم إلغاء الدفع' : 'Payment Cancelled' }}</h2>
        <p>{{ app()->getLocale() == 'ar' ? 'لقد تم إلغاء عملية الدفع' : 'Your payment has been cancelled.' }}</p>
        @if(!empty($hasRedirect))
            <p>{{ app()->getLocale() == 'ar' ? 'سيتم توجيهك خلال 5 ثوانٍ.' : 'You will be redirected in 5 seconds.' }}</p>
        @else
            <a href="{!! $url !!}">{{ app()->getLocale() == 'ar' ? 'العودة للرئيسية' : 'Back to home' }}</a>
        @endif
    </div>
</div>
@if(!empty($hasRedirect))
<script>setTimeout(function () { window.location.href = '{!! $url !!}'; }, 5000);</script>
@endif
</body>
</html>
