# ADR-002: الـFrontend = Next.js منفصل، وLaravel = API

- **الحالة:** Accepted (2026-10-25)
- **صاحب القرار:** المؤسس
- **بيحل محل:** توصية الـBlueprint (Inertia 3 + React) في الـspine §8 و[09](../blueprint/09-technical-architecture.md) §9.3 و[14](../blueprint/14-key-decisions.md) D-T02.

## القرار
- واجهة التاجر هتتبني **Next.js + TypeScript + Tailwind** في تطبيق منفصل.
- Laravel بيبقى **backend بس**: API تحت `/api/v1`، والـwebhooks، والـjobs، والأدمن الداخلي.

## ليه
المؤسس اختار Next.js، وده الأداة اللي خبرته فيها (البرومبت §6).

## التبعات (الـBlueprint كان ذاكرها، وبنقبلها صراحةً)

| التبعة | إزاي هنتعامل معاها |
|---|---|
| Auth بين تطبيقين | **Laravel Sanctum SPA** (session cookie + CSRF). Next.js وLaravel على نفس الـtop-level domain، مثلًا `app.codexai.systems` و`api.codexai.systems` |
| CORS | `supports_credentials` + origin واحد مسموح (دومين Next.js) |
| API layer كامل | كل feature في P1 بتطلع **JSON API** بـtenant في الـpath: `/api/v1/teams/{team}/...`، ونفس `EnsureTeamMembership` + `SetTenantContext` |
| Deploy تاني | Next.js على نفس الـregion (ADR-005). مفيش بيانات تجار بتتخزن في Next.js |
| البراند | الـtokens في `resources/css/app.css` ومكوّنات `resources/js/components/brand/` بتتنقل لـNext.js زي ما هي (Tailwind 4) |
| RTL | نفس القاعدة: logical properties بس، ونفس `scripts/check-rtl.mjs` |

## اللي اتعمل قبل القرار
الـscaffold بتاع Inertia + React (S0) **فاضل مؤقتًا**: صفحات الـauth والـteams والثيم. مش هيتبني عليه أي شاشة جديدة، وهيتشال لما تطبيق Next.js يغطي نفس الصفحات.

## مش جزء من القرار
الـAdmin الداخلي (E14) يفضل Filament جوه Laravel، لأنه لفريق CODEX بس، مش للتاجر.
