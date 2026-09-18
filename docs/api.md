# API and embedding

Namespace: `/wp-json/orchestrix-forms/v1/`

| Method | Route | Authorization |
|---|---|---|
| GET | `/fields` | `orchestrix_edit_forms` |
| GET/POST | `/forms` | manage/edit forms |
| GET | `/forms/{id}` | edit forms |
| PUT/PATCH | `/forms/{id}/draft` | edit forms |
| POST | `/forms/{id}/publish` | publish forms |
| POST | `/forms/{id}/submissions` | signed public form token + rate limit |
| GET | `/submissions` | view entries |
| GET | `/submissions/{id}` | view entries or entry owner |

Embed with `[orchestrix_form id="123"]`, the Orchestrix Form block, or
`Plugin::instance()->renderer()->render(123)`. REST errors use WordPress' standard
`code`, `message`, and `data.status`; validation errors also include `data.fields`.

Register a field during `orchestrix_forms_register_fields` with an implementation of
`FieldTypeInterface`. Custom workflow actions listen on
`orchestrix_forms_workflow_action_{type}` and must be idempotent.
