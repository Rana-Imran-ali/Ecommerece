<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject ?? 'Order Status Update' }}</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #f4f5f7;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            color: #1f2937;
            line-height: 1.5;
        }
        .email-wrapper {
            width: 100%;
            background-color: #f4f5f7;
            padding: 32px 16px;
        }
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
            border: 1px solid #e5e7eb;
        }
        .email-header {
            background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%);
            padding: 28px 32px;
            text-align: center;
            color: #ffffff;
        }
        .email-header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 800;
            letter-spacing: -0.025em;
        }
        .email-header p {
            margin: 6px 0 0;
            font-size: 13px;
            color: rgba(255, 255, 255, 0.85);
        }
        .email-body {
            padding: 32px;
        }
        .status-badge-container {
            text-align: center;
            margin-bottom: 24px;
        }
        .status-badge {
            display: inline-block;
            padding: 6px 16px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .badge-pending { background-color: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
        .badge-processing { background-color: #dbeafe; color: #1e40af; border: 1px solid #bfdbfe; }
        .badge-out_for_delivery, .badge-shipped { background-color: #ede9fe; color: #5b21b6; border: 1px solid #ddd6fe; }
        .badge-delivered { background-color: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
        .badge-cancelled { background-color: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }

        .headline {
            font-size: 20px;
            font-weight: 700;
            color: #111827;
            margin: 0 0 12px;
            text-align: center;
        }
        .greeting {
            font-size: 15px;
            color: #4b5563;
            margin-bottom: 20px;
            text-align: center;
        }
        
        /* Highlight box for expected delivery date */
        .delivery-highlight-box {
            background-color: #eef2ff;
            border: 1px solid #c7d2fe;
            border-radius: 10px;
            padding: 18px 20px;
            margin: 24px 0;
            display: flex;
            align-items: center;
            text-align: left;
        }
        .delivery-icon {
            font-size: 28px;
            margin-right: 16px;
            line-height: 1;
        }
        .delivery-content .delivery-label {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            color: #4f46e5;
            letter-spacing: 0.05em;
            margin: 0 0 2px;
        }
        .delivery-content .delivery-date {
            font-size: 16px;
            font-weight: 800;
            color: #1e1b4b;
            margin: 0;
        }

        /* Order items table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 24px 0 16px;
        }
        .items-table th {
            text-align: left;
            padding: 10px 12px;
            font-size: 11px;
            text-transform: uppercase;
            color: #6b7280;
            font-weight: 700;
            border-bottom: 2px solid #e5e7eb;
            background-color: #f9fafb;
        }
        .items-table td {
            padding: 12px;
            border-bottom: 1px solid #f3f4f6;
            font-size: 14px;
            vertical-align: middle;
        }
        .item-name {
            font-weight: 600;
            color: #111827;
        }
        .item-qty {
            color: #6b7280;
            font-size: 13px;
        }
        .text-right {
            text-align: right;
        }

        /* Financial summary table */
        .summary-box {
            width: 100%;
            margin-top: 12px;
            border-top: 2px solid #e5e7eb;
            padding-top: 12px;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 4px 0;
            font-size: 14px;
            color: #4b5563;
        }
        .summary-total {
            font-size: 17px;
            font-weight: 800;
            color: #111827;
            border-top: 1px solid #e5e7eb;
            padding-top: 8px;
            margin-top: 6px;
        }

        /* Info columns (shipping & payment) */
        .info-grid {
            margin: 28px 0;
            border-top: 1px solid #e5e7eb;
            padding-top: 20px;
        }
        .info-col {
            background-color: #f9fafb;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 12px;
            font-size: 13px;
            color: #4b5563;
            border: 1px solid #f3f4f6;
        }
        .info-col strong {
            display: block;
            font-size: 11px;
            text-transform: uppercase;
            color: #6b7280;
            margin-bottom: 6px;
            letter-spacing: 0.05em;
        }

        /* CTA Button */
        .cta-container {
            text-align: center;
            margin: 32px 0 16px;
        }
        .cta-button {
            display: inline-block;
            background-color: #4f46e5;
            color: #ffffff !important;
            font-weight: 700;
            font-size: 14px;
            padding: 14px 32px;
            text-decoration: none;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(79, 70, 229, 0.25);
        }

        /* Footer */
        .email-footer {
            background-color: #f9fafb;
            padding: 24px 32px;
            text-align: center;
            font-size: 12px;
            color: #6b7280;
            border-top: 1px solid #e5e7eb;
        }
        .email-footer p {
            margin: 4px 0;
        }
        .email-footer a {
            color: #4f46e5;
            text-decoration: none;
        }
    </style>
</head>
<body>
<div class="email-wrapper">
    <div class="email-container">
        <!-- Header -->
        <div class="email-header">
            <h1>{{ config('app.name', 'EStore') }}</h1>
            <p>Order Update Notification</p>
        </div>

        <!-- Body -->
        <div class="email-body">
            <!-- Status Badge -->
            <div class="status-badge-container">
                <span class="status-badge badge-{{ $order->status }}">
                    {{ $statusLabel }}
                </span>
            </div>

            <!-- Headline -->
            <h2 class="headline">{{ $headline }}</h2>
            <p class="greeting">
                Hello {{ $customerName }},<br>
                {{ $statusMessage }}
            </p>

            <!-- Expected Delivery Date Box (if present and not delivered/cancelled) -->
            @if(!empty($expectedDeliveryDate) && !in_array($order->status, ['delivered', 'cancelled']))
            <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#eef2ff; border:1px solid #c7d2fe; border-radius:10px; margin:20px 0; padding:16px;">
                <tr>
                    <td width="48" align="center" style="font-size:30px; vertical-align:middle;">
                        🚚
                    </td>
                    <td style="padding-left:12px; vertical-align:middle;">
                        <div style="font-size:11px; font-weight:700; text-transform:uppercase; color:#4f46e5; letter-spacing:0.05em; margin-bottom:2px;">
                            Expected Delivery Date
                        </div>
                        <div style="font-size:16px; font-weight:800; color:#1e1b4b;">
                            {{ $expectedDeliveryDate }}
                        </div>
                    </td>
                </tr>
            </table>
            @elseif($order->status === 'delivered')
            <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#ecfdf5; border:1px solid #a7f3d0; border-radius:10px; margin:20px 0; padding:16px;">
                <tr>
                    <td width="48" align="center" style="font-size:30px; vertical-align:middle;">
                        🎉
                    </td>
                    <td style="padding-left:12px; vertical-align:middle;">
                        <div style="font-size:11px; font-weight:700; text-transform:uppercase; color:#059669; letter-spacing:0.05em; margin-bottom:2px;">
                            Delivery Status
                        </div>
                        <div style="font-size:16px; font-weight:800; color:#064e3b;">
                            Delivered to your address
                        </div>
                    </td>
                </tr>
            </table>
            @endif

            <!-- Order Items -->
            <table class="items-table" cellpadding="0" cellspacing="0">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th class="text-right" width="60">Qty</th>
                        <th class="text-right" width="80">Price</th>
                        <th class="text-right" width="80">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->items as $item)
                    <tr>
                        <td>
                            <div class="item-name">{{ $item->product_name ?? $item->product?->name ?? 'Product' }}</div>
                        </td>
                        <td class="text-right item-qty">{{ $item->quantity }}</td>
                        <td class="text-right">${{ number_format($item->price, 2) }}</td>
                        <td class="text-right font-bold">${{ number_format($item->price * $item->quantity, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <!-- Financial Summary -->
            <table width="100%" cellpadding="0" cellspacing="0" style="border-top: 1px solid #e5e7eb; padding-top: 12px; margin-bottom: 20px;">
                <tr>
                    <td style="padding: 4px 0; color: #6b7280; font-size: 14px;">Total Amount:</td>
                    <td class="text-right" style="padding: 4px 0; font-size: 17px; font-weight: 800; color: #111827;">
                        ${{ number_format($order->total_amount, 2) }}
                    </td>
                </tr>
            </table>

            <!-- Shipping & Payment Details -->
            <div class="info-grid">
                @if($order->address)
                <div class="info-col">
                    <strong>📍 Delivery Address</strong>
                    <div>{{ $order->address->name ?? $customerName }}</div>
                    <div>{{ $order->address->address_line1 }}{{ $order->address->address_line2 ? ', ' . $order->address->address_line2 : '' }}</div>
                    <div>{{ $order->address->city }}, {{ $order->address->state }} {{ $order->address->postal_code }}</div>
                    <div>{{ $order->address->country }}</div>
                    @if($order->address->phone)
                    <div style="margin-top: 4px; color: #9ca3af;">Phone: {{ $order->address->phone }}</div>
                    @endif
                </div>
                @endif

                <div class="info-col">
                    <strong>📧 Notification Recipient</strong>
                    <div>Updates sent to: <strong>{{ $recipientEmail }}</strong></div>
                    <div style="margin-top: 2px; color: #9ca3af;">Order ID: #{{ $order->id }}</div>
                </div>
            </div>

            <!-- CTA View / Track Order -->
            <div class="cta-container">
                <a href="{{ $orderUrl }}" class="cta-button" target="_blank">
                    View Order Details &rarr;
                </a>
            </div>
        </div>

        <!-- Footer -->
        <div class="email-footer">
            <p>This automated message was sent to <strong>{{ $recipientEmail }}</strong>.</p>
            <p>&copy; {{ date('Y') }} {{ config('app.name', 'EStore') }}. All rights reserved.</p>
            <p><a href="{{ url('/') }}">Visit Store</a> &bull; <a href="{{ url('/contact') }}">Customer Support</a></p>
        </div>
    </div>
</div>
</body>
</html>
