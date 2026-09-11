<div id="sec-settings" class="section">
            <div class="admin-panel" style="max-width: 600px;">
                <h2>Osobné údaje</h2>
                
                <?php if ($is_verified): ?>
                    <div style="background: rgba(245, 176, 65, 0.1); border: 1px solid #f5b041; padding: 15px; border-radius: 8px; margin-bottom: 25px; display: flex; align-items: center; gap: 15px;">
                        <span class="material-symbols-outlined" style="color: #f5b041; font-size: 32px;">verified</span>
                        <div>
                            <h3 style="margin: 0; color: #f5b041;">Ste overený zákazník!</h3>
                            <p style="margin: 5px 0 0 0; font-size: 14px; color: var(--text-secondary);">Vďaka overeniu môžete robiť rezervácie bez nutnosti platiť zálohy.</p>
                        </div>
                    </div>
                <?php else: ?>
                    <div style="background: var(--input-bg); border: 1px solid var(--border-color); padding: 15px; border-radius: 8px; margin-bottom: 25px; display: flex; align-items: center; gap: 15px; justify-content: space-between; flex-wrap: wrap;">
                        <div style="display: flex; align-items: center; gap: 15px;">
                            <span class="material-symbols-outlined" style="color: var(--text-secondary); font-size: 32px;">gpp_maybe</span>
                            <div>
                                <h3 style="margin: 0; color: var(--text-primary);">Staňte sa overeným zákazníkom</h3>
                                <p style="margin: 5px 0 0 0; font-size: 14px; color: var(--text-secondary);">Získajte exkluzívny odznak a osloboďte sa od platenia záloh pri všetkých salónoch.</p>
                            </div>
                        </div>
                        <button type="button" class="btn btn-primary" onclick="payVerificationFee()" style="white-space: nowrap;">
                            <span class="material-symbols-outlined">payments</span> Overiť sa (4,90 &euro;)
                        </button>
                    </div>
                <?php endif; ?>
<form id="profile-form" onsubmit="event.preventDefault(); saveProfile();">
                    
                    <div class="form-group" style="text-align: center; margin-bottom: 25px;">
                        <div style="width: 100px; height: 100px; border-radius: 50%; background: var(--border-color); margin: 0 auto 15px auto; overflow: hidden; position: relative; display: flex; align-items: center; justify-content: center;" id="avatar-preview-container">
                            <?php if (!empty($res['avatar_url'])): ?>
                                <img id="avatar-preview" src="<?= htmlspecialchars($res['avatar_url']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                            <?php else: ?>
                                <span id="avatar-placeholder" class="material-symbols-outlined" style="font-size: 60px; color: var(--text-secondary);">person</span>
                                <img id="avatar-preview" src="" style="width: 100%; height: 100%; object-fit: cover; display: none;">
                            <?php endif; ?>
                            <div style="position: absolute; bottom: 0; left: 0; width: 100%; background: rgba(0,0,0,0.5); padding: 5px 0; cursor: pointer; transition: 0.3s;" onclick="document.getElementById('profile-avatar').click();" onmouseover="this.style.background='rgba(0,0,0,0.7)'" onmouseout="this.style.background='rgba(0,0,0,0.5)'">
                                <span class="material-symbols-outlined" style="color: white; font-size: 18px;">photo_camera</span>
                            </div>
                        </div>
                        <input type="file" id="profile-avatar" accept="image/*" style="display:none;" onchange="previewAvatar(this)">
                    </div>

                    <div class="form-group">
                        <label>Celé meno</label>
                        <input type="text" class="form-control" id="profile-name" value="<?= $user_name ?>">
                    </div>
                    <div class="form-group">
                        <label>E-mailová adresa (nedá sa zmeniť)</label>
                        <input type="email" class="form-control" value="<?= $user_email ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label>Telefónne číslo</label>
                        <input type="text" class="form-control" id="profile-phone" value="<?= $user_phone ?>" placeholder="+421 9xx xxx xxx">
                    </div>
                    <div class="form-group">
                        <label>WhatsApp číslo</label>
                        <input type="text" class="form-control" id="profile-whatsapp" value="<?= $user_whatsapp ?>" placeholder="+421 9xx xxx xxx">
                    </div>

                    <button type="submit" class="btn">Uložiť zmeny</button>
                    <p id="profile-msg" style="margin-top: 10px;"></p>
                </form>
            </div>
        </div>