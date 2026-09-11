import sys

def update_file(filename):
    with open(filename, 'r', encoding='utf-8') as f:
        content = f.read()

    # 1. Add Turnstile JS to <head>
    if 'challenges.cloudflare.com' not in content:
        content = content.replace('</title>', '</title>\n    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>')

    # 2. Add Verification view HTML
    verification_html = """        <!-- Verify view -->
        <div id="modal-verify-view" class="auth-view" style="display: none; flex-direction: column; text-align: center; padding: 40px 20px;">
            <div class="auth-heading" style="margin-bottom: 30px;">
                <h1>Overenie e-mailu</h1>
                <p>Zadajte 6-miestny kód, ktorý sme vám poslali.</p>
            </div>
            <form id="verifyForm" onsubmit="submitVerify(event)">
                <div class="auth-field" style="margin-bottom: 20px;">
                    <span class="material-symbols-outlined auth-icon">pin</span>
                    <input type="text" id="verify-code" placeholder="000000" maxlength="6" required style="text-align: center; font-size: 24px; letter-spacing: 5px;">
                </div>
                <div id="verify-error" style="color: #ff6b6b; margin-bottom: 15px; font-size: 14px; display: none;"></div>
                <button type="submit" class="auth-submit" id="btn-verify">Overiť kód</button>
            </form>
        </div>"""
    
    if 'modal-verify-view' not in content:
        content = content.replace('<!-- Role Selection view -->', verification_html + '\n\n        <!-- Role Selection view -->')

    # 3. Add IDs to inputs
    # We replace them carefully by finding the blocks
    login_block_start = content.find('<!-- Login View -->')
    register_block_start = content.find('<!-- Register view -->')
    
    # Login email
    login_email_tag = '<input type="email" placeholder="E-mailová adresa" required>'
    new_login_email_tag = '<input type="email" id="login-email" placeholder="E-mailová adresa" required>'
    if new_login_email_tag not in content:
        content = content[:register_block_start].replace(login_email_tag, new_login_email_tag) + content[register_block_start:]
    
    # Register name & email
    reg_name_tag = '<input type="text" placeholder="Celé meno" required>'
    new_reg_name_tag = '<input type="text" id="reg-name" placeholder="Celé meno" required>'
    content = content.replace(reg_name_tag, new_reg_name_tag)
    
    reg_email_tag = '<input type="email" placeholder="E-mailová adresa" required>'
    new_reg_email_tag = '<input type="email" id="reg-email" placeholder="E-mailová adresa" required>'
    content = content.replace(reg_email_tag, new_reg_email_tag)

    # 4. Replace <form onsubmit="..."> and add Turnstile
    login_form = '<form onsubmit="event.preventDefault();">'
    if login_form in content:
        content = content.replace(login_form, '<form id="loginForm" onsubmit="submitLogin(event);">', 1)
        content = content.replace(login_form, '<form id="registerForm" onsubmit="submitRegister(event);">', 1)
    
    login_btn = '<button type="submit" class="auth-submit" onclick="switchAuthView(\'role\')">Prihlásiť sa</button>'
    if login_btn in content:
        new_login_btn = """<div class="cf-turnstile" id="cf-turnstile-login" data-sitekey="<?php echo TURNSTILE_SITE_KEY; ?>" data-theme="auto" style="margin-bottom:15px; display:flex; justify-content:center;"></div>
                    <div id="login-error" style="color: #ff6b6b; margin-bottom: 15px; font-size: 14px; display: none;"></div>
                    <button type="submit" class="auth-submit" id="btn-login">Prihlásiť sa</button>"""
        content = content.replace(login_btn, new_login_btn)

    reg_btn = '<button type="submit" class="auth-submit" onclick="switchAuthView(\'role\')">Zaregistrovať sa</button>'
    if reg_btn in content:
        new_reg_btn = """<div class="cf-turnstile" id="cf-turnstile-register" data-sitekey="<?php echo TURNSTILE_SITE_KEY; ?>" data-theme="auto" style="margin-bottom:15px; display:flex; justify-content:center;"></div>
                    <div id="register-error" style="color: #ff6b6b; margin-bottom: 15px; font-size: 14px; display: none;"></div>
                    <button type="submit" class="auth-submit" id="btn-register">Zaregistrovať sa</button>"""
        content = content.replace(reg_btn, new_reg_btn)
        
    # 5. Add JS functions for AJAX
    js_code = """
    async function submitLogin(e) {
        e.preventDefault();
        const btn = document.getElementById('btn-login');
        const errDiv = document.getElementById('login-error');
        errDiv.style.display = 'none';
        btn.innerHTML = 'Moment...';
        btn.disabled = true;

        const email = document.getElementById('login-email').value;
        const pwd = document.getElementById('modal-login-pwd').value;
        const token = document.querySelector('#cf-turnstile-login input[name="cf-turnstile-response"]')?.value || '';

        const fd = new FormData();
        fd.append('action', 'login');
        fd.append('email', email);
        fd.append('password', pwd);
        fd.append('cf-turnstile-response', token);

        try {
            const res = await fetch('api/auth.php', { method: 'POST', body: fd });
            const data = await res.json();
            if (data.success) {
                if (data.require_verification) {
                    switchAuthView('verify');
                } else if (data.redirect) {
                    window.location.href = data.redirect;
                }
            } else {
                errDiv.textContent = data.message;
                errDiv.style.display = 'block';
                if(typeof turnstile !== 'undefined') turnstile.reset('#cf-turnstile-login');
            }
        } catch(e) {
            errDiv.textContent = 'Chyba spojenia.';
            errDiv.style.display = 'block';
        }
        btn.innerHTML = 'Prihlásiť sa';
        btn.disabled = false;
    }

    async function submitRegister(e) {
        e.preventDefault();
        const btn = document.getElementById('btn-register');
        const errDiv = document.getElementById('register-error');
        errDiv.style.display = 'none';
        btn.innerHTML = 'Moment...';
        btn.disabled = true;

        const name = document.getElementById('reg-name').value;
        const email = document.getElementById('reg-email').value;
        const pwd = document.getElementById('modal-reg-pwd').value;
        const token = document.querySelector('#cf-turnstile-register input[name="cf-turnstile-response"]')?.value || '';

        const fd = new FormData();
        fd.append('action', 'register');
        fd.append('name', name);
        fd.append('email', email);
        fd.append('password', pwd);
        fd.append('cf-turnstile-response', token);

        try {
            const res = await fetch('api/auth.php', { method: 'POST', body: fd });
            const data = await res.json();
            if (data.success) {
                if (data.require_verification) {
                    switchAuthView('verify');
                }
            } else {
                errDiv.textContent = data.message;
                errDiv.style.display = 'block';
                if(typeof turnstile !== 'undefined') turnstile.reset('#cf-turnstile-register');
            }
        } catch(e) {
            errDiv.textContent = 'Chyba spojenia.';
            errDiv.style.display = 'block';
        }
        btn.innerHTML = 'Zaregistrovať sa';
        btn.disabled = false;
    }

    async function submitVerify(e) {
        e.preventDefault();
        const btn = document.getElementById('btn-verify');
        const errDiv = document.getElementById('verify-error');
        errDiv.style.display = 'none';
        btn.innerHTML = 'Moment...';
        btn.disabled = true;

        const code = document.getElementById('verify-code').value;

        const fd = new FormData();
        fd.append('action', 'verify_code');
        fd.append('code', code);

        try {
            const res = await fetch('api/auth.php', { method: 'POST', body: fd });
            const data = await res.json();
            if (data.success && data.require_role) {
                switchAuthView('role');
            } else {
                errDiv.textContent = data.message;
                errDiv.style.display = 'block';
            }
        } catch(e) {
            errDiv.textContent = 'Chyba spojenia.';
            errDiv.style.display = 'block';
        }
        btn.innerHTML = 'Overiť kód';
        btn.disabled = false;
    }

    async function selectRole(role) {
        const fd = new FormData();
        fd.append('action', 'set_role');
        fd.append('role', role);
        try {
            const res = await fetch('api/auth.php', { method: 'POST', body: fd });
            const data = await res.json();
            if (data.success && data.redirect) {
                window.location.href = data.redirect;
            }
        } catch(e) {
            alert('Chyba pri ukladaní roly.');
        }
    }
"""

    old_role = """function selectRole(role) {
        alert("Rola vybraná: " + role + "\\n(V ostrej verzii toto uloží rolu do databázy)");
        closeAuthModal();
    }"""
    if old_role in content:
        content = content.replace(old_role, js_code)
        
    # Update switchAuthView
    old_switch = """const registerView = document.getElementById('modal-register-view');
        const roleView = document.getElementById('modal-role-view');
        
        loginView.style.display = 'none';
        registerView.style.display = 'none';
        if (roleView) roleView.style.display = 'none';
        
        if (view === 'login') {
            loginView.style.display = 'flex';
        } else if (view === 'register') {
            registerView.style.display = 'flex';
        } else if (view === 'role') {
            if(roleView) roleView.style.display = 'flex';
        }"""
        
    new_switch = """const registerView = document.getElementById('modal-register-view');
        const roleView = document.getElementById('modal-role-view');
        const verifyView = document.getElementById('modal-verify-view');
        
        loginView.style.display = 'none';
        registerView.style.display = 'none';
        if (roleView) roleView.style.display = 'none';
        if (verifyView) verifyView.style.display = 'none';
        
        if (view === 'login') {
            loginView.style.display = 'flex';
        } else if (view === 'register') {
            registerView.style.display = 'flex';
        } else if (view === 'role') {
            if(roleView) roleView.style.display = 'flex';
        } else if (view === 'verify') {
            if(verifyView) verifyView.style.display = 'flex';
        }"""
        
    if old_switch in content:
        content = content.replace(old_switch, new_switch)

    with open(filename, 'w', encoding='utf-8') as f:
        f.write(content)

update_file('c:/VolneKreslo/index.php')
update_file('c:/VolneKreslo/prevadzky.php')
