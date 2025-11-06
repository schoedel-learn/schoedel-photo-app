@extends('emails.layouts.base')

@section('title', 'Download Ready')

@section('content')
    <h2 style="margin: 0 0 20px 0; font-size: 22px; font-weight: 600; color: #111827;">
        Your Download is Ready!
    </h2>

    <p style="margin: 0 0 15px 0; color: #374151;">
        Hi {{ $order->user->name }},
    </p>

    <p style="margin: 0 0 15px 0; color: #374151;">
        Your download archive for Order #{{ $order->order_number }} is ready!
    </p>

    <div style="background-color: #f0fdf4; border: 1px solid #86efac; border-radius: 8px; padding: 20px; margin: 20px 0;">
        <p style="margin: 0 0 10px 0; font-weight: 600; color: #166534;">
            ✓ Download Archive Ready
        </p>
        <p style="margin: 0; color: #15803d; font-size: 14px;">
            All {{ $order->items->count() }} photos from your order are included in the ZIP file.
        </p>
    </div>

    <div style="margin: 30px 0; text-align: center;">
        <a href="{{ $downloadUrl }}" class="button" style="display: inline-block; padding: 12px 24px; background-color: #4f46e5; color: #ffffff; text-decoration: none; border-radius: 6px; font-weight: 600;">
            Download Archive
        </a>
    </div>

    <p style="margin: 20px 0 15px 0; color: #374151; padding: 15px; background-color: #fef3c7; border-left: 4px solid #f59e0b; border-radius: 4px;">
        <strong>Note:</strong> This download link is valid for 7 days. Make sure to download and save your files before it expires.
    </p>

    <p style="margin: 30px 0 0 0; color: #6b7280; font-size: 14px;">
        If you have any questions or issues with your download, please contact support.
    </p>
@endsection

