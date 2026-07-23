# Task 9 Report: Style docs table as card

**Completed:** Yes

## Changes made to `views/docs/file-list.php`

1. Added `docs-table` class to `<table>` element
2. Wrapped table in `<div class="card-custom p-0 overflow-hidden">` — the existing `<div class="table-responsive">` was changed to `mb-0` and enclosed inside the card
3. Closed the card wrapper with an extra `</div>` after the table-responsive closes
4. Folder rows (`role="link"`, `tabindex="0"`, keyboard handler) unchanged
5. File type icons unchanged

## Verification

- `php -l /mnt/disco/explorador/views/docs/file-list.php` — No syntax errors detected
