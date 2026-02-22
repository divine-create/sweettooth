@if($forCsv ?? false)
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Department Name</th>
                <th>Category</th>
                <th>Branch</th>
                <th>Description</th>
                <th>Created Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data as $department)
                <tr>
                    <td>{{ $department->id }}</td>
                    <td>{{ $department->name }}</td>
                    <td>{{ $department->category?->name ?? '-' }}</td>
                    <td>{{ $department->branch?->name ?? 'Global' }}</td>
                    <td>{{ $department->description ?? '-' }}</td>
                    <td>{{ $department->created_at?->format('Y-m-d H:i') ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif