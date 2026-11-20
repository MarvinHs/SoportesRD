<?php
require __DIR__ . '/../includes/functions.php';
if ($u = staff()) log_activity('Cerró sesión', $u['nombre']);
unset($_SESSION['staff_id']);
redirect('admin/login.php');
