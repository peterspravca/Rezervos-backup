<!-- includes/ai_chat_modal.php -->
<style>
    @keyframes ai-breathing {
        0% { transform: scale(1); box-shadow: 0 4px 15px rgba(176, 128, 66, 0.4); }
        50% { transform: scale(1.05); box-shadow: 0 8px 25px rgba(176, 128, 66, 0.6); }
        100% { transform: scale(1); box-shadow: 0 4px 15px rgba(176, 128, 66, 0.4); }
    }
    @keyframes stars-twinkle {
        0%, 100% { opacity: 1; transform: scale(1); }
        50% { opacity: 0.7; transform: scale(0.9); }
    }
    .ai-fab-pulse { animation: ai-breathing 2.5s ease-in-out infinite; }
    .ai-fab-pulse .material-symbols-outlined { animation: stars-twinkle 3s ease-in-out infinite; display: block; }

    #ai-response-box {
        background-color: var(--bg-color);
        padding: 20px;
        border-radius: 12px;
        border: 1px solid var(--border-color);
        min-height: 200px;
        max-height: 400px;
        overflow-y: auto;
        overflow-x: hidden;
        color: var(--text-secondary);
        text-align: left;
        display: block;
    }
    #ai-response-text {
        line-height: 1.6;
        font-size: 15px;
        overflow-wrap: break-word;
        word-wrap: break-word;
        min-height: 160px;
    }
    #ai-dynamic-response { white-space: pre-wrap; }
    .ai-spinner {
        border: 3px solid var(--border-color);
        border-top: 3px solid var(--primary-color);
        border-radius: 50%;
        width: 24px;
        height: 24px;
        animation: spin 1s linear infinite;
        display: inline-block;
        margin-right: 10px;
        vertical-align: middle;
    }
    @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
</style>

<!-- PLÁVAJÚCE AI TLAČIDLO -->
<div id="ai-fab-container" style="position: fixed; bottom: 20px; right: 20px; display: flex; align-items: center; z-index: 9999; gap: 12px;">
    <span class="ai-fab-title" style="color: var(--text-primary); font-size: 13px; font-weight: 700; opacity: 0; transition: all 0.3s; pointer-events: none; white-space: nowrap; background: var(--card-bg); padding: 6px 12px; border-radius: 10px; border: 1px solid var(--border-color); box-shadow: 0 10px 25px rgba(0,0,0,0.2);">Spýtať sa AI poradcu</span>
    <div id="ai-fab" class="ai-fab-pulse" onclick="document.getElementById('ai-modal').style.display='flex';"
        style="background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-hover) 100%); color: #ffffff; width: 55px; height: 55px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; box-shadow: 0 4px 15px rgba(0,0,0,0.4); transition: transform 0.2s;">
        <span class="material-symbols-outlined notranslate" translate="no" style="font-size: 26px;">auto_awesome</span>
    </div>
</div>

