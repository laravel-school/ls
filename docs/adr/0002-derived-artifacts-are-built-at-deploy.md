---
status: accepted
---

# Derived Artifacts are built at deploy, never committed

Every Derived Artifact — Index, sitemap, RSS feed, search index, OG images — is
regenerated on the server by a `Build` step in the Forge deploy script, from the
Documents in the repository. None of them is committed to git.

Previously the SQLite index and the sitemap were generated on the author's laptop
and committed alongside each post. This produced two standing defects: the index
only regenerated while `npm run dev` happened to be running (so a post could be
published invisible), and the committed `public/prezet_sitemap.xml` advertised
**157 `http://localhost` URLs** to search engines, because it was generated in an
environment where `APP_URL` was `http://localhost`.

## Consequences

- **Publishing is now literally one file.** Commit the markdown, push, done — which
  is the requirement this whole rebuild exists to satisfy.
- **The deployed site is a pure function of the git SHA.** Rollback is `git revert`
  plus a redeploy, and it is complete, because there is no state outside the
  repository to un-migrate. This is what makes a big-bang cutover safe.
- **Derived Artifacts cannot drift from Documents**, because they are never
  incrementally updated — only rebuilt.
- **Scheduled publishing becomes impossible**, and deliberately so. Nothing runs
  between deploys, so a future-dated Document would stay hidden indefinitely. The
  alternative — a cron rebuilding the site — would let published content change
  with no commit behind it, forfeiting the property above.
- **A malformed Document fails the Build and aborts the deploy.** To keep that from
  blocking unrelated work, the same validation runs in CI on push, so a broken
  Document fails there first. The server-side failure is a backstop.
