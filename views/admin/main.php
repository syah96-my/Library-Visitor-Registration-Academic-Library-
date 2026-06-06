<?php

header('Content-Type: text/html');
include_once '../../config/config.php';
include_once '../../sessions/session.php';
include_once '../../controllers/FormFieldController.php';

if (!isLoggedIn()) {
    header('Location: ' . $base_url . '/views/admin/log-masuk.php');
    exit();
}

// Function to read locations from DB
function readLocations() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT * FROM locations");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

function readUsers() {
    global $pdo;  // Use your existing PDO connection

    try {
        $stmt = $pdo->query("SELECT account_id, username, role FROM accounts ORDER BY username ASC");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $users;
    } catch (PDOException $e) {
        // Handle error - in production, better logging
        error_log("Database error in readUsers(): " . $e->getMessage());
        return [];
    }
}

function readAdminFormFields() {
    return getFormFields(false);
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Admin Interface - Dark Theme</title>
    <!-- Bulma CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@0.9.4/css/bulma.min.css" />
    <link rel="stylesheet" href="../../assets/css/main-visitor.css" />
    <link rel="stylesheet" href="../../assets/css/icon.css" />
    <!-- Iconify Icons -->
    <script src="https://code.iconify.design/2/2.1.2/iconify.min.js"></script>
    <style>
        body {
            background-color: #121212;
            color: #e0e0e0;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            padding: 20px;
            margin: 0;
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .container {
            width: 400px;
            background-color: #1e1e1e;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.8);
            margin-top: 20px;
            color: #e0e0e0;
        }
        .tab-content {
            margin-top: 20px;
            padding: 15px;
            background: #2c2c2c;
            border-radius: 5px;
            color: #e0e0e0;
        }
        .is-hidden {
            display: none;
        }
        .tabs a {
            cursor: pointer;
            color: #ccc;
        }
        .tabs .is-active a {
            color: #3273dc;
            font-weight: bold;
            border-bottom: 2px solid #3273dc;
        }
        input.input,
        textarea.textarea {
            background-color: #333;
            color: #e0e0e0;
            border: 1px solid #555;
        }
        input.input::placeholder,
        textarea.textarea::placeholder {
            color: #999;
        }
        input.input:focus,
        textarea.textarea:focus {
            background-color: #444;
            border-color: #3273dc;
            color: #fff;
            outline: none;
        }
        button.button {
            background-color: #3273dc;
            color: white;
            border: none;
        }
        button.button.is-danger {
            background-color: #cc3333;
        }
        button.button.is-info {
            background-color: #209cee;
        }
        button.button.is-success {
            background-color: #23d160;
        }
        button.button:hover {
            filter: brightness(1.1);
        }
        table {
            color: #e0e0e0;
            border-color: #555;
        }
        table thead {
            background-color: #444;
            color: #ccc;
        }
        table tbody tr:hover {
            background-color: #555;
        }
        table td,
        table th {
            border: 1px solid #555;
            padding: 8px 10px;
        }
        td span {
            font-size: 1.5rem;
        }
        table .button {
            margin-right: 4px;
        }
        .input,
        .select {
            max-width: 300px;
        }
        .stat-head {
            background-color: #00ffff !important;
        }
        .stat-head th {
            text-align: center !important;
            vertical-align: middle !important;
            padding: 10px;
        }
        .admin-card,
        .table-card {
            background: transparent;
            border: 0;
            box-shadow: none;
            padding: 0;
            overflow-x: visible;
        }
        .management-grid,
        .stats-grid {
            display: block;
        }
        .field-row {
            display: block;
        }
        .logout-link {
            display: inline-block;
            margin-top: 18px;
            padding: 8px 16px;
            background-color: #f44336;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-weight: bold;
            cursor: pointer;
        }
        @media (max-width: 720px) {
          .container {
            width: 100%;
          }
        }
    </style>
</head>
<body>
<div class="container has-text-centered">
    <nav class="tabs is-boxed is-centered">
        <ul>
            <li id="statisticTab" class="is-active"><a onclick="showTab('Statistic')">Statistic</a></li>
            <li id="locationTab"><a onclick="showTab('Location')">Location</a></li>
            <li id="fieldsTab"><a onclick="showTab('Fields')">Fields</a></li>
            <li id="profileTab"><a onclick="showTab('Profile')">Profile</a></li>
        </ul>
    </nav>

    <div id="Statistic" class="tab-content">
        <h2 class="title">Statistics</h2>
        <div class="field">
            <label class="label">From Date</label>
            <input id="startDate" class="input" type="date" value="<?php echo date('Y-m-01'); ?>">
        </div>
        <div class="field">
            <label class="label">To Date</label>
            <input id="endDate" class="input" type="date" value="<?php echo date('Y-m-d'); ?>">
        </div>
        <div class="field">
            <div class="control">
                <button onclick="openReport()" class="button is-success">Open Report</button>
            </div>
        </div>
    </div>

    <div id="Fields" class="tab-content is-hidden">
        <h2 class="title">Visitor Fields</h2>
        <div class="management-grid">
            <section class="admin-card">
                <input type="hidden" id="editingFieldId" value="">
                <div class="field">
                    <label class="label">Label</label>
                    <input id="fieldLabel" class="input" type="text" placeholder="Field Label">
                </div>
                <div class="field">
                    <label class="label">Type</label>
                    <div class="select is-fullwidth">
                        <select id="fieldType">
                            <option value="text">Text</option>
                            <option value="select">Select</option>
                        </select>
                    </div>
                </div>
                <div class="field">
                    <label class="label">Sort Order</label>
                    <input id="fieldSortOrder" class="input" type="number" value="100" placeholder="Sort Order">
                </div>
                <div class="field">
                    <label class="label">Options</label>
                    <textarea id="fieldOptions" class="textarea" rows="4" placeholder="Options, one per line. Only used for Select fields."></textarea>
                </div>
                <div class="field-actions">
                    <label class="checkbox"><input id="fieldRequired" type="checkbox" checked> Required</label>
                    <label class="checkbox"><input id="fieldActive" type="checkbox" checked> Active</label>
                    <button id="submitFieldBtn" onclick="submitField()" class="button is-success">Add Field</button>
                    <button id="cancelFieldEditBtn" onclick="cancelFieldEdit()" class="button is-warning is-hidden">Cancel Edit</button>
                </div>
            </section>

        <section class="admin-card table-card">
            <table class="table is-fullwidth is-striped is-hoverable">
            <thead class="stat-head">
                <tr>
                    <th>Label</th>
                    <th>Type</th>
                    <th>Required</th>
                    <th>Active</th>
                    <th>Order</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="fieldsTableBody">
                <?php foreach (readAdminFormFields() as $field): ?>
                <tr data-field-id="<?php echo $field['field_id']; ?>">
                    <td><?php echo htmlspecialchars($field['label']); ?></td>
                    <td><?php echo htmlspecialchars($field['field_type']); ?></td>
                    <td><?php echo (int) $field['required'] === 1 ? 'Yes' : 'No'; ?></td>
                    <td><?php echo (int) $field['active'] === 1 ? 'Yes' : 'No'; ?></td>
                    <td><?php echo (int) $field['sort_order']; ?></td>
                    <td>
                        <div class="table-actions">
                            <button class="button is-small is-info" onclick="editField(<?php echo (int) $field['field_id']; ?>, <?php echo htmlspecialchars(json_encode($field['label']), ENT_QUOTES); ?>, <?php echo htmlspecialchars(json_encode($field['field_type']), ENT_QUOTES); ?>, <?php echo htmlspecialchars(json_encode($field['options'] ?? ''), ENT_QUOTES); ?>, <?php echo (int) $field['required']; ?>, <?php echo (int) $field['active']; ?>, <?php echo (int) $field['sort_order']; ?>)">Edit</button>
                            <?php if ((int) $field['is_system'] === 0): ?>
                                <button class="button is-small is-danger" onclick="deleteField(<?php echo (int) $field['field_id']; ?>)">Delete</button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            </table>
        </section>
        </div>
    </div>

    <div id="Location" class="tab-content is-hidden">
        <h2 class="title">Locations</h2>
        <div class="management-grid">
            <section class="admin-card">
                <input type="hidden" id="editingLocationId" value="">
                <div class="field">
                    <label class="label">Location Name</label>
                    <input id="newLocationName" class="input" type="text" placeholder="Location Name">
                </div>
                <div class="field">
                    <label class="label">Color</label>
                    <input id="newLocationColor" class="input" type="color" value="#000000">
                </div>
                <div class="field">
                    <label class="label">Description</label>
                    <textarea id="newLocationDescription" class="textarea" rows="4" placeholder="Location Description"></textarea>
                </div>
                <div class="field-actions">
                    <button id="submitLocationBtn" onclick="submitLocation()" class="button is-success">Add Location</button>
                    <button id="cancelLocationEditBtn" onclick="cancelEdit()" class="button is-warning is-hidden">Cancel Edit</button>
                </div>
            </section>

        <section class="admin-card table-card">
            <table class="table is-fullwidth is-striped is-hoverable">
            <thead class="stat-head">
                <tr>
                    <th>Name</th>
                    <th>Description</th>
                    <th>Color</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (readLocations() as $location): ?>
                <tr>
                    <td><?php echo htmlspecialchars($location['name']); ?></td>
                    <td><?php echo htmlspecialchars($location['description']); ?></td>
                    <td>
                        <span style="color: <?php echo htmlspecialchars($location['color']); ?>;">■</span>
                    </td>
                    <td>
                        <div class="table-actions">
                            <button class="button is-small is-info" onclick="editLocation(<?php echo (int) $location['location_id']; ?>, <?php echo htmlspecialchars(json_encode($location['name']), ENT_QUOTES); ?>, <?php echo htmlspecialchars(json_encode($location['description']), ENT_QUOTES); ?>, <?php echo htmlspecialchars(json_encode($location['color']), ENT_QUOTES); ?>)">Edit</button>
                            <button class="button is-small is-danger" onclick="deleteLocation(<?php echo $location['location_id']; ?>)">Delete</button>
                            <button class="button is-small" onclick="viewLocation(<?php echo $location['location_id']; ?>)">View</button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            </table>
        </section>
        </div>
    </div>


    <div id="Profile" class="tab-content is-hidden">
    <h2 class="title">User Management</h2>
    <div class="management-grid">
        <section class="admin-card">
            <input type="hidden" id="editingUserId" value="">
            <div class="field">
                <label class="label">Username</label>
                <input id="newUsername" class="input" type="text" placeholder="Username" />
            </div>
            <div class="field">
                <label class="label">Password</label>
                <input id="newPassword" class="input" type="password" placeholder="Password" />
            </div>
            <div class="field">
                <label class="label">Role</label>
                <div class="select is-fullwidth">
                    <select id="newRole">
                        <option value="admin">Admin</option>
                        <option value="superadmin">Super Admin</option>
                    </select>
                </div>
            </div>
            <div class="field-actions">
                <button id="submitUserBtn" onclick="submitUser()" class="button is-success">Add User</button>
                <button id="cancelUserEditBtn" onclick="cancelEditUser()" class="button is-warning is-hidden">Cancel Edit</button>
            </div>
        </section>

    <section class="admin-card table-card">
        <table class="table is-fullwidth is-striped is-hoverable">
        <thead class="stat-head">
            <tr>
                <th>Username</th>
                <th>Password (hashed)</th>
                <th>Role</th>
                <th>Actions</th>
            </tr>
        </thead>
<tbody id="usersTableBody">
    <?php foreach (readUsers() as $user): ?>
    <tr data-user-id="<?php echo $user['account_id']; ?>">
        <td><?php echo htmlspecialchars($user['username']); ?></td>
        <td>••••••••</td> <!-- never show actual password -->
        <td><?php echo htmlspecialchars($user['role']); ?></td>
        <td>
            <div class="table-actions">
                <button class="button is-small is-info" onclick="editUser(<?php echo (int) $user['account_id']; ?>, <?php echo htmlspecialchars(json_encode($user['username']), ENT_QUOTES); ?>, <?php echo htmlspecialchars(json_encode($user['role']), ENT_QUOTES); ?>)">Edit</button>
                <button class="button is-small is-danger" onclick="deleteUser(<?php echo $user['account_id']; ?>)">Delete</button>
            </div>
        </td>
    </tr>
    <?php endforeach; ?>
</tbody>
        </table>
    </section>
    </div>
<a href="../../logout.php" class="logout-link">Log Out</a>
</div>

<script>
function showTab(tabName) {
    const tabs = ['Statistic', 'Location', 'Fields', 'Profile'];
    tabs.forEach((tab) => {
        document.getElementById(tab).classList.add('is-hidden');
        document.getElementById(tab.toLowerCase() + 'Tab').classList.remove('is-active');
    });
    document.getElementById(tabName).classList.remove('is-hidden');
    document.getElementById(tabName.toLowerCase() + 'Tab').classList.add('is-active');
}

function createFieldRow(field) {
    const tr = document.createElement('tr');
    tr.setAttribute('data-field-id', field.field_id);
    const canDelete = Number(field.is_system) === 0;
    tr.innerHTML = `
        <td>${escapeHtml(String(field.label || ''))}</td>
        <td>${escapeHtml(String(field.field_type || 'text'))}</td>
        <td>${Number(field.required) === 1 ? 'Yes' : 'No'}</td>
        <td>${Number(field.active) === 1 ? 'Yes' : 'No'}</td>
        <td>${Number(field.sort_order || 0)}</td>
        <td>
            <div class="table-actions">
                <button class="button is-small is-info" onclick="editField(${Number(field.field_id)}, '${escapeJs(String(field.label || ''))}', '${escapeJs(String(field.field_type || 'text'))}', '${escapeJs(String(field.options || ''))}', ${Number(field.required)}, ${Number(field.active)}, ${Number(field.sort_order || 0)})">Edit</button>
                ${canDelete ? `<button class="button is-small is-danger" onclick="deleteField(${Number(field.field_id)})">Delete</button>` : ''}
            </div>
        </td>
    `;
    return tr;
}

function updateFieldTable(fields) {
    const tbody = document.getElementById('fieldsTableBody');
    tbody.innerHTML = '';
    fields.forEach(field => tbody.appendChild(createFieldRow(field)));
}

async function submitField() {
    const id = document.getElementById('editingFieldId').value;
    const label = document.getElementById('fieldLabel').value.trim();
    const fieldType = document.getElementById('fieldType').value;
    const options = document.getElementById('fieldOptions').value;
    const sortOrder = document.getElementById('fieldSortOrder').value;
    const required = document.getElementById('fieldRequired').checked ? 1 : 0;
    const active = document.getElementById('fieldActive').checked ? 1 : 0;

    if (!label) {
        alert('Field label is required.');
        return;
    }

    const csrfToken = await getCsrfToken();
    if (!csrfToken) return;

    const payload = {
        action: id ? 'edit' : 'add',
        label,
        field_type: fieldType,
        options,
        sort_order: sortOrder,
        required,
        active,
        csrf_token: csrfToken
    };
    if (id) payload.id = id;

    const response = await fetch('../../controllers/form_field_api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    });
    const result = await response.json();
    alert(result.message || (result.status === 'success' ? 'Saved' : 'Error'));

    if (result.status === 'success') {
        updateFieldTable(result.fields || []);
        cancelFieldEdit();
    }
}

function editField(id, label, fieldType, options, required, active, sortOrder) {
    document.getElementById('editingFieldId').value = id;
    document.getElementById('fieldLabel').value = label;
    document.getElementById('fieldType').value = fieldType;
    document.getElementById('fieldOptions').value = options;
    document.getElementById('fieldSortOrder').value = sortOrder;
    document.getElementById('fieldRequired').checked = Number(required) === 1;
    document.getElementById('fieldActive').checked = Number(active) === 1;
    document.getElementById('submitFieldBtn').textContent = 'Update Field';
    document.getElementById('cancelFieldEditBtn').classList.remove('is-hidden');
}

function cancelFieldEdit() {
    document.getElementById('editingFieldId').value = '';
    document.getElementById('fieldLabel').value = '';
    document.getElementById('fieldType').value = 'text';
    document.getElementById('fieldOptions').value = '';
    document.getElementById('fieldSortOrder').value = '100';
    document.getElementById('fieldRequired').checked = true;
    document.getElementById('fieldActive').checked = true;
    document.getElementById('submitFieldBtn').textContent = 'Add Field';
    document.getElementById('cancelFieldEditBtn').classList.add('is-hidden');
}

async function deleteField(id) {
    if (!confirm('Delete this custom field? Existing visit history will remain in statistics.')) return;

    const csrfToken = await getCsrfToken();
    if (!csrfToken) return;

    const response = await fetch('../../controllers/form_field_api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'delete', id, csrf_token: csrfToken })
    });
    const result = await response.json();
    alert(result.message || 'Done');
    if (result.status === 'success') {
        updateFieldTable(result.fields || []);
    }
}

// Called on Add or Update button click
async function submitLocation() {
    const id = document.getElementById('editingLocationId').value;
    const name = document.getElementById('newLocationName').value.trim();
    const description = document.getElementById('newLocationDescription').value.trim();
    const color = document.getElementById('newLocationColor').value;

    if (!name) {
        alert('Please enter a location name.');
        return;
    }

    const csrfToken = await getCsrfToken();
    if (!csrfToken) return;

    const action = id ? 'edit' : 'add';
    const payload = { action, name, description, color, csrf_token: csrfToken };
    if (id) payload.id = id;

    const response = await fetch('../../controllers/location_api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    });
    const result = await response.json();

    if (result.status === 'success') {
        alert(result.message);
        updateLocationTable();
        cancelEdit();
    } else {
        alert('Error: ' + result.message);
    }
}


    // Find the location's <li> or from DOM (better to have a data attribute or from backend reload)
    // But here we parse from the DOM list for simplicity:
    
function editLocation(id, name, description, color) {
    document.getElementById('newLocationName').value = name;
    document.getElementById('newLocationDescription').value = description;
    document.getElementById('newLocationColor').value = color;

    document.getElementById('editingLocationId').value = id;

    document.getElementById('submitLocationBtn').textContent = 'Update Location';
    document.getElementById('cancelLocationEditBtn').classList.remove('is-hidden');

    document.getElementById('newLocationName').scrollIntoView({ behavior: 'smooth' });
}


function cancelEdit() {
    document.getElementById('editingLocationId').value = '';
    document.getElementById('newLocationName').value = '';
    document.getElementById('newLocationDescription').value = '';
    document.getElementById('newLocationColor').value = '#000000';

    document.getElementById('submitLocationBtn').textContent = 'Add Location';
    document.getElementById('cancelLocationEditBtn').classList.add('is-hidden');
}

// Helper: convert rgb(a) color string to hex (#rrggbb)
function rgbToHex(rgb) {
    if (!rgb) return '#000000';
    if (rgb.startsWith('#')) return rgb; // Already hex

    // rgb or rgba format: "rgb(255, 0, 0)" or "rgba(255, 0, 0, 1)"
    const rgbValues = rgb.match(/\d+/g);
    if (!rgbValues || rgbValues.length < 3) return '#000000';

    return '#' + rgbValues.slice(0,3).map(x => {
        const hex = parseInt(x).toString(16);
        return hex.length === 1 ? '0' + hex : hex;
    }).join('');
}

async function deleteLocation(id) {
    if (!confirm('Are you sure you want to delete this location?')) return;

    const csrfToken = await getCsrfToken();
    if (!csrfToken) return;

    const response = await fetch('../../controllers/location_api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'delete', id, csrf_token: csrfToken })
    });
    const result = await response.json();

    if (result.status === 'success') {
        alert(result.message);
        updateLocationTable();
    } else {
        alert('Error: ' + result.message);
    }
}

async function updateLocationTable() {
    const csrfToken = await getCsrfToken();
    if (!csrfToken) return;

    const response = await fetch('../../controllers/location_api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'list', csrf_token: csrfToken })
    });
    const result = await response.json();

    if (result.status === 'success') {
        const tbody = document.querySelector('#Location tbody');
        tbody.innerHTML = ''; // Clear existing rows

        result.locations.forEach(location => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${escapeHtml(String(location.name || ''))}</td>
                <td>${escapeHtml(String(location.description || ''))}</td>
                <td><span style="color: ${location.color};">■</span></td>
                <td>
                    <button class="button is-small is-info" onclick="editLocation(${Number(location.location_id)}, '${escapeJs(String(location.name || ''))}', '${escapeJs(String(location.description || ''))}', '${escapeJs(String(location.color || '#000000'))}')">Edit</button>
                    <button class="button is-small is-danger" onclick="deleteLocation(${location.location_id})">Delete</button>
                    <button class="button is-small" onclick="viewLocation(${location.location_id})">View</button>
                </td>
            `;
            tbody.appendChild(tr);
        });
    } else {
        alert('Error: ' + result.message);
    }
}


//user
// Function to fetch CSRF token from the session
async function getCsrfToken() {
    try {
        const response = await fetch('../../controllers/get_csrf.php', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
            },
        });
        if (!response.ok) throw new Error('Failed to fetch CSRF token');
        const data = await response.json();
        return data.csrf_token;
    } catch (err) {
        console.error('Error fetching CSRF token:', err);
        alert('Failed to load CSRF token.');
        return null;
    }
}

// Create user row
function createUserRow(user) {
    const tr = document.createElement('tr');
    tr.setAttribute('data-user-id', user.id);
    tr.innerHTML = `
        <td>${escapeHtml(user.username)}</td>
        <td>••••••••</td>
        <td>${escapeHtml(user.role)}</td>
        <td>
            <button class="button is-small is-info" onclick="editUser(${user.id}, '${escapeJs(user.username)}', '${escapeJs(user.role)}')">Edit</button>
            <button class="button is-small is-danger" onclick="deleteUser(${user.id})">Delete</button>
        </td>
    `;
    return tr;
}

// Escape helper functions for security
function escapeHtml(text) {
    return text.replace(/[&<>"']/g, m => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    })[m]);
}

function escapeJs(text) {
    return text.replace(/\\/g, '\\\\').replace(/'/g, "\\'").replace(/"/g, '\\"').replace(/\r/g, '\\r').replace(/\n/g, '\\n');
}

// Submit user (add/update)
async function submitUser() {
    const id = document.getElementById('editingUserId').value;
    const username = document.getElementById('newUsername').value.trim();
    const password = document.getElementById('newPassword').value;
    const role = document.getElementById('newRole').value;

    if (!username) {
        alert('Username required');
        return;
    }

    try {
        // Get CSRF token from the server
        const csrfToken = await getCsrfToken();
        if (!csrfToken) return;

        const action = id ? 'update' : 'add';
        const payload = { action, username, role, csrf_token: csrfToken };
        if (id) payload.id = id;
        if (password) payload.password = password;

        const response = await fetch('../../controllers/user_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(payload)
        });

        if (!response.ok) {
            const errorText = await response.text();
            throw new Error(`Server error ${response.status}: ${errorText || response.statusText}`);
        }

        const data = await response.json();
        alert(data.message);

        if (data.status === 'success') {
            const usersTableBody = document.getElementById('usersTableBody');
            if (action === 'add' && data.user) {
                usersTableBody.appendChild(createUserRow(data.user));
            } else if (action === 'update') {
                const row = usersTableBody.querySelector(`tr[data-user-id="${id}"]`);
                if (row) {
                    row.cells[0].textContent = username;
                    row.cells[2].textContent = role;
                }
            }
            cancelEditUser();
        }
    } catch (err) {
        console.error('Error submitting user:', err);
        alert('An error occurred while processing the user: ' + err.message);
    }
}

// Delete user
async function deleteUser(id) {
    if (!confirm('Are you sure you want to delete this user?')) return;

    try {
        // Get CSRF token from the server
        const csrfToken = await getCsrfToken();
        if (!csrfToken) return;

        const response = await fetch('../../controllers/user_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ action: 'delete', id, csrf_token: csrfToken })
        });

        const data = await response.json();
        alert(data.message);

        if (data.status === 'success') {
            const usersTableBody = document.getElementById('usersTableBody');
            const row = usersTableBody.querySelector(`tr[data-user-id="${id}"]`);
            if (row) usersTableBody.removeChild(row);
        }
    } catch (err) {
        console.error('Error deleting user:', err);
        alert('An error occurred while deleting the user.');
    }
}


function editUser(id, username, role) {
    // Set the hidden input so submitUser knows this is an update
    document.getElementById('editingUserId').value = id;
    // Fill inputs with existing data
    document.getElementById('newUsername').value = username;
    document.getElementById('newPassword').value = ''; // clear password field for security
    document.getElementById('newRole').value = role;

    // Change the submit button to reflect update mode
    const submitBtn = document.getElementById('submitUserBtn');
    submitBtn.textContent = 'Update User';

    // Show the cancel edit button
    document.getElementById('cancelUserEditBtn').classList.remove('is-hidden');
}

function cancelEditUser() {
    // Clear the hidden input so submitUser knows this is an add operation
    document.getElementById('editingUserId').value = '';

    // Clear all form inputs
    document.getElementById('newUsername').value = '';
    document.getElementById('newPassword').value = '';
    document.getElementById('newRole').value = 'admin'; // or your default role

    // Reset buttons
    const submitBtn = document.getElementById('submitUserBtn');
    submitBtn.textContent = 'Add User';

    // Hide the cancel edit button
    document.getElementById('cancelUserEditBtn').classList.add('is-hidden');
}

function openReport() {
    const startDate = document.getElementById('startDate').value;
    const endDate = document.getElementById('endDate').value;

    if (!startDate || !endDate) {
        alert('Please select both dates.');
        return;
    }

    if (startDate > endDate) {
        alert('From Date must be before To Date.');
        return;
    }

    const params = new URLSearchParams({
        start_date: startDate,
        end_date: endDate
    });
    window.open('report.php?' + params.toString(), '_blank');
}

function viewLocation(id) {
  const encodedId = btoa(id); // encode id to base64
  window.open('qr-page.php?id=' + encodeURIComponent(encodedId), '_blank');
}


</script>
</body>
</html>
