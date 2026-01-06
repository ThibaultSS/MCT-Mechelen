<!DOCTYPE html>
<html>
<head>
    <title>login</title>
</head>
<body>
    <header>
        <h1>My Laravel App</h1>
    </header>
    <main>
        <h2>Login</h2>
        <form method="POST" action="/login">
            @csrf
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" required>
            <br>
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>
            <br>
            <button type="submit">Login</button>
        </form>
    </main>
    <footer>
        <p>&copy; 2025 My Laravel App</p>
    </footer>
</body>
</html>