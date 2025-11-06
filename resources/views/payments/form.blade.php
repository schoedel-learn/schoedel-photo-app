<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Payment - Order #{{ $order->order_number }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://js.stripe.com/v3/"></script>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen">
        <header class="bg-white shadow-sm border-b">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                <h1 class="text-2xl font-bold text-gray-900">Complete Payment</h1>
                <p class="text-sm text-gray-600 mt-1">Order #{{ $order->order_number }}</p>
            </div>
        </header>

        <main class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div id="payment-error" class="hidden mb-6 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
                <span id="payment-error-message"></span>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-6">Payment Details</h2>

                        <form id="payment-form">
                            <!-- Stripe Elements will create form elements here -->
                            <div id="card-element" class="mb-4">
                                <!-- Stripe Elements will mount here -->
                            </div>

                            <!-- Used to display form errors -->
                            <div id="card-errors" role="alert" class="mb-4 text-sm text-red-600"></div>

                            <!-- 3D Secure authentication -->
                            <div id="payment-method-errors" class="hidden mb-4 text-sm text-red-600"></div>

                            <button type="submit" id="submit-button" 
                                    class="w-full px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition font-semibold disabled:bg-gray-400 disabled:cursor-not-allowed">
                                <span id="button-text">Pay ${{ number_format($order->total, 2) }}</span>
                                <span id="button-spinner" class="hidden inline-block animate-spin rounded-full h-4 w-4 border-b-2 border-white ml-2"></span>
                            </button>
                        </form>
                    </div>

                    <div class="mt-6 text-xs text-gray-500 text-center">
                        <p>Your payment is secured by Stripe. We never store your card details.</p>
                    </div>
                </div>

                <div class="lg:col-span-1">
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 sticky top-8">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">Order Summary</h2>

                        <div class="space-y-2 mb-4 text-sm">
                            <div class="text-gray-600 mb-3">
                                {{ $order->items->count() }} {{ Str::plural('item', $order->items->count()) }}
                            </div>

                            @foreach($order->items as $item)
                                <div class="flex justify-between pb-2 border-b border-gray-100">
                                    <span class="text-gray-700">{{ $item->photo ? $item->photo->filename : 'Photo #' . $item->photo_id }}</span>
                                    <span class="font-medium">${{ number_format($item->total_price, 2) }}</span>
                                </div>
                            @endforeach
                        </div>

                        <div class="space-y-2 pt-4 border-t">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Subtotal:</span>
                                <span class="font-medium">${{ number_format($order->subtotal, 2) }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Tax:</span>
                                <span class="font-medium">${{ number_format($order->tax, 2) }}</span>
                            </div>
                            <div class="flex justify-between text-lg font-semibold pt-2 border-t">
                                <span>Total:</span>
                                <span>${{ number_format($order->total, 2) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        const stripe = Stripe('{{ $stripeKey }}');
        const clientSecret = '{{ $paymentIntent->client_secret }}';
        const paymentIntentId = '{{ $paymentIntent->id }}';

        const elements = stripe.elements();
        const cardElement = elements.create('card', {
            style: {
                base: {
                    fontSize: '16px',
                    color: '#424770',
                    '::placeholder': {
                        color: '#aab7c4',
                    },
                },
                invalid: {
                    color: '#9e2146',
                },
            },
        });

        cardElement.mount('#card-element');

        const form = document.getElementById('payment-form');
        const submitButton = document.getElementById('submit-button');
        const buttonText = document.getElementById('button-text');
        const buttonSpinner = document.getElementById('button-spinner');
        const cardErrors = document.getElementById('card-errors');
        const paymentError = document.getElementById('payment-error');
        const paymentErrorMessage = document.getElementById('payment-error-message');

        cardElement.on('change', function(event) {
            if (event.error) {
                cardErrors.textContent = event.error.message;
            } else {
                cardErrors.textContent = '';
            }
        });

        form.addEventListener('submit', async function(event) {
            event.preventDefault();

            submitButton.disabled = true;
            buttonText.classList.add('hidden');
            buttonSpinner.classList.remove('hidden');
            cardErrors.textContent = '';
            paymentError.classList.add('hidden');

            try {
                // Create payment method
                const { error: pmError, paymentMethod } = await stripe.createPaymentMethod({
                    type: 'card',
                    card: cardElement,
                });

                if (pmError) {
                    throw pmError;
                }

                // Confirm payment intent (handles 3DS automatically)
                const { error: confirmError, paymentIntent } = await stripe.confirmCardPayment(clientSecret, {
                    payment_method: paymentMethod.id,
                });

                if (confirmError) {
                    // Handle 3D Secure authentication
                    if (confirmError.type === 'card_error' && confirmError.payment_intent) {
                        const paymentIntent = confirmError.payment_intent;
                        
                        if (paymentIntent.status === 'requires_action') {
                            // Confirm again after 3DS authentication
                            const { error: retryError, paymentIntent: finalIntent } = await stripe.confirmCardPayment(
                                paymentIntent.client_secret
                            );

                            if (retryError) {
                                throw retryError;
                            }

                            if (finalIntent.status === 'succeeded') {
                                handlePaymentSuccess(finalIntent);
                            } else {
                                throw new Error('Payment was not successful after authentication.');
                            }
                        } else {
                            throw confirmError;
                        }
                    } else {
                        throw confirmError;
                    }
                } else if (paymentIntent.status === 'succeeded') {
                    handlePaymentSuccess(paymentIntent);
                } else if (paymentIntent.status === 'requires_action') {
                    // Handle additional authentication if needed
                    const { error: actionError, paymentIntent: finalIntent } = await stripe.confirmCardPayment(
                        paymentIntent.client_secret
                    );

                    if (actionError) {
                        throw actionError;
                    }

                    if (finalIntent.status === 'succeeded') {
                        handlePaymentSuccess(finalIntent);
                    } else {
                        throw new Error('Payment was not successful after authentication.');
                    }
                } else {
                    throw new Error('Payment was not successful.');
                }
            } catch (error) {
                showError(error.message || 'An error occurred while processing your payment.');
                submitButton.disabled = false;
                buttonText.classList.remove('hidden');
                buttonSpinner.classList.add('hidden');
            }
        });

        function handlePaymentSuccess(paymentIntent) {
            // Send payment confirmation to server
            fetch('{{ route("payments.process", $order) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({
                    payment_intent_id: paymentIntent.id,
                    payment_method_id: paymentIntent.payment_method,
                }),
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = data.redirect || '{{ route("orders.show", $order) }}';
                } else {
                    showError(data.error || 'Payment confirmation failed.');
                    submitButton.disabled = false;
                    buttonText.classList.remove('hidden');
                    buttonSpinner.classList.add('hidden');
                }
            })
            .catch(error => {
                showError('An error occurred while confirming your payment.');
                submitButton.disabled = false;
                buttonText.classList.remove('hidden');
                buttonSpinner.classList.add('hidden');
            });
        }

        function showError(message) {
            paymentError.classList.remove('hidden');
            paymentErrorMessage.textContent = message;
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    </script>
</body>
</html>

