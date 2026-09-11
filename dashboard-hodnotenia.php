<?php
require_once 'config.php';
if (!defined('BRAND_NAME')) require_once __DIR__ . '/includes/branding.php';
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'business') {
    header('Location: index.php'); exit;
}
require_once 'includes/employee_permissions_helper.php';
requireEmployeePermission('crm');
$pageTitle = 'Hodnotenia a Recenzie - ' . BRAND_NAME;
$currentPage = 'hodnotenia';
require_once 'includes/dashboard-head.php';
?>
<div class="admin-sidebar">
<?php require_once 'includes/sidebar.php'; ?>
</div>
<div class="admin-main">
  <?php $headerTitle = 'Hodnotenia a Recenzie'; $headerIcon = 'star'; require_once 'includes/dashboard-topbar.php'; ?>
  <div class="admin-content">
    <div class="section">

    <!-- SEKCIA 1: HODNOTENIA PREVÁDZKY OD ZÁKAZNÍKOV -->
    <div class="vueto-card" style="margin-bottom:20px;">
      <div style="padding:20px;">

        <!-- HEADER SO ŠTATISTIKAMI -->
        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:15px;margin-bottom:20px;">
          <div>
            <h2 class="section-header" style="margin:0 0 6px 0;font-size:20px;display:flex;align-items:center;gap:8px;">
              <span class="material-symbols-outlined" style="color:var(--primary-color);">storefront</span> Hodnotenia prevádzky
            </h2>
            <p class="section-desc" style="margin:0;">Recenzie od zákazníkov a možnosť odpovedať na každú recenziu.</p>
          </div>
          <div id="reviews-stats-box" style="background:var(--bg-color);border:1px solid var(--border-color);border-radius:14px;padding:14px 20px;text-align:center;min-width:160px;">
            <div id="avg-rating-stars" style="font-size:22px;color:#f59e0b;letter-spacing:2px;">☆☆☆☆☆</div>
            <div id="avg-rating-num" style="font-size:28px;font-weight:900;color:var(--text-primary);line-height:1;">–</div>
            <div id="avg-rating-count" style="font-size:12px;color:var(--text-secondary);margin-top:2px;">Celkovo 0 hodnotení</div>
          </div>
        </div>

        <!-- ZOZNAM RECENZIÍ -->
        <div id="business-reviews-list" style="display:flex;flex-direction:column;gap:16px;">
          <div style="text-align:center;padding:30px;color:var(--text-secondary);">Načítavam recenzie...</div>
        </div>

      </div>
    </div>

    <!-- SEKCIA 2: HODNOTENIA ZÁKAZNÍKOV OD NÁS -->
    <div class="vueto-card">
      <div style="padding:20px;">
        <h2 class="section-header" style="margin:0 0 6px 0;font-size:20px;display:flex;align-items:center;gap:8px;">
          <span class="material-symbols-outlined" style="color:var(--primary-color);">person_check</span> Hodnotenia zákazníkov (od vás)
        </h2>
        <p class="section-desc" style="margin:0 0 20px 0;">Vaše hodnotenia zákazníkov po návšteve.</p>
        <div id="customer-ratings-list" style="display:flex;flex-direction:column;gap:12px;">
          <div style="text-align:center;padding:20px;color:var(--text-secondary);">Načítavam...</div>
        </div>
      </div>
    </div>

    </div><!-- /.section -->
  </div>
</div>

