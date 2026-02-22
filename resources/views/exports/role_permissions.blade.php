@if($forCsv ?? false)
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Role Name</th>
                <th>Guard</th>
                <th>Permissions</th>
                <th>Permissions Count</th>
                <th>Created Date</th>
                <th>Updated Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data as $role)
                <tr>
                    <td>{{ $role->id }}</td>
                    <td>{{ $role->name }}</td>
                    <td>{{ $role->guard_name }}</td>
                    <td>{{ $role->permissions->pluck('name')->join(', ') }}</td>
                    <td>{{ $role->permissions->count() }}</td>
                    <td>{{ $role->created_at?->format('Y-m-d H:i') ?? '-' }}</td>
                    <td>{{ $role->updated_at?->format('Y-m-d H:i') ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif