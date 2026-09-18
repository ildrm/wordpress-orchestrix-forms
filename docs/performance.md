# Performance

The public path is server-rendered and does not depend on jQuery or the builder bundle.
`frontend.js` and `frontend.css` are the only unconditional form assets. Feature assets
are enqueued only when the schema inspector finds the corresponding field/rule feature.
Submission and entry queries use bounded database pagination. High-cardinality lookup,
date, status, and aggregate paths are indexed. Workflow HTTP and mail work runs outside
the public request through the retryable queue.

Measured production core assets are 3,995 bytes raw / 1,352 bytes gzip JavaScript and
1,693 bytes raw / 677 bytes gzip CSS. Dataset benchmarks at 10K, 100K, and 1M
entries have not been run in this environment; query design supports them, but this is
not a substitute for measured capacity testing.
