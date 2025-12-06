<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Sales Report</title>
</head>

<body>
    <table border="1">
        <thead>
            <tr>
                <th>Salesperson Name</th>
                <th>Category Name</th>
                <th>Total Quantity</th>
                <th>Product Name</th>
                <th>Total Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($salesData as $data)
                <tr>
                    <td>{{ $data->salesperson_name ?? 'N/A' }}</td>
                    <td>{{ $data->category_name }}</td>
                    <td>{{ $data->total_quantity ?? 0 }}</td>
                    <td>{{ $data->product_name }}</td>
                    <td>{{ number_format($data->total_amount, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>

</html>
