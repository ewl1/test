<?php
/**
 * Placeholder WYSIWYG infusion.
 * Ateityje galima prijungti TinyMCE, CKEditor ar kitą editorių.
 */
function wysiwyg_editor($name, $value = '')
{
    $value = htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    return '<textarea class="form-control" name="' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '" rows="10">' . $value . '</textarea>';
}
