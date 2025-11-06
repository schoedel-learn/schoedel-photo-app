@extends('emails.layouts.base')

@section('title', 'Your Photos Are Ready')

@section('content')
    <h2 style="margin: 0 0 20px 0; font-size: 22px; font-weight: 600; color: #111827;">
        Your Photos Are Ready!
    </h2>

    <p style="margin: 0 0 15px 0; color: #374151;">
        Hi {{ $client->name }},
    </p>

    <p style="margin: 0 0 15px 0; color: #374151;">
        Great news! Your photo gallery <strong>{{ $gallery->name }}</strong> is now available for viewing.
    </p>

    @if($gallery->description)
        <p style="margin: 0 0 15px 0; color: #374151;">
            {{ $gallery->description }}
        </p>
    @endif

    <div style="margin: 30px 0; text-align: center;">
        <a href="{{ $galleryUrl }}" class="button" style="display: inline-block; padding: 12px 24px; background-color: #4f46e5; color: #ffffff; text-decoration: none; border-radius: 6px; font-weight: 600;">
            View Your Gallery
        </a>
    </div>

    @if($gallery->access_type === 'password_protected')
        <p style="margin: 20px 0 15px 0; color: #374151; padding: 15px; background-color: #fef3c7; border-left: 4px solid #f59e0b; border-radius: 4px;">
            <strong>Note:</strong> This gallery is password protected. You'll be prompted for the password when viewing.
        </p>
    @endif

    <p style="margin: 30px 0 0 0; color: #6b7280; font-size: 14px;">
        If you have any questions, please don't hesitate to reach out to your photographer.
    </p>
@endsection

