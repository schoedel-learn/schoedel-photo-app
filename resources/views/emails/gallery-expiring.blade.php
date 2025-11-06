@extends('emails.layouts.base')

@section('title', 'Gallery Expiring Soon')

@section('content')
    <h2 style="margin: 0 0 20px 0; font-size: 22px; font-weight: 600; color: #111827;">
        Your Gallery Expires Soon
    </h2>

    <p style="margin: 0 0 15px 0; color: #374151;">
        Hi {{ $client->name }},
    </p>

    <p style="margin: 0 0 15px 0; color: #374151;">
        This is a friendly reminder that your gallery <strong>{{ $gallery->name }}</strong> will expire in 
        <strong>{{ $daysRemaining }} {{ Str::plural('day', $daysRemaining) }}</strong>.
    </p>

    <div style="background-color: #fef3c7; border: 1px solid #fbbf24; border-radius: 8px; padding: 20px; margin: 20px 0;">
        <p style="margin: 0 0 10px 0; font-weight: 600; color: #92400e;">
            ⏰ Don't Miss Out!
        </p>
        <p style="margin: 0; color: #78350f; font-size: 14px;">
            Once your gallery expires, you won't be able to view or purchase photos. Make sure to select and purchase your favorite photos before the expiration date.
        </p>
    </div>

    <div style="margin: 30px 0; text-align: center;">
        <a href="{{ $galleryUrl }}" class="button" style="display: inline-block; padding: 12px 24px; background-color: #4f46e5; color: #ffffff; text-decoration: none; border-radius: 6px; font-weight: 600;">
            View Gallery Now
        </a>
    </div>

    <p style="margin: 20px 0 15px 0; color: #374151;">
        <strong>Expiration Date:</strong> {{ $gallery->expires_at->format('F d, Y') }}
    </p>

    <p style="margin: 30px 0 0 0; color: #6b7280; font-size: 14px;">
        If you need more time to make your selections, please contact your photographer.
    </p>
@endsection

