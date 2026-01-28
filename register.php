<?php include 'includes/header.php'; ?>

<div class="form-container">
    <h2>Create Account</h2>

    <form>
        <input type="text" placeholder="Full Name" required>
        <input type="email" placeholder="Email Address" required>
        <input type="password" placeholder="Password" required>
        <input type="password" placeholder="Confirm Password" required>
        <button type="submit">Register</button>
    </form>

    <p>
        Already have an account?
        <a href="login.php">Sign In</a>
    </p>
</div>

<?php include 'includes/footer.php'; ?>
