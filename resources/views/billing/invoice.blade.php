<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        :root { color-scheme: light; font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; color: #172554; }
        * { box-sizing: border-box; }
        body { margin: 0; padding: 40px 20px; background: #f1f5f9; }
        .invoice { max-width: 820px; margin: auto; padding: 48px; background: white; border: 1px solid #dbe3ef; border-radius: 14px; box-shadow: 0 12px 35px #0f172a0c; }
        header, .row, .totals { display: flex; justify-content: space-between; gap: 24px; }
        header { padding-bottom: 32px; border-bottom: 1px solid #e2e8f0; }
        h1 { margin: 0; font-size: 30px; } h2 { margin: 30px 0 10px; font-size: 16px; }
        p { margin: 6px 0; color: #475569; line-height: 1.5; }
        .meta { text-align: right; } .label { color: #64748b; font-size: 12px; text-transform: uppercase; letter-spacing: .08em; }
        table { width: 100%; margin-top: 28px; border-collapse: collapse; }
        th, td { padding: 14px 10px; border-bottom: 1px solid #e2e8f0; text-align: left; }
        th { color: #64748b; font-size: 12px; text-transform: uppercase; }
        .amount { text-align: right; white-space: nowrap; }
        .totals { justify-content: flex-end; margin-top: 18px; }
        .totals table { width: min(100%, 330px); margin: 0; }
        .total { font-size: 18px; font-weight: 700; color: #1d4ed8; }
        .status { display: inline-block; padding: 6px 10px; border-radius: 99px; background: #dcfce7; color: #166534; font-size: 12px; font-weight: 700; text-transform: uppercase; }
        .actions { max-width: 820px; margin: 0 auto 16px; text-align: right; }
        button { padding: 10px 16px; color: white; background: #1d4ed8; border: 0; border-radius: 8px; font: inherit; font-weight: 600; cursor: pointer; }
        @media (max-width: 600px) { body { padding: 12px; } .invoice { padding: 24px 18px; } header { flex-direction: column; } .meta { text-align: left; } }
        @media print { body { padding: 0; background: white; } .invoice { max-width: none; border: 0; border-radius: 0; box-shadow: none; padding: 20px; } .actions { display: none; } }
    </style>
</head>
<body>
    <div class="actions"><button type="button" onclick="window.print()">Print / Save as PDF</button></div>
    <main class="invoice">
        <header>
            <div><p class="label">NDK Job Platform</p><h1>Invoice</h1><p>{{ $invoice->invoice_number }}</p></div>
            <div class="meta"><span class="status">{{ str_replace('_', ' ', $invoice->status) }}</span><p><strong>Issued</strong> {{ $invoice->issued_at?->format('M j, Y') ?? '—' }}</p><p><strong>Paid</strong> {{ $invoice->paid_at?->format('M j, Y') ?? '—' }}</p></div>
        </header>
        <section class="row">
            <div><h2>Bill to</h2><p><strong>{{ data_get($invoice->bill_to_snapshot, 'company_name', $invoice->user?->name) }}</strong></p><p>{{ data_get($invoice->bill_to_snapshot, 'contact_name', $invoice->user?->name) }}</p><p>{{ data_get($invoice->bill_to_snapshot, 'contact_email', $invoice->user?->email) }}</p></div>
            <div class="meta"><h2>Order</h2><p>{{ $invoice->order?->order_number }}</p></div>
        </section>
        <table>
            <thead><tr><th>Description</th><th>Quantity</th><th class="amount">Amount</th></tr></thead>
            <tbody>
                @foreach ($invoice->order?->items ?? [] as $item)
                    <tr><td>{{ $item->item_name }}</td><td>{{ $item->quantity }}</td><td class="amount">{{ number_format($item->total_amount) }} {{ $invoice->currency }}</td></tr>
                @endforeach
            </tbody>
        </table>
        <div class="totals"><table>
            <tr><td>Subtotal</td><td class="amount">{{ number_format($invoice->subtotal_amount) }} {{ $invoice->currency }}</td></tr>
            <tr><td>Discount</td><td class="amount">−{{ number_format($invoice->discount_amount) }} {{ $invoice->currency }}</td></tr>
            <tr class="total"><td>Total</td><td class="amount">{{ number_format($invoice->total_amount) }} {{ $invoice->currency }}</td></tr>
        </table></div>
        <p style="margin-top: 36px; font-size: 12px;">This invoice records the order and payment status in the platform.</p>
    </main>
</body>
</html>
