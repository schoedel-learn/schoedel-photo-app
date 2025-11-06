@extends('emails.layouts.base')

@section('title', 'Payment Receipt')

@section('content')
    <h2 style="margin: 0 0 20px 0; font-size: 22px; font-weight: 600; color: #111827;">
        Payment Receipt
    </h2>

    <p style="margin: 0 0 15px 0; color: #374151;">
        Hi {{ $order->user->name }},
    </p>

    <p style="margin: 0 0 15px 0; color: #374151;">
        Thank you for your payment! We've successfully processed your payment for Order #{{ $order->order_number }}.
    </p>

    <div style="background-color: #f0fdf4; border: 1px solid #86efac; border-radius: 8px; padding: 20px; margin: 20px 0;">
        <p style="margin: 0 0 10px 0; font-weight: 600; color: #166534; font-size: 16px;">
            ✓ Payment Successful
        </p>
        <p style="margin: 5px 0 0 0; color: #15803d; font-size: 14px;">
            Transaction ID: {{ $transaction->gateway_transaction_id }}<br>
            Amount: ${{ number_format($transaction->amount, 2) }}<br>
            Payment Method: {{ ucfirst(str_replace('_', ' ', $transaction->payment_method ?? 'card')) }}
        </p>
    </div>

    @if($receiptUrl)
        <p style="margin: 20px 0 15px 0; color: #374151;">
            Your official receipt is available:
        </p>
        <div style="margin: 20px 0; text-align: center;">
            <a href="{{ $receiptUrl }}" target="_blank" class="button" style="display: inline-block; padding: 12px 24px; background-color: #4f46e5; color: #ffffff; text-decoration: none; border-radius: 6px; font-weight: 600;">
                View Receipt
            </a>
        </div>
    @endif

    <h3 style="margin: 25px 0 15px 0; font-size: 18px; font-weight: 600; color: #111827;">
        Order Summary
    </h3>

    @if($order->items->count() > 0)
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
            <thead>
                <tr style="background-color: #f9fafb;">
                    <th style="padding: 12px; text-align: left; border-bottom: 1px solid #e5e7eb; color: #374151; font-weight: 600;">Item</th>
                    <th style="padding: 12px; text-align: right; border-bottom: 1px solid #e5e7eb; color: #374151; font-weight: 600;">Price</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $item)
                    <tr>
                        <td style="padding: 12px; border-bottom: 1px solid #e5e7eb; color: #374151;">
                            {{ $item->photo ? $item->photo->filename : 'Photo #' . $item->photo_id }}
                            @if($item->product_type === 'digital_download')
                                <span style="color: #059669; font-size: 12px;">(Digital Download)</span>
                            @endif
                        </td>
                        <td style="padding: 12px; text-align: right; border-bottom: 1px solid #e5e7eb; color: #111827; font-weight: 600;">
                            ${{ number_format($item->total_price, 2) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div style="background-color: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 20px; margin: 20px 0;">
        <div style="display: flex; justify-content: space-between; padding-top: 10px; border-top: 2px solid #e5e7eb; margin-top: 10px;">
            <span style="color: #111827; font-weight: 600; font-size: 18px;">Total Paid:</span>
            <span style="color: #111827; font-weight: 600; font-size: 18px;">${{ number_format($transaction->amount, 2) }}</span>
        </div>
    </div>

    @if(isset($downloadLinks) && $downloadLinks->count() > 0)
        <h3 style="margin: 25px 0 15px 0; font-size: 18px; font-weight: 600; color: #111827;">
            Download Your Photos
        </h3>
        <p style="margin: 0 0 15px 0; color: #374151;">
            Click the links below to download your purchased photos. Links are valid for 7 days.
        </p>
        <div style="margin: 20px 0;">
            @foreach($downloadLinks as $link)
                @if($link['photo'])
                    <div style="padding: 12px; background-color: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px; margin-bottom: 10px;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="color: #374151; font-size: 14px;">{{ $link['photo']->filename }}</span>
                            <a href="{{ $link['url'] }}" style="color: #4f46e5; text-decoration: none; font-weight: 600; font-size: 14px;">
                                Download →
                            </a>
                        </div>
                    </div>
                @endif
            @endforeach
        </div>
        @if($downloadLinks->count() > 5)
            <div style="margin: 20px 0; text-align: center;">
                <a href="{{ route('downloads.index') }}" class="button" style="display: inline-block; padding: 12px 24px; background-color: #4f46e5; color: #ffffff; text-decoration: none; border-radius: 6px; font-weight: 600;">
                    View All Downloads
                </a>
            </div>
        @endif
    @endif

    <div style="margin: 30px 0; text-align: center;">
        <a href="{{ $orderUrl }}" class="button" style="display: inline-block; padding: 12px 24px; background-color: #4f46e5; color: #ffffff; text-decoration: none; border-radius: 6px; font-weight: 600;">
            View Order Details
        </a>
    </div>

    <p style="margin: 30px 0 0 0; color: #6b7280; font-size: 14px;">
        If you have any questions about your payment or order, please contact support.
    </p>
@endsection

