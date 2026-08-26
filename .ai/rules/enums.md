# Enum Translations

- Enum case labels MUST be resolved through the `App\Enums\Enum::trans()` trait (e.g. `SourceEnum::fromString($key)?->trans()`), never hardcoded label arrays or match statements in Blade/controllers.
- Every enum case MUST have a translation entry in BOTH `lang/en.json` and `lang/fa.json`, added in the same change that introduces the case.
- Key format: `enums.{ClassName}.{CASE}` (e.g. `enums.CaratEnum.CARAT_18`). The trait checks this namespaced key first, then `enums.{CASE}`, then falls back to the raw case name.
- CRITICAL: JSON lang files must contain FLAT string keys (`"enums.SourceEnum.DIGIKALA": "..."`). Laravel's Translator does exact-key lookup for JSON files (`$this->loaded['*']['*'][$locale][$key] ?? null`) — nested structures silently return the raw key instead of translating.
