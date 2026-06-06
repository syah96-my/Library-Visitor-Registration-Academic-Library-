<?php

function getFormFields($activeOnly = true) {
    global $pdo;

    $sql = "SELECT * FROM form_fields";
    if ($activeOnly) {
        $sql .= " WHERE active = 1";
    }
    $sql .= " ORDER BY sort_order ASC, field_id ASC";

    $stmt = $pdo->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function sanitizeFieldKey($label) {
    $key = strtolower(trim($label));
    $key = preg_replace('/[^a-z0-9]+/', '_', $key);
    $key = trim($key, '_');

    return $key !== '' ? substr($key, 0, 64) : 'custom_field';
}

function normalizeOptions($options) {
    $lines = preg_split('/\r\n|\r|\n/', (string) $options);
    $clean = [];

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line !== '') {
            $clean[] = $line;
        }
    }

    return implode("\n", $clean);
}

function renderVisitorFields($prefill = []) {
    $fields = getFormFields(true);

    foreach ($fields as $field) {
        $key = $field['field_key'];
        $label = htmlspecialchars($field['label']);
        $required = (int) $field['required'] === 1 ? 'required' : '';
        $inputName = in_array($key, ['name', 'faculty', 'semester'], true)
            ? $key
            : 'custom_fields[' . htmlspecialchars($key) . ']';
        $value = htmlspecialchars($prefill[$key] ?? '');

        echo '<div class="field">';
        echo '<label class="label has-text-white">' . $label . '</label>';
        echo '<div class="control has-icons-left">';

        if ($field['field_type'] === 'select') {
            echo '<div class="select is-fullwidth is-rounded"><select name="' . $inputName . '" ' . $required . '>';
            echo '<option value="" disabled ' . ($value === '' ? 'selected' : '') . '>Sila pilih</option>';
            foreach (preg_split('/\r\n|\r|\n/', (string) $field['options']) as $option) {
                $option = trim($option);
                if ($option === '') {
                    continue;
                }
                $safeOption = htmlspecialchars($option);
                $selected = $value === $option ? 'selected' : '';
                echo '<option value="' . $safeOption . '" ' . $selected . '>' . $safeOption . '</option>';
            }
            echo '</select></div>';
        } else {
            echo '<input class="input is-rounded" type="text" name="' . $inputName . '" value="' . $value . '" placeholder="' . $label . '" ' . $required . '>';
            echo '<span class="icon is-small is-left"><span class="iconify" data-icon="mdi:form-textbox" style="color: #ffffff;"></span></span>';
        }

        echo '</div>';
        echo '</div>';
    }
}

function collectVisitPayload($post) {
    $customFields = [];
    if (!empty($post['custom_fields']) && is_array($post['custom_fields'])) {
        foreach ($post['custom_fields'] as $key => $value) {
            $key = sanitizeFieldKey($key);
            $value = trim((string) $value);
            if ($value !== '') {
                $customFields[$key] = $value;
            }
        }
    }

    return [
        'name' => trim((string) ($post['name'] ?? $post['nama'] ?? '')),
        'faculty' => trim((string) ($post['faculty'] ?? '')),
        'semester' => trim((string) ($post['semester'] ?? '')),
        'custom_fields' => $customFields,
    ];
}

