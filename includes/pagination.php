<?php

declare(strict_types=1);

function paginate(int $totalItems, int $perPage, int $currentPage): array
{
    $totalPages = max(1, (int) ceil($totalItems / max(1, $perPage)));
    $currentPage = max(1, min($currentPage, $totalPages));
    $offset = ($currentPage - 1) * $perPage;

    return [
        'total_items' => $totalItems,
        'per_page' => $perPage,
        'current_page' => $currentPage,
        'total_pages' => $totalPages,
        'offset' => $offset,
    ];
}

function page_url(int $page): string
{
    $params = $_GET;
    $params['page'] = $page;
    return strtok($_SERVER['REQUEST_URI'] ?? '', '?') . '?' . http_build_query($params);
}

function render_pagination(array $pagination): void
{
    if (($pagination['total_pages'] ?? 1) <= 1) {
        return;
    }
    ?>
    <nav class="mt-6 flex items-center justify-center gap-2 text-sm">
      <?php if ($pagination['current_page'] > 1): ?>
        <a class="rounded border px-3 py-1 hover:opacity-80 transition" href="<?= htmlspecialchars(page_url($pagination['current_page'] - 1), ENT_QUOTES, 'UTF-8'); ?>">← Trước</a>
      <?php endif; ?>

      <?php for ($p = 1; $p <= (int) $pagination['total_pages']; $p++): ?>
        <a href="<?= htmlspecialchars(page_url($p), ENT_QUOTES, 'UTF-8'); ?>"
           class="rounded border px-3 py-1 <?= $p === (int) $pagination['current_page'] ? 'bg-black text-white border-black' : 'hover:opacity-80'; ?> transition">
          <?= $p; ?>
        </a>
      <?php endfor; ?>

      <?php if ($pagination['current_page'] < $pagination['total_pages']): ?>
        <a class="rounded border px-3 py-1 hover:opacity-80 transition" href="<?= htmlspecialchars(page_url($pagination['current_page'] + 1), ENT_QUOTES, 'UTF-8'); ?>">Sau →</a>
      <?php endif; ?>
    </nav>
    <?php
}
