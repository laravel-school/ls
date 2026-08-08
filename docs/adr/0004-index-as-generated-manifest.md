---
status: accepted
---

# The Index is a generated in-memory manifest, not a database

The `Build` writes the Index as a single generated PHP array file holding every
Document's Frontmatter and file path. Feeds filter and sort it as a Collection.
Pagination uses `LengthAwarePaginator`, which paginates arrays natively, so the
existing `$paginator->links()` in the listing view keeps working.

The corpus is 157 Documents and 840KB of markdown, growing by roughly one
Document a week. At that size a schema, a migration, a second connection, and an
FTS index are machinery without a workload — and a generated PHP file sits in
opcache, so after the first request it costs nothing to load.

## Consequences

- The Index and the client-side search index are the same data at two levels of
  detail, produced by one pass: the full manifest to `storage/`, a trimmed public
  subset to `public/` for the browser to fetch and filter. Search needs no
  endpoint and no backend.
- Search is substring matching over 157 records, not ranked full-text with
  stemming. Acceptable at this size; the reason to revisit is thousands of
  Documents, not hundreds.
- Rendered HTML is *not* part of the Index. It is rendered per request and cached
  forever under a key including the file's mtime, so a changed Document can never
  serve stale HTML and there is no cache to clear by hand. The `Build`'s final
  step pre-warms that cache, which doubles as the test that all 157 still parse.
