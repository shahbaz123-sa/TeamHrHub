<html>
<head>
    <style>
        /* Layout & spacing */
        .p-0 { padding: 0 !important; }
        .p-2 { padding: .5rem !important; }
        .m-0 { margin: 0 !important; }
        .m-5 { margin: 3rem !important; }
        .mb-0 { margin-bottom: 0 !important; }
        .mt-2 { margin-top: .5rem !important; }
        .mt-3 { margin-top: 1rem !important; }
        .mt-4 { margin-top: 1.5rem !important; }
        .mt-5 { margin-top: 3rem !important; }
        .flex-grow-1 { flex-grow: 1 !important; }
        .text-end { text-align: right !important; }

        /* Typography */
        .text-muted { color: #6c757d !important; }
        .text-main { color: #D55835 !important; }
        .text-h2 { font-size: 2rem; font-weight: 500; margin-bottom: .5rem; }
        .text-h3 { font-size: 1.5rem; font-weight: 500; margin-bottom: 1rem; }
        .text-h6 { font-size: 1rem; font-weight: 500; }
        .text-left { text-align: left !important; }
        .text-bg-primary { color: #fff; background-color: #0d6efd; }
        .text-bg-success { color: #fff; background-color: #198754; }
        .text-bg-info { color: #fff; background-color: #0dcaf0; }
        .text-bg-warning { color: #fff; background-color: #ffc107; }
        .text-bg-danger { color: #fff; background-color: #dc3545; }
        
        /* Table alignment */
        .align-middle { vertical-align: middle !important; }
        /* Table */
        .table { width: 100%; margin-bottom: 1rem; color: #212529; border-collapse: collapse; }
        .table th,
        .table td { padding: .75rem; vertical-align: middle; }
        .table-bordered th, .table-bordered td { border: 1px solid #c8cacd; }
        .table-bordered { border: 1px solid #dee2e6; }
        .table-danger { background-color: #f8d7da; }
        .table-primary { background-color: #cfe2ff; }
        .table-secondary { background-color: #e2e3e5; }
        .table-warning { background-color: #e5d6a9; }
        /* Responsive table wrapper */
        .table-responsive { display: block; width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; }
        
        /* Badge */
        .badge { display: inline-block; padding: .35em .65em; font-size: .75em; font-weight: 700; line-height: 1; text-align: center; white-space: nowrap; vertical-align: baseline; border-radius: .25rem; }
        
        /* Utilities */
        strong { font-weight: bolder; }
        small { font-size: 80%; }

        /* Optional: fix <br> spacing in items */
        td span + br { line-height: .5; }

        /* Custom fonts for PDF */
        body { font-family: DejaVu Sans, sans-serif; font-size: 14px; }
    </style>
</head>
<body>
    <div class="m-5">
        <div class="table-responsive">
            <table class="table">
                <tr>
                    <td class="p-0">
                        <h2 class="text-h2">Daily Report</h2>
                    </td>
                    <td class="p-0 text-end">
                        <h2 class="text-h2 text-main">11:55 PM</h2>
                        <p class="mt-2 text-muted">{{ now()->format('d M Y') }}</p>
                    </td>
                </tr>
            </table>
        </div>
        <div class="mt-4">
            <div>
                <h3 class="text-h3 text-left">Orders</h3>
            </div>
            <div class="table-responsive mt-4">
                <table class="table table-bordered">
                    <thead>
                        <tr class="table-danger">
                            <td class="align-middle"><h6 class="m-0 text-h6">ID</h6></td>
                            <td class="align-middle"><h6 class="m-0 text-h6">Product (Qty)</h6></td>
                            <td class="align-middle"><h6 class="m-0 text-h6">Date</h6></td>
                            <td class="align-middle"><h6 class="m-0 text-h6">Customer</h6></td>
                            <td class="align-middle"><h6 class="m-0 text-h6">Phone</h6></td>
                            <td class="align-middle"><h6 class="m-0 text-h6">Address</h6></td>
                            <td class="align-middle"><h6 class="m-0 text-h6">Amount</h6></td>
                            <td class="align-middle"><h6 class="m-0 text-h6">Status</h6></td>
                            <td class="align-middle"><h6 class="m-0 text-h6">Cancel<br>Reason</h6></td>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($orders as $order)
                        <tr>
                            <td class="align-middle">
                                <strong class="text-main">{{ data_get($order, 'order_name') }}</strong><br/>
                                <p class="mb-0 mt-2"><small class="text-muted">{{ data_get($order, 'order_type') }} Order</small></p>
                            </td>
                            <td class="align-middle">
                                <ol>
                                    @foreach (data_get($order, 'items', []) as $item)
                                        <li>{{ data_get($item, 'product.name') }} ({{ data_get($item, 'quantity') }})</li>
                                    @endforeach
                                </ol>
                            </td>
                            <td class="align-middle">{{ data_get($order, 'order_date') }}</td>
                            <td class="align-middle">{{ data_get($order, 'customer.type') === 'B2B' ? data_get($order, 'customer.company.company_name') : data_get($order, 'customer.profile.full_name') }}</td>
                            <td class="align-middle">{{ data_get($order, 'customer.phone_number') }}</td>
                            <td class="align-middle">{{ data_get($order, 'shipping_address') }}</td>
                            <td class="align-middle">{{ data_get($order, 'total_amount') }}</td>
                            <td class="align-middle">
                                <span class="badge {{ $statusColors[data_get($order, 'status')] ?? 'text-bg-primary' }} p-2">
                                    {{ ucfirst(data_get($order, 'status')) }}
                                </span>
                            </td>
                            <td class="align-middle">{{ data_get($order, 'cancel_reason') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-4">
            <div>
                <h3 class="text-h3 text-left">RFQ (Request for Quotation)</h3>
            </div>
            <div class="table-responsive mt-4">
                <table class="table table-bordered">
                    <thead>
                        <tr class="table-primary">
                            <td class="align-middle"><h6 class="m-0 text-h6">ID</h6></td>
                            <td class="align-middle"><h6 class="m-0 text-h6">Product</h6></td>
                            <td class="align-middle"><h6 class="m-0 text-h6">Quantity</h6></td>
                            <td class="align-middle"><h6 class="m-0 text-h6">Uom</h6></td>
                            <td class="align-middle"><h6 class="m-0 text-h6">Req Date</h6></td>
                            <td class="align-middle"><h6 class="m-0 text-h6">Customer</h6></td>
                            <td class="align-middle"><h6 class="m-0 text-h6">Phone</h6></td>
                            <td class="align-middle"><h6 class="m-0 text-h6">Status</h6></td>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rfqs as $rfq)
                        <tr>
                            <td class="align-middle">
                                <strong class="text-main">{{ data_get($rfq, 'reference_no') }}</strong><br/>
                                <p class="mb-0 mt-2"><small class="text-muted">RFQ</small></p>
                            </td>
                            <td class="align-middle">{{ data_get($rfq, 'item.product.name') }}</td>
                            <td class="align-middle">{{ data_get($rfq, 'item.quantity') }}</td>
                            <td class="align-middle">{{ data_get($rfq, 'item.uom') }}</td>
                            <td class="align-middle">{{ data_get($rfq, 'req_date') }}</td>
                            <td class="align-middle">{{ data_get($rfq, 'customer_name') }}</td>
                            <td class="align-middle">{{ data_get($rfq, 'user.phone_number') }}</td>
                            <td class="align-middle">
                                <span class="badge {{ $statusColors[data_get($rfq, 'status')] ?? 'text-bg-primary' }} p-2">
                                    {{ ucfirst(data_get($rfq, 'status_label')) }}
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="mt-4">
            <div>
                <h3 class="text-h3 text-left">Credit Requests</h3>
            </div>
            <div class="table-responsive mt-4">
                <table class="table table-bordered">
                    <thead>
                        <tr class="table-secondary">
                            <td class="align-middle"><h6 class="m-0 text-h6">ID</h6></td>
                            <td class="align-middle"><h6 class="m-0 text-h6">Commodity</h6></td>
                            <td class="align-middle"><h6 class="m-0 text-h6">Date</h6></td>
                            <td class="align-middle"><h6 class="m-0 text-h6">Customer</h6></td>
                            <td class="align-middle"><h6 class="m-0 text-h6">Phone</h6></td>
                            <td class="align-middle"><h6 class="m-0 text-h6">Req<br>Amount</h6></td>
                            <td class="align-middle"><h6 class="m-0 text-h6">Approved<br>Amount</h6></td>
                            <td class="align-middle"><h6 class="m-0 text-h6">Status</h6></td>
                            <td class="align-middle"><h6 class="m-0 text-h6">Rejection<br>Reason</h6></td>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($creditRequests as $creditRequest)
                        <tr>
                            <td class="align-middle">
                                <strong class="text-main">{{ data_get($creditRequest, 'reference_no') }}</strong><br/>
                                <p class="mb-0 mt-2"><small class="text-muted">Credit Request</small></p>
                            </td>
                            <td class="align-middle">{{ data_get($creditRequest, 'category.name') }}</td>
                            <td class="align-middle">{{ data_get($creditRequest, 'date') }}</td>
                            <td class="align-middle">{{ data_get($creditRequest, 'customer_name') }}</td>
                            <td class="align-middle">{{ data_get($creditRequest, 'user.phone_number') }}</td>
                            <td class="align-middle">{{ data_get($creditRequest, 'formatted_req_credit_limit') }}</td>
                            <td class="align-middle">{{ data_get($creditRequest, 'formatted_app_credit_limit') }}</td>
                            <td class="align-middle">
                                <span class="badge {{ $statusColors[strtolower(data_get($creditRequest, 'status'))] ?? 'text-bg-primary' }} p-2">
                                    {{ ucfirst(data_get($creditRequest, 'status_label')) }}
                                </span>
                            </td>
                            <td class="align-middle">{{ data_get($creditRequest, 'rejection_reason') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-4">
            <div>
                <h3 class="text-h3 text-left">Supplier Requests</h3>
            </div>
            <div class="table-responsive mt-4">
                <table class="table table-bordered">
                    <thead>
                        <tr class="table-warning">
                            <td class="align-middle"><h6 class="m-0 text-h6">Name</h6></td>
                            <td class="align-middle"><h6 class="m-0 text-h6">Phone</h6></td>
                            <td class="align-middle"><h6 class="m-0 text-h6">Email</h6></td>
                            <td class="align-middle"><h6 class="m-0 text-h6">Address</h6></td>
                            <td class="align-middle"><h6 class="m-0 text-h6">Type</h6></td>
                            <td class="align-middle"><h6 class="m-0 text-h6">Brand</h6></td>
                            <td class="align-middle"><h6 class="m-0 text-h6">Commodity</h6></td>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($suppliers as $supplier)
                        <tr>
                            <td class="align-middle">{{ data_get($supplier, 'name') }}</td>
                            <td class="align-middle">{{ data_get($supplier, 'phone') }}</td>
                            <td class="align-middle">{{ data_get($supplier, 'email') }}</td>
                            <td class="align-middle">{{ data_get($supplier, 'address') }}</td>
                            <td class="align-middle">{{ ucfirst(data_get($supplier, 'type')) }}</td>
                            <td class="align-middle">{{ ucfirst(data_get($supplier, 'brand')) }}</td>
                            <td class="align-middle">{{ ucfirst(data_get($supplier, 'product_category', data_get($supplier, 'commodity'))) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</body>
</html>
