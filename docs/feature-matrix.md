# Feature matrix

| Domain | Implementation | Automated coverage | Manual verification | Known limitation |
|---|---|---|---|---|
| Builder | Partial | TS compile, state tests | Not run in WordPress | Core field canvas only |
| Fields | Partial | PHP syntax/static analysis | Not run | Catalog/contract present; specialized widgets incomplete |
| Layout | Partial | Static analysis | Not run | Structural schema types; runtime layout controls limited |
| Styling | Partial | CSS build/lint | Not run | Tokens/core theme only |
| Validation | Partial | Static analysis | Not run | Core synchronous rules; remote/file endpoint incomplete |
| Conditional logic | Partial | Unit tests | Not run | Server AST works; full action editor/client runtime incomplete |
| Calculations | Implemented core | Unit tests | Not run | Restricted engine and aggregates; formula editor incomplete |
| Repeaters | Partial | Calculation aggregate test | Not run | Schema types only; add/remove UI/runtime incomplete |
| Multi-step | Partial | None | Not run | Step schema only |
| Conversational | Not implemented | None | Not run | — |
| Entries | Partial | Static analysis | Not run | List/detail API; advanced UI/editing absent |
| Drafts | Partial | Docker draft/public version isolation | Draft remained private while published version stayed public | Submission resume endpoints absent |
| Partial submissions | Partial | Schema syntax | Not run | State model only |
| Workflow | Partial | State tests + Docker queue lease recovery | Expired lease reclaimed and processed | Full action catalog absent |
| Notifications | Partial | Merge-tag static analysis | Not run | Queued email works; templates/log UI absent |
| WordPress data | Not implemented | None | Not run | Mapping adapter absent |
| ACF | Not implemented | None | Not run | Graceful absence by no coupling |
| Users | Not implemented | None | Not run | — |
| WooCommerce | Not implemented | None | Not run | Graceful absence by no coupling |
| Payments | Partial | State/refund tests | Not run | Domain contract/table; no gateway adapters/controllers |
| Subscriptions | Partial | Schema only | Not run | No gateway adapter |
| Coupons | Partial | Calculation support | Not run | No persistence/UI |
| Surveys | Partial | NPS unit test | Not run | Fields/statistics; persistence/report UI absent |
| Polls | Partial | Field catalog | Not run | Restrictions/results absent |
| Quizzes | Partial | Scoring unit test | Not run | Fields/scorer; attempts/certificates absent |
| Signatures | Partial | Field catalog | Not run | Canvas/storage absent |
| Files | Partial | Executable-name/MIME PHPUnit attack test | Validator rejection passed | Upload/finalization endpoint absent |
| PDF/Documents | Not implemented | None | Not run | — |
| Views | Not implemented | None | Not run | — |
| Frontend CRUD | Partial | Owner authorization analysis | Not run | Read authorization only |
| Booking | Partial | Atomic capacity service analysis | Not run | Scheduling UI/model absent |
| Analytics | Partial | Aggregate schema | Not run | Collection/reporting absent |
| Geolocation | Partial | Field catalog | Not run | Provider adapter absent |
| Spam protection | Partial | Static analysis | Not run | Honeypot/time/links/rate limit; CAPTCHA providers absent |
| Integrations | Partial | Webhook/security tests | Not run | Generic webhook only |
| Webhooks | Partial | Signature/replay test | Not run | Outgoing job works; inbound endpoint absent |
| REST API | Partial | PHPStan + Docker smoke | Create/publish/submit/private denial passed | Core forms/submissions only |
| Developer API | Partial | PHPStan | Not run | Field/workflow hooks and payment contract; full registry set absent |
| AI | Not implemented | None | Not run | — |
| Migration | Partial | Idempotent schema + clean install | v1.0.0 migration passed | Competitor importers absent |
| Multilingual | Partial | String scan/manual code review | Not run | i18n functions/RTL CSS; catalogs absent |
| Accessibility | Partial | Semantic code review | Not run | axe/manual testing pending |
| Privacy | Partial | Privacy columns/hash analysis | Not run | WP exporter/eraser and retention UI absent |
| Security | Partial | PHPUnit/static/offline npm audit/Docker auth and encryption tests | Anonymous IDOR denial and encrypted secret storage passed | Full penetration tests and fresh online advisory queries pending |
| Performance | Partial | Asset gzip budget passed | Core JS 1.35KB, CSS 677B gzip | Load tests pending |
| Debugging | Partial | Static analysis | Not run | Queue/database diagnostics; simulation console absent |
| Multisite | Partial | Per-site activation code analysis | Not run | Runtime multisite test pending |

“Implemented core” is not equivalent to complete specification coverage. See
[known limitations](known-limitations.md).
