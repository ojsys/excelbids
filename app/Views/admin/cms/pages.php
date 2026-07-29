<?php
/** @var array<int,array<string,mixed>> $pages */
?>

<div class="card">
  <?php if (!$pages): ?>
    <div class="empty">
      <span class="mark">▭</span>
      <h3>No pages yet</h3>
      <p>Standalone pages such as a privacy policy or terms of service live here.</p>
      <a href="<?= e(path('admin/cms/pages/create')) ?>" class="btn btn-red btn-sm">Create a page</a>
    </div>
  <?php else: ?>
    <div class="table-wrap">
      <table class="data">
        <thead>
          <tr><th>Title</th><th>URL</th><th>Status</th><th>In footer</th><th>Updated</th><th class="actions"></th></tr>
        </thead>
        <tbody>
          <?php foreach ($pages as $page): ?>
            <tr>
              <td>
                <span class="primary-cell">
                  <a href="<?= e(path('admin/cms/pages/' . $page['id'] . '/edit')) ?>"><?= e((string) $page['title']) ?></a>
                </span>
              </td>
              <td class="ref">/<?= e((string) $page['slug']) ?></td>
              <td>
                <?php if ((int) $page['is_published'] === 1): ?>
                  <span class="badge badge-success">Published</span>
                <?php else: ?>
                  <span class="badge badge-muted">Draft</span>
                <?php endif; ?>
              </td>
              <td class="u-small u-muted"><?= (int) $page['show_in_footer'] === 1 ? 'Yes' : 'No' ?></td>
              <td class="u-small u-muted"><?= e(fdate((string) ($page['updated_at'] ?: $page['created_at']))) ?></td>
              <td class="actions">
                <?php if (($page['layout_mode'] ?? 'blocks') === 'blocks'): ?>
                  <a href="<?= e(path('admin/cms/pages/' . $page['id'] . '/build')) ?>" class="btn btn-red btn-sm">Page builder</a>
                <?php endif; ?>
                <?php if ((int) $page['is_published'] === 1): ?>
                  <a href="<?= e(path((string) $page['slug'])) ?>" target="_blank" rel="noopener" class="btn btn-subtle btn-sm">View ↗</a>
                <?php endif; ?>
                <form method="post" action="<?= e(path('admin/cms/pages/' . $page['id'] . '/delete')) ?>"
                      style="display:inline;" data-confirm="Delete the page &quot;<?= e((string) $page['title']) ?>&quot;?">
                  <?= csrf_field() ?>
                  <button type="submit" class="btn btn-subtle btn-sm" aria-label="Delete page">✕</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
