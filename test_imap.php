<?php
if (function_exists('imap_open')) {
    echo "IMAP is ENABLED";
} else {
    echo "IMAP is DISABLED";
}
