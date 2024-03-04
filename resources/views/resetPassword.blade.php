
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<h1>Reset Password</h1>

<form method="POST" name="resetPasswordForm" id="resetPasswordForm">
    @csrf
    <div>
        <label for="email">Email:</label>
        <input type="text" id="email" name="email" value="{{ $user->email }}" disabled>
        <input type="hidden" id="token" name="token" value="{{ $token }}">
    </div>
    <div>
        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required>
        
    </div>
    <div>
        <label for="password">Confirm Password:</label>
        <input type="password" id="confirmPassword" name="confirmPassword" required>
        <span id="confirm_password_error" class="error"></span>
    </div>
    <div>
        <button type="submit">Submit</button>
        <span id="error_message" style="color: red;"></span>
    </div>
</form>

<script>
    $(document).ready(function() {
        $('#resetPasswordForm').submit(function(event) {
            event.preventDefault();

            var email = $('#email').val();
            var token = $('#token').val();
            var password = $('#password').val();
            var confirmPassword = $('#confirmPassword').val();

            //compare password and confirm password
            if (password != confirmPassword) {
                $('#confirm_password_error').text('Password and Confirm Password do not match');
                return;
            }
            var actionUrl = 'http://127.0.0.1:8000/api/reset-password';
            $.ajax({
                url: actionUrl,
                method: 'POST',
                data: {
                    token: token,
                    email: email,
                    password: password
                },
                success: function(response) {
                    if (response.success == false) {
                        $('#error_message').text(response.msg);
                    } else if (response.success == true) {   
                       alert(response.msg);                     
                        window.open('http://127.0.0.1:8080/profile', '_self');
                    } else {

                        console.log(response);

                    }
                },
                error: function(jqXHR, status, error) {
                    var response = JSON.parse(jqXHR.responseText);
                    console.log(jqXHR.responseText);


                }
            });
        });
    });
</script>