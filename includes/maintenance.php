<?php
function maintenance_pages(): array
{
    $raw = site_setting('maintenance_pages', '');
    $pages = array_filter(array_map('trim', explode(',', $raw)));
    return array_values(array_unique($pages));
}

function page_under_maintenance(string $page): bool
{
    return in_array($page, maintenance_pages(), true);
}

function public_page_active(string $page): bool
{
    return is_admin_logged_in() || !page_under_maintenance($page);
}
