<?php
$role_permissions = [
    'admin' => [
        'users' => ['create', 'read', 'update', 'delete'],
        'projects' => ['create', 'read', 'update', 'delete'],
        'tasks' => ['create', 'read', 'update', 'delete'],
        'reports' => ['create', 'read', 'update', 'delete'],
        'settings' => ['create', 'read', 'update', 'delete'],
        'designs' => ['create', 'read', 'update', 'delete'],
        'inventory' => ['create', 'read', 'update', 'delete'],
        'schedule' => ['create', 'read', 'update', 'delete'],
        'attendance' => ['create', 'read', 'update', 'delete']
    ],
    'manager' => [
        'users' => ['read', 'update'],
        'projects' => ['create', 'read', 'update'],
        'tasks' => ['create', 'read', 'update', 'delete'],
        'reports' => ['create', 'read', 'update'],
        'settings' => ['read']
    ],
    'designer' => [
        'projects' => ['read', 'update'],
        'tasks' => ['read', 'update'],
        'reports' => ['read'],
        'designs' => ['create', 'read', 'update']
    ],
    'site_manager' => [
        'projects' => ['read', 'update'],
        'tasks' => ['create', 'read', 'update'],
        'reports' => ['create', 'read'],
        'inventory' => ['read', 'update']
    ],
    'site_coordinator' => [
        'projects' => ['read'],
        'tasks' => ['read', 'update'],
        'reports' => ['read'],
        'schedule' => ['read', 'update']
    ],
    'site_supervisor' => [
        'tasks' => ['read', 'update'],
        'reports' => ['create', 'read'],
        'attendance' => ['create', 'read']
    ]
];

function hasPermission($role, $module, $action) {
    global $role_permissions;
    return isset($role_permissions[$role][$module]) && 
           in_array($action, $role_permissions[$role][$module]);
}
?>