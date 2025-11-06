@extends('emails.layouts.base')

@section('title', 'Order Confirmation')

@section('content')
    <h2 style="margin: 0 0 20px 0; font-size: 22px; font-weight: 600; color: #111827;">
        @if($sendToPhotographer)
            New Order Received
        @else
            Order Confirmation
        @endif
    </h2>

    <p style="margin: 0 0 15px 0; color: #374151;">
        @if($sendToPhotographer)
            Hi {{ $order->photographer->name }},
        @else
            Hi {{ $order->user->name }},
        @endif
    </p>

    @if($sendToPhotographer)
        <p style="margin: 0 0 15px 0; color: #374151;">
            You have received a new order from <strong>{{ $order->user->name }}</strong>.
        </p>
    @else
        <p style="margin: 0 0 15px 0; color: #374151;">
            Thank you for your order! We've received your order and will begin processing it shortly.
        </p>
    @endif

    <div style="background-color: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 20px; margin: 20px 0;">
        <p style="margin: 0 0 10px 0; font-weight: 600; color: #111827;">
            Order #{{ $order->order_number }}
        </p>
        <p style="margin: 0 0 5px 0; color: #6b7280; font-size: 14px;">
            Date: {{ $order->created_at->format('F d, Y g:i A') }}
        </p>
        <p style="margin: 0 0 5px 0; color: #6b7280; font-size: 14px;">
            Status: <span style="color: #111827; font-weight: 600;">{{ ucfirst(str_replace('_', ' ', $order->status)) }}</span>
        </p>
    </div>

    @if($order->items->count() > 0)
        <h3 style="margin: 25px 0 15px 0; font-size: 18px; font-weight: 600; color: #111827;">
            Order Items
        </h3>
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
                            <span style="color: #6b7280; font-size: 14px;">× {{ $item->quantity }}</span>
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
        <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
            <span style="color: #374151;">Subtotal:</span>
            <span style="color: #111827; font-weight: 600;">${{ number_format($order->subtotal, 2) }}</span>
        </div>
        <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
            <span style="color: #374151;">Tax:</span>
            <span style="color: #111827; font-weight: 600;">${{ number_format($order->tax, 2) }}</span>
        </div>
        <div style="display: flex; justify-content: space-between; padding-top: 10px; border-top: 2px solid #e5e7eb; margin-top: 10px;">
            <span style="color: #111827; font-weight: 600; font-size: 18px;">Total:</span>
            <span style="color: #111827; font-weight: 600; font-size: 18px;">${{ number_format($order->total, 2) }}</span>
        </div>
    </div>

    @if($order->status === 'pending' || $order->status === 'pre_order_pending')
        <p style="margin: 20px 0 15px 0; color: #374151; padding: 15px; background-color: #fef3c7; border-left: 4px solid #f59e0b; border-radius: 4px;">
            <strong>Payment Required:</strong> Please complete payment to finalize your order.
        </p>
        <div style="margin: 30px 0; text-align: center;">
            <a href="{{ route('payments.show', $order) }}" class="button" style="display: inline-block; padding: 12px 24px; background-color: #4f46e5; color: #ffffff; text-decoration: none; border-radius: 6px; font-weight: 600;">
                Complete Payment
            </a>
        </div>
    @else
        <div style="margin: 30px 0; text-align: center;">
            <a href="{{ $orderUrl }}" class="button" style="display: inline-block; padding: 12px 24px; background-color: #4f46e5; color: #ffffff; text-decoration: none; border-radius: 6px; font-weight: 600;">
                View Order Details
            </a>
        </div>
    @endif

    <p style="margin: 30px 0 0 0; color: #6b7280; font-size: 14px;">
        If you have any questions about your order, please contact your photographer.
    </p>
@endsection

