# CODEX Revenue OS — CODEX Commerce

منصة B2B SaaS لمتاجر سلة (وWooCommerce للتجربة): استرجاع السلات المتروكة على واتساب الرسمي، وتقرير **ربح إضافي** بمجموعة مقارنة (holdout)، ومساعد مبيعات AI بوضع الموافقة.

- **المرجع الأول:** [CLAUDE.md](CLAUDE.md)، وفيه القواعد والـstack وطريقة الشغل.
- **الحالة:** [STATUS.md](STATUS.md)، وفيها اللي اتعمل واللي مستني قرار.
- **القرارات المعمارية:** [docs/adr/](docs/adr/).

## المتطلبات
| الأداة | الإصدار |
|---|---|
| PHP | 8.3 أو أحدث، ومعاه `pdo_pgsql` و`bcmath` |
| Composer | 2 |
| Node.js | 20.9 أو أحدث (يفضّل 22) |
| Docker | علشان PostgreSQL + pgvector |

## التشغيل لأول مرة
```bash
git clone https://github.com/mathewosama9/CODEX-Revenue-OS.git
cd CODEX-Revenue-OS

# 1. الداتابيز (PostgreSQL 17 + pgvector على بورت 54320، ومعاها codex_testing للاختبارات)
docker compose up -d pgsql

# 2. ملف الإعدادات
cp .env.example .env
php -r "echo bin2hex(random_bytes(32)).PHP_EOL;"   # حط الناتج في CODEX_SUBJECT_HASH_KEY جوه .env

# 3. للتشغيل المحلي من غير مفاتيح مدفوعة، عدّل في .env:
#    CODEX_AI_PROVIDER=demo
#    CODEX_PRICE_MAX_AGE_MINUTES=10080

# 4. تسطيب الحزم، والمفتاح، والجداول، وحزم الواجهة
composer setup

# 5. متجر تجريبي فيه بيانات
php artisan db:seed --class=DemoStoreSeeder

# 6. ملف إعدادات الواجهة
cp web/.env.example web/.env.local
```

## التشغيل اليومي
```bash
docker compose up -d pgsql
composer dev
```
الأمر ده بيشغّل الـAPI على http://localhost:8000، والـqueue، والـlogs، والتطبيق على **http://localhost:3001**.

- **الدخول:** `owner@demo.test` أو `agent@demo.test`، والباسورد `password`.
- افتح على `localhost` مش `127.0.0.1`، علشان الـsession cookie.
- رسالة عميل تجريبية من غير واتساب حقيقي: `php artisan codex:demo-message "بكم العود الكمبودي؟"`.
- الـscheduler (الـsync ورسايل الاسترجاع): `php artisan schedule:work` في terminal منفصل.

## الاختبارات
```bash
composer ci:check     # RTL، وlint وtypes بتوع web/، وPint، وPHPStan، وPest
cd web && npm run e2e # Playwright على الـAPI الحقيقي (DB: codex_e2e، بورتات 8010 و3010)
php artisan ai:eval   # الـgolden set بتاع مساعد المبيعات offline، من غير مفاتيح
```
**ممنوع تفتح PR ولو اختبار واحد فاشل.**

## الهيكل
| الفولدر | فيه إيه |
|---|---|
| `app/Modules/*` | الـModules (Commerce، Recovery، Measurement، SalesAgent، AiGateway…)، وقواعدها في `app/Modules/README.md` ومفروضة بـ`tests/Arch` |
| `web/` | تطبيق Next.js 16 (اقرا `web/AGENTS.md` الأول) |
| `integrations/wordpress/codex-connector/` | plugin الـWooCommerce. بيتبني بـ`scripts/build-wp-plugin.sh` |
| `evals/golden/` | حالات اختبار مساعد المبيعات |
| `docs/` | الـBlueprint، والـADRs، وأدلة كل مرحلة |

## قواعد لازم تتّبع
- **الأسرار:** ممنوع ترفع `.env` أو أي مفتاح. المفاتيح المدفوعة (AI، Meta، سلة) بتتاخد من المؤسس بس.
- **الشغل:** على branch، وبعدين Pull Request. مفيش push مباشر على `main`.
- **الفلوس:** integer بأصغر وحدة لعملة المساحة، والتحويل عن طريق `Currency::toMinor` بس، **ممنوع float** ([ADR-012](docs/adr/ADR-012-multi-currency.md)).
- **العزل:** أي model فيه بيانات تاجر لازم يستخدم `BelongsToTenant`.
- **الواجهة:** logical properties بس (`ms-` و`me-`، مش `ml-` و`mr-`)، والأصفر للفلوس بس.
