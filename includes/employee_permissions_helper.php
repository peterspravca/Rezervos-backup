<?php
// includes/employee_permissions_helper.php
// Zamestnanecké oprávnenia (Fáza 4) — majiteľ prevádzky (bez $_SESSION['is_employee']) má vždy
// všetky práva; zamestnanec má len to, čo mu majiteľ zapol v Tíme (dashboard-tym.php).

// Vráti true, ak aktuálna session smie vidieť/používať danú oblasť.
// $perm: 'revenue' | 'crm' | 'settings'
function employeeCan($perm) {
    if (empty($_SESSION['is_employee'])) return true; // majiteľ
    switch ($perm) {
        case 'revenue':  return !empty($_SESSION['emp_can_view_revenue']);
        case 'crm':      return !empty($_SESSION['emp_can_view_crm']);
        case 'settings': return !empty($_SESSION['emp_can_edit_settings']);
        default:         return false;
    }
}

// Zavolať hneď po overení prihlásenia na stránke, ktorá vyžaduje dané oprávnenie.
// Zamestnanca bez oprávnenia presmeruje na kalendár namiesto zobrazenia obsahu.
function requireEmployeePermission($perm, $redirect = 'dashboard.php') {
    if (!employeeCan($perm)) {
        header('Location: ' . $redirect);
        exit;
    }
}
