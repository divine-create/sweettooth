@if($forCsv ?? false)
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Branch Name</th>
                <th>Code</th>
                <th>Location</th>
                <th>City</th>
                <th>Country</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Manager</th>
                <th>Status</th>
                <th>Created Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data as $branch)
                <tr>
                    <td>{{ $branch->id }}</td>
                    <td>{{ $branch->name }}</td>
                    <td>{{ $branch->code }}</td>
                    <td>{{ $branch->location ?? '-' }}</td>
                    <td>{{ $branch->city ?? '-' }}</td>
                    <td>{{ $branch->country ?? '-' }}</td>
                    <td>{{ $branch->email ?? '-' }}</td>
                    <td>{{ $branch->phone ?? '-' }}</td>
                    <td>{{ $branch->manager?->name ?? '-' }}</td>
                    <td>{{ $branch->is_active ? 'Active' : 'Inactive' }}</td>
                    <td>{{ $branch->created_at?->format('Y-m-d H:i') ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif