@extends('layouts.admin.app')

@section('content')
<div class="container">
    <h2>All Tables & Index Details</h2>

    @foreach($result as $table => $indexes)
        <div style="margin-top:40px;">
            <h3>Table: {{ $table }}</h3>

            <table border="1" cellpadding="8" cellspacing="0" width="100%">
                <thead>
                    <tr>
                        <th>Index Name</th>
                        <th>Column Name</th>
                        <th>Non-Unique</th>
                        <th>Seq</th>
                        <th>Index Type</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($indexes as $index)
                        <tr>
                            <td>{{ $index->Key_name }}</td>
                            <td>{{ $index->Column_name }}</td>
                            <td>{{ $index->Non_unique }}</td>
                            <td>{{ $index->Seq_in_index }}</td>
                            <td>{{ $index->Index_type }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endforeach
</div>
@endsection