<!-- AI Modal Okno -->
<div id="ai-modal" onclick="if(event.target === this) this.style.display='none';"
    style="display: none; position: fixed; z-index: 10000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.6); align-items: center; justify-content: center; padding: 15px; box-sizing: border-box; backdrop-filter: blur(4px);">
    <div style="background-color: var(--card-bg); padding: 20px; border-radius: 16px; width: 100%; max-width: 600px; border: 1px solid var(--border-color); position: relative; box-shadow: 0 10px 30px rgba(0,0,0,0.15); box-sizing: border-box;">
        <span id="close-modal" class="material-symbols-outlined notranslate" translate="no"
            onclick="document.getElementById('ai-modal').style.display='none';"
            style="position: absolute; top: 15px; right: 15px; z-index: 10001; color: var(--text-secondary); font-size: 28px; cursor: pointer; transition: all 0.3s ease; display: flex; align-items: center; justify-content: center; width: 30px; height: 30px; transform-origin: center;"
            onmouseover="this.style.transform='rotate(90deg)'; this.style.color='var(--primary-color)';"
            onmouseout="this.style.transform='rotate(0)'; this.style.color='var(--text-secondary)';">close</span>

        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 15px;">
            <span class="material-symbols-outlined notranslate" translate="no" style="color: var(--primary-color); font-size: 32px;">auto_awesome</span>
            <h2 style="font-size: 22px; font-weight: 700; margin: 0; color: var(--text-primary);">AI Asistent <?php if (!defined('BRAND_NAME')) require_once __DIR__ . '/branding.php'; echo BRAND_NAME; ?></h2>
        </div>
        <p style="color: var(--text-secondary); margin-bottom: 15px; font-size: 13px;">Hľadáte ten správny salón krásy alebo službu? Opýtajte sa našej umelej inteligencie. Zodpovie vaše otázky a poradí s výberom.</p>

        <!-- Box pre odpoveď -->
        <div id="ai-response-box" style="margin-bottom: 15px; max-height: 45vh; overflow-y: auto; padding-right: 5px; position: relative; box-sizing: border-box;">
            <div id="ai-response-text" style="font-size: 14px; line-height: 1.5; height: 100%;">Tu sa zobrazí odpoveď AI...</div>
        </div>

        <!-- Vstupná zóna -->
        <div class="ai-input-group" id="ai-input-group" style="display: flex; flex-direction: column; gap: 0; box-sizing: border-box;">
            <div style="position: relative; width: 100%; box-sizing: border-box;">
                <input type="text" id="ai-prompt" autocomplete="off" maxlength="500"
                    onkeypress="if(event.key === 'Enter') window.sendAiMessage();" placeholder="Čo hľadáte?"
                    style="width: 100%; padding: 12px 85px 12px 20px; border-radius: 24px; border: 1px solid var(--border-color); background-color: var(--bg-color); color: var(--text-primary); font-size: 15px; outline: none; box-sizing: border-box; box-shadow: 0 2px 6px rgba(0,0,0,0.02); transition: border-color 0.2s;"
                    onfocus="this.style.borderColor='var(--primary-color)'"
                    onblur="this.style.borderColor='var(--border-color)'">

                <div id="ai-voice-btn"
                    style="position: absolute; right: 42px; top: 50%; transform: translateY(-50%); color: var(--text-secondary); cursor: pointer; display: flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 50%; transition: all 0.2s;"
                    title="Nadiktovať hlasom" onmouseover="this.style.color='var(--primary-color)'" onmouseout="this.style.color='var(--text-secondary)'">
                    <span class="material-symbols-outlined notranslate" translate="no" style="font-size: 20px;">mic</span>
                </div>

                <div id="send-ai" onclick="window.sendAiMessage();"
                    style="position: absolute; right: 4px; top: 50%; transform: translateY(-50%); background: var(--primary-color); color: white; cursor: pointer; display: flex; align-items: center; justify-content: center; width: 34px !important; height: 34px !important; min-width: 34px !important; border-radius: 50%; transition: all 0.2s; box-shadow: 0 2px 8px rgba(176,128,66,0.3); border: none; outline: none; z-index: 10; padding: 0 !important; box-sizing: border-box;"
                    title="Opýtať sa" onmouseover="this.style.transform='translateY(-50%) scale(1.05)'" onmouseout="this.style.transform='translateY(-50%) scale(1)'">
                    <span class="material-symbols-outlined notranslate" translate="no" style="font-size: 16px; margin-left: 2px;">send</span>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    window.sendAiMessage = function () {
        const aiPrompt = document.getElementById('ai-prompt');
        const aiResponseBox = document.getElementById('ai-response-box');
        const aiResponseText = document.getElementById('ai-response-text');

        if (!aiPrompt) return;

        const prompt = aiPrompt.value.trim();
        if (!prompt) return;

        aiPrompt.value = '';
        aiPrompt.placeholder = 'Máte nejaké ďalšie otázky?';
        
        aiResponseText.innerHTML = `
            <div style="margin-bottom: 15px; padding-bottom: 12px; border-bottom: 1px dashed var(--border-color); font-size: 14px; line-height: 1.4;">
                <span style="color: var(--primary-color); font-weight: 700; margin-right: 6px;">Vy:</span>
                <span style="color: var(--text-primary);">${prompt}</span>
            </div>
            <div id="ai-dynamic-response"><div class="ai-spinner"></div> Rozmýšľam...</div>
        `;
        aiResponseBox.style.color = 'var(--text-secondary)';

        fetch('api/ai_chat.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ prompt: prompt })
        })
        .then(response => response.json())
        .then(data => {
            const dynamicResp = document.getElementById('ai-dynamic-response');
            if (data.error) {
                if (dynamicResp) dynamicResp.innerHTML = 'Chyba: ' + data.error;
                aiResponseBox.style.color = '#dc322f';
            } else {
                if (dynamicResp) dynamicResp.innerHTML = data.response;
                aiResponseBox.style.color = 'var(--text-primary)';
            }
            aiResponseBox.scrollTop = 0;
        })
        .catch(error => {
            const dynamicResp = document.getElementById('ai-dynamic-response');
            if (dynamicResp) {
                dynamicResp.innerHTML = 'Sieťová chyba: ' + error.message;
            }
            aiResponseBox.style.color = '#dc322f';
        });
    };

    (function () {
        const aiPrompt = document.getElementById('ai-prompt');
        const aiFab = document.getElementById('ai-fab');
        const aiVoiceBtn = document.getElementById('ai-voice-btn');
        if (aiVoiceBtn && ('webkitSpeechRecognition' in window || 'SpeechRecognition' in window)) {
            const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
            const recognition = new SpeechRecognition();
            recognition.lang = 'sk-SK';
            recognition.interimResults = false;
            recognition.maxAlternatives = 1;
            let isAiRecording = false;

            aiVoiceBtn.addEventListener('click', function (e) {
                e.preventDefault();
                if (isAiRecording) {
                    recognition.stop();
                    return;
                }
                try { recognition.start(); } catch (e) { }
            });

            recognition.onstart = function () {
                isAiRecording = true;
                aiVoiceBtn.style.color = '#ff0000';
                aiVoiceBtn.style.transform = 'translateY(-50%) scale(1.2)';
                aiPrompt.placeholder = 'Hovorte...';
            };

            recognition.onresult = function (event) {
                const transcript = event.results[0][0].transcript;
                aiPrompt.value = transcript.replace(/[.,!?]+$/, '');
            };

            recognition.onend = function () {
                isAiRecording = false;
                resetAiMicBtn();
            };
            recognition.onerror = function () {
                isAiRecording = false;
                resetAiMicBtn();
            };

            function resetAiMicBtn() {
                aiVoiceBtn.style.color = 'var(--text-secondary)';
                aiVoiceBtn.style.transform = 'translateY(-50%) scale(1)';
                aiPrompt.placeholder = 'Napr. Aké služby ponúka Barber?';
            }
        } else if (aiVoiceBtn) {
            aiVoiceBtn.style.display = 'none';
        }

        aiFab.addEventListener('mouseenter', function () {
            this.style.transform = 'scale(1.1)';
            const title = document.querySelector('.ai-fab-title');
            if (title) title.style.opacity = '1';
        });
        aiFab.addEventListener('mouseleave', function () {
            this.style.transform = 'scale(1)';
            const title = document.querySelector('.ai-fab-title');
            if (title) title.style.opacity = '0';
        });
    })();
</script>
