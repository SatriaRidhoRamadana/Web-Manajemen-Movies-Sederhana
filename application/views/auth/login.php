<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Login</title>
    <style>
        body{font-family:Segoe UI,Arial,Helvetica,sans-serif;background:#f5f7fb;margin:0;padding:0;display:flex;align-items:center;justify-content:center;height:100vh}
        .card{background:#fff;padding:28px;border-radius:8px;box-shadow:0 6px 20px rgba(23,43,77,0.08);width:360px}
        h1{margin:0 0 12px;font-size:20px;color:#172b4d}
        label{display:block;margin-bottom:6px;color:#42526e;font-size:13px}
        input[type=text],input[type=password]{width:100%;padding:10px 12px;margin-bottom:12px;border:1px solid #e2e8f0;border-radius:6px;font-size:14px}
        button{width:100%;padding:10px 12px;background:#2f80ed;border:none;color:#fff;border-radius:6px;font-size:15px;cursor:pointer}
        .msg{padding:10px;margin-bottom:12px;border-radius:6px;display:none}
        .msg.error{background:#fff5f5;border:1px solid #f1a5a5;color:#9b1c1c}
        .msg.success{background:#f3fff2;border:1px solid #a7e3a0;color:#1a7a2e}
        .token{word-break:break-all;background:#f6f8fb;padding:8px;border-radius:6px;font-size:12px;margin-top:8px}
    </style>
</head>
<body>
    <div class="card">
        <h1>Login</h1>
        <?php echo '<form id="loginForm" action="' . site_url('auth/login') . '" method="post">'; ?>
            <label for="name">Name</label>
            <input type="text" id="name" name="name" autocomplete="username" />

            <label for="password">Password</label>
            <input type="password" id="password" name="password" autocomplete="current-password" />

            <div id="msg" class="msg"></div>

            <button type="submit">Login</button>
        <?php echo '</form>'; ?>

        <!-- result container kept for accessible messages but token is not shown -->
        <div id="result" style="margin-top:12px;display:none">
            <div class="msg success" id="successBox" style="display:none">Login successful</div>
        </div>
    </div>

    <script>
    (function(){
        var form = document.getElementById('loginForm');
        var msg = document.getElementById('msg');
        var result = document.getElementById('result');
        var successBox = document.getElementById('successBox');
        var tokenBox = document.getElementById('tokenBox');

        form.addEventListener('submit', function(e){
            e.preventDefault();
            msg.style.display='none'; result.style.display='none';
            var data = new FormData(form);
            var xhr = new XMLHttpRequest();
            xhr.open('POST', form.action);
            xhr.onload = function(){
                try {
                    var res = JSON.parse(xhr.responseText || '{}');
                } catch(err) { res = {status:false, error:'Invalid server response'}; }

                if (xhr.status >= 200 && xhr.status < 300 && res.status) {
                    // store token in localStorage but do NOT display it in the UI
                    if (res.token) localStorage.setItem('api_token', res.token);
                    // show temporary success message
                    msg.className = 'msg success';
                    msg.textContent = res.message || 'Login successful';
                    msg.style.display = 'block';

                    // Clear form fields for privacy
                    try { form.reset(); } catch(e){}

                    // Make an immediate follow-up request using the token so it appears in Network -> Request Headers
                    // This helps you inspect the token in the Network tab without exposing the password there.
                    if (res.token) {
                        fetch('<?php echo site_url('api/users'); ?>', {
                            method: 'GET',
                            headers: {
                                'Authorization': 'Bearer ' + res.token,
                                'Accept': 'application/json'
                            },
                            credentials: 'same-origin'
                        }).then(function(r){
                            // ensure the network entry is created; we don't need the response here
                            return r.text();
                        }).then(function(){
                            // small delay so developer can inspect Network tab, then redirect
                            setTimeout(function(){ window.location.href = '<?php echo site_url('movies'); ?>'; }, 600);
                        }).catch(function(){
                            // network error - still redirect after short delay
                            setTimeout(function(){ window.location.href = '<?php echo site_url('movies'); ?>'; }, 600);
                        });
                    } else {
                        // no token: fallback redirect
                        setTimeout(function(){ window.location.href = '<?php echo site_url('movies'); ?>'; }, 600);
                    }
                } else {
                    msg.className = 'msg error';
                    msg.textContent = res.error || 'Login failed';
                    msg.style.display = 'block';
                }
            };
            xhr.onerror = function(){
                msg.className = 'msg error'; msg.textContent = 'Network error'; msg.style.display='block';
            };
            xhr.send(data);
        });
    })();
    </script>
</body>
</html>
