<div class="container text-center mt-5 login">
  <div class="card shadow-sm p-5">
      <h1>Log In</h1>

      <form method="post" action="dbconnect.php">
        <div class="mb-3">
          <label>Username</label>
          <input type="text" class="form-control" name="username" autofocus>
        </div>

        <div class="mb-3">
          <label>Password</label>
          <input type="password" class="form-control" name="password">
        </div>

        <button type="submit" class="btn btn-primary">Submit</button>
      </form>
  </div>
</div>