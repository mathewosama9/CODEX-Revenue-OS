<?php

namespace App\Modules\AiGateway\Demo;

use App\Modules\AiGateway\Testing\FakeLlm;

/**
 * `CODEX_AI_PROVIDER=demo`: a canned model for local demos and the web E2E
 * suite, so the drafts flow can be tried without a paid key. It searches the
 * catalog and writes a reply with no numbers. Only in the `local` and
 * `testing` environments: a staging box with a real number must never send it.
 */
final class DemoModel
{
    public static function allowed(): bool
    {
        return app()->environment(['local', 'testing']);
    }

    public const PROVIDER = 'demo';

    public const REPLY = 'رد تجريبي من الذكاء الاصطناعي: شكرًا لرسالتك! بحثت لك في منتجات المتجر، وأقدر أساعدك تختار. وش تفضّل؟';

    public static function script(string $customerText): void
    {
        FakeLlm::script([
            FakeLlm::tool('search_products', ['query' => mb_substr($customerText, 0, 100)]),
            FakeLlm::reply(self::REPLY),
        ]);
    }
}
