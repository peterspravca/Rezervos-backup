<?php
// stripe_secrets.example.php — ŠABLÓNA. Skopíruj tento súbor na server ako "stripe_secrets.php"
// (bez ".example") a vyplň reálne kľúče zo Stripe Dashboardu (Developers -> API keys / Webhooks).
//
// "stripe_secrets.php" je v .gitignore — nikdy sa nenahráva do gitu ani neposiela do chatu.
// Ak sa kľúč niekedy dostane von (chat, commit, screenshot), okamžite ho v Stripe Dashboarde zruš (Roll key).

define('STRIPE_SECRET_KEY', '');       // sk_live_... (alebo sk_test_... počas testovania)
define('STRIPE_PUBLISHABLE_KEY', '');  // pk_live_...
define('STRIPE_WEBHOOK_SECRET', '');   // whsec_... — z Dashboard -> Developers -> Webhooks -> tvoj endpoint
