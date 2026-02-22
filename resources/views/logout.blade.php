<!DOCTYPE html>
<html>
<head>
    <title>Logout</title>
</head>
<body>
    <h1>Logging out...</h1>
    
    <form method="POST" action="{{ route('logout') }}" id="logoutForm">
        @csrf
    </form>
    
    <script>
        document.getElementById('logoutForm').submit();
    </script>
</body>
</html>
