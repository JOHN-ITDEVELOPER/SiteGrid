<?php
/**
 * Laravel Application Entry Point Redirect
 * 
 * This file redirects all root requests to the public directory.
 * For production, configure your web server to point directly to the public folder.
 */

// Redirect to public folder
header('Location: /mjengo/public/');
exit;
