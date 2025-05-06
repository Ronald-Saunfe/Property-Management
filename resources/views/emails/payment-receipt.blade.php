<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Payment Receipt</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .receipt-box {
            border: 1px solid #ddd;
            padding: 20px;
            border-radius: 5px;
        }
        .receipt-title {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 20px;
            color: #2c3e50;
        }
        .receipt-details {
            margin-bottom: 20px;
        }
        .receipt-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        .receipt-label {
            font-weight: bold;
            color: #555;
        }
        .receipt-value {
            text-align: right;
        }
        .receipt-total {
            font-size: 18px;
            font-weight: bold;
            margin-top: 20px;
            text-align: right;
        }
        .footer {
            margin-top: 30px;
            font-size: 12px;
            color: #777;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $property->name ?? 'Property Management System' }}</h1>
    </div>
    
    <div class="receipt-box">
        <div class="receipt-title">Payment Receipt</div>
        
        <div class="receipt-details">
            <div class="receipt-row">
                <span class="receipt-label">Receipt #:</span>
                <span class="receipt-value">{{ $payment->id }}</span>
            </div>
            
            <div class="receipt-row">
                <span class="receipt-label">Date:</span>
                <span class="receipt-value">{{ $payment->payment_date->format('F j, Y') }}</span>
            </div>
            
            <div class="receipt-row">
                <span class="receipt-label">Tenant:</span>
                <span class="receipt-value">{{ $tenant->first_name }} {{ $tenant->last_name }}</span>
            </div>
            
            <div class="receipt-row">
                <span class="receipt-label">Property:</span>
                <span class="receipt-value">{{ $property->name ?? 'N/A' }}</span>
            </div>
            
            <div class="receipt-row">
                <span class="receipt-label">Unit:</span>
                <span class="receipt-value">{{ $unit->unit_number }}</span>
            </div>
            
            <div class="receipt-row">
                <span class="receipt-label">Payment Method:</span>
                <span class="receipt-value">{{ ucfirst($payment->payment_method ?? 'N/A') }}</span>
            </div>
            
            <div class="receipt-row">
                <span class="receipt-label">Transaction ID:</span>
                <span class="receipt-value">{{ $payment->transaction_id ?? 'N/A' }}</span>
            </div>
            
            <div class="receipt-row">
                <span class="receipt-label">Description:</span>
                <span class="receipt-value">Rent payment for {{ $payment->due_date->format('F Y') }}</span>
            </div>
        </div>
        
        <div class="receipt-total">
            Total Amount: ${{ number_format($payment->amount, 2) }}
        </div>
    </div>
    
    <div class="footer">
        <p>Thank you for your payment. This is an automatically generated receipt.</p>
        <p>If you have any questions, please contact your property manager.</p>
    </div>
</body>
</html>