<style>
.review-card { background:var(--card-bg);border:1px solid var(--border-color);border-radius:14px;padding:20px; }
.star-display { color:#f59e0b;font-size:16px;letter-spacing:1px; }
.reply-box { background:var(--bg-color);border:1px solid var(--border-color);border-radius:10px;padding:14px;margin-top:14px;border-left:3px solid var(--primary-color); }
</style>

<script>
function starsHTML(rating) {
  const r = parseInt(rating)||0;
  let s='';
  for (let i=1;i<=5;i++) s+=(i<=r?'★':'☆');
  return s;
}

async function loadBusinessReviews() {
  const container = document.getElementById('business-reviews-list');
  try {
    const fd=new FormData(); fd.append('action','get_business_reviews');
    const res = await fetch('api/reviews.php',{method:'POST',body:fd});
    const data = await res.json();
    if (data.success) {
      // Stats
      const stats = data.stats||{};
      const avg = parseFloat(stats.avg_rating||0);
      const total = parseInt(stats.total||0);
      document.getElementById('avg-rating-stars').innerText = starsHTML(Math.round(avg));
      document.getElementById('avg-rating-num').innerText = avg ? avg.toFixed(1) : '–';
      document.getElementById('avg-rating-count').innerText = `Celkovo ${total} ${total===1?'hodnotenie':(total>=2&&total<=4?'hodnotenia':'hodnotení')}`;
      // Reviews
      const reviews = data.reviews||[];
      if (!reviews.length) {
        container.innerHTML = '<div style="text-align:center;padding:40px;color:var(--text-secondary);"><span class="material-symbols-outlined" style="font-size:48px;margin-bottom:12px;display:block;">rate_review</span>Zatiaľ žiadne recenzie od zákazníkov.</div>';
        return;
      }
      container.innerHTML = reviews.map(r => {
        const initials = (r.reviewer_name||'?').charAt(0).toUpperCase();
        const dateStr = new Date(r.created_at).toLocaleDateString('sk-SK');
        const hasReply = r.reply_text ? true : false;
        return `<div class="review-card">
          <div style="display:flex;align-items:flex-start;gap:14px;">
            <div style="width:44px;height:44px;border-radius:50%;background:var(--primary-color);color:#fff;display:flex;align-items:center;justify-content:center;font-size:18px;font-weight:800;flex-shrink:0;">${initials}</div>
            <div style="flex:1;">
              <div style="display:flex;justify-content:space-between;align-items:baseline;flex-wrap:wrap;gap:8px;">
                <strong style="font-size:14.5px;color:var(--text-primary);">${escHtml(r.reviewer_name||'Anonymný zákazník')}</strong>
                <span style="font-size:12px;color:var(--text-secondary);">${dateStr}</span>
              </div>
              <div class="star-display" style="margin:4px 0;">${starsHTML(r.rating)}</div>
              ${r.comment?`<p style="margin:8px 0 0 0;font-size:13.5px;color:var(--text-primary);line-height:1.5;">${escHtml(r.comment)}</p>`:''}
              ${hasReply
                ? `<div class="reply-box"><div style="font-size:12px;font-weight:700;color:var(--primary-color);margin-bottom:6px;display:flex;align-items:center;gap:6px;"><span class="material-symbols-outlined" style="font-size:15px;">storefront</span> Vaša odpoveď:</div><p style="margin:0;font-size:13px;color:var(--text-primary);">${escHtml(r.reply_text)}</p></div>`
                : `<div style="margin-top:14px;">
                    <textarea id="reply-${r.id}" rows="2" placeholder="Napíšte odpoveď na túto recenziu..." style="width:100%;padding:10px 14px;border:1px solid var(--border-color);border-radius:10px;background:var(--bg-color);color:var(--text-primary);font-size:13px;resize:vertical;box-sizing:border-box;"></textarea>
                    <button type="button" onclick="replyToReview(${r.id})" class="btn-primary" style="margin-top:8px;padding:8px 18px;font-size:13px;font-weight:700;border-radius:10px;display:inline-flex;align-items:center;gap:6px;">
                      <span class="material-symbols-outlined" style="font-size:16px;">reply</span> Odpovedať
                    </button>
                  </div>`
              }
            </div>
          </div>
        </div>`;
      }).join('');
    } else {
      container.innerHTML = '<div style="padding:20px;color:#ef4444;">Chyba načítania recenzií.</div>';
    }
  } catch(e) { container.innerHTML = '<div style="padding:20px;color:#ef4444;">Chyba pripojenia.</div>'; }
}

async function replyToReview(reviewId) {
  const text = document.getElementById('reply-'+reviewId)?.value.trim();
  if (!text) { showToast('Napíšte text odpovede.','warning'); return; }
  const fd=new FormData(); fd.append('action','reply_to_review'); fd.append('review_id',reviewId); fd.append('reply_text',text);
  try {
    const res = await fetch('api/reviews.php',{method:'POST',body:fd});
    const data = await res.json();
    if (data.success) { showToast('Odpoveď bola uložená!'); loadBusinessReviews(); }
    else showToast(data.error||'Chyba pri ukladaní odpovede.','error');
  } catch(e) { showToast('Chyba pripojenia.','error'); }
}

async function loadMyCustomerRatings() {
  const container = document.getElementById('customer-ratings-list');
  try {
    const fd=new FormData(); fd.append('action','get_my_customer_ratings');
    const res = await fetch('api/reviews.php',{method:'POST',body:fd});
    const data = await res.json();
    if (data.success) {
      const ratings = data.ratings||[];
      if (!ratings.length) {
        container.innerHTML = '<div style="text-align:center;padding:30px;color:var(--text-secondary);">Zatiaľ ste neohodnotili žiadneho zákazníka.</div>';
        return;
      }
      container.innerHTML = ratings.map(r => `
        <div style="background:var(--bg-color);border:1px solid var(--border-color);border-radius:12px;padding:14px 18px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;">
          <div>
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:4px;">
              <strong style="font-size:14px;color:var(--text-primary);">${escHtml(r.customer_name||'Zákazník #'+r.reviewee_id)}</strong>
              <span class="star-display" style="font-size:14px;">${starsHTML(r.rating)}</span>
            </div>
            ${r.comment?`<p style="margin:0;font-size:12.5px;color:var(--text-secondary);">${escHtml(r.comment)}</p>`:''}
            <div style="font-size:12px;color:var(--text-secondary);margin-top:4px;">${new Date(r.created_at).toLocaleDateString('sk-SK')}</div>
          </div>
          <button type="button" onclick="openEditRating(${r.id},${r.reviewee_id},${r.rating},'${encodeURIComponent(r.comment||'')}')" class="btn-secondary" style="padding:6px 12px;font-size:12px;border-radius:8px;display:flex;align-items:center;gap:4px;">
            <span class="material-symbols-outlined" style="font-size:15px;">edit</span> Upraviť
          </button>
        </div>
      `).join('');
    } else {
      container.innerHTML = '<div style="padding:20px;color:#ef4444;">Chyba načítania.</div>';
    }
  } catch(e) { container.innerHTML = '<div style="padding:20px;color:#ef4444;">Chyba pripojenia.</div>'; }
}

function openEditRating(id, customerId, rating, commentEnc) {
  const comment = decodeURIComponent(commentEnc);
  const stars = [1,2,3,4,5].map(i=>`<label style="cursor:pointer;font-size:28px;color:${i<=rating?'#f59e0b':'var(--text-secondary)'}" onclick="this.parentNode.querySelectorAll('label').forEach((l,idx)=>l.style.color=idx<${i}?'#f59e0b':'var(--text-secondary)');document.getElementById('edit-rating-val').value=${i};">★</label>`).join('');
  const html = `
    <div id="edit-rating-modal" style="position:fixed;inset:0;background:rgba(0,0,0,0.65);z-index:3000;display:flex;align-items:center;justify-content:center;padding:20px;">
      <div style="background:var(--card-bg);border:1px solid var(--border-color);border-radius:16px;max-width:440px;width:100%;padding:28px;box-shadow:0 20px 40px rgba(0,0,0,0.3);">
        <h3 style="margin:0 0 20px 0;font-size:18px;font-weight:800;">Upraviť hodnotenie zákazníka</h3>
        <div style="margin-bottom:14px;"><label style="display:block;font-size:13px;font-weight:600;margin-bottom:8px;">Hodnotenie</label>
          <div style="display:flex;gap:4px;">${stars}</div>
          <input type="hidden" id="edit-rating-val" value="${rating}">
        </div>
        <div style="margin-bottom:18px;"><label style="display:block;font-size:13px;font-weight:600;margin-bottom:6px;">Komentár</label>
          <textarea id="edit-rating-comment" rows="3" style="width:100%;padding:10px 14px;border:1px solid var(--border-color);border-radius:10px;background:var(--bg-color);color:var(--text-primary);font-size:14px;resize:vertical;box-sizing:border-box;">${escHtml(comment)}</textarea>
        </div>
        <div style="display:flex;gap:10px;">
          <button type="button" onclick="document.getElementById('edit-rating-modal').remove()" class="btn-secondary" style="flex:1;padding:12px;border-radius:10px;">Zrušiť</button>
          <button type="button" onclick="saveRating(${customerId})" class="btn-primary" style="flex:1;padding:12px;border-radius:10px;font-weight:700;">Uložiť</button>
        </div>
      </div>
    </div>`;
  document.body.insertAdjacentHTML('beforeend',html);
}

async function saveRating(customerId) {
  const rating = document.getElementById('edit-rating-val').value;
  const comment = document.getElementById('edit-rating-comment').value;
  const fd=new FormData(); fd.append('action','rate_customer'); fd.append('customer_id',customerId); fd.append('rating',rating); fd.append('comment',comment);
  try {
    const res=await fetch('api/reviews.php',{method:'POST',body:fd}); const data=await res.json();
    if (data.success) { showToast('Hodnotenie bolo uložené!'); document.getElementById('edit-rating-modal')?.remove(); loadMyCustomerRatings(); }
    else showToast(data.error||'Chyba.','error');
  } catch(e) { showToast('Chyba pripojenia.','error'); }
}

function escHtml(str) { return String(str||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

function showToast(msg,type='success') {
  if (typeof showAppToast==='function'){showAppToast(msg,type);return;}
  let c=document.getElementById('_tc');if(!c){c=document.createElement('div');c.id='_tc';c.style.cssText='position:fixed;top:24px;right:24px;z-index:99999;display:flex;flex-direction:column;gap:10px;pointer-events:none;';document.body.appendChild(c);}
  const t=document.createElement('div');t.style.cssText=`background:${type==='error'?'#ef4444':type==='warning'?'#f59e0b':'#10b981'};color:#fff;padding:12px 20px;border-radius:12px;font-size:13.5px;font-weight:600;opacity:0;transform:translateY(-15px);transition:all 0.3s;`;
  t.innerText=msg;c.appendChild(t);setTimeout(()=>{t.style.opacity='1';t.style.transform='translateY(0)';},10);setTimeout(()=>{t.style.opacity='0';t.style.transform='translateY(-15px)';setTimeout(()=>t.remove(),300);},3500);
}

document.addEventListener('DOMContentLoaded', () => {
  const isDark = document.body.classList.contains('dark-mode');
  const _ti = document.getElementById('theme-icon'); if (_ti) _ti.textContent = isDark ? 'dark_mode' : 'light_mode';
  init();
});
function init() {
  loadBusinessReviews();
  loadMyCustomerRatings();
}
</script>
</body>
</html>
