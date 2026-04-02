# Backend Architecture Guardrails

## Official Convention

Use `FormRequest -> Controller -> Service -> Repository`.

- `FormRequest`: validates and normalizes HTTP input.
- `Controller`: translates HTTP to application calls, prepares view/resource/redirect, nothing else.
- `Service`: owns business rules, transactions, orchestration, uploads, external integrations.
- `Repository`: encapsulates data access when query/persistence logic is non-trivial.

## Prohibited In Controllers

- `$request->validate(...)` or `Validator::make(...)` in mutable flows
- `DB::transaction(...)`
- direct `Model::query()`, `Model::create()`, `Model::update()`, `Model::delete()`
- direct route-model mutation such as `$model->update()` or `$model->delete()`
- direct `Repository` injection when a service boundary should exist

## Temporary Exceptions

The temporary whitelist lives in [tests/Support/ControllerArchitectureWhitelist.php](/home/oem/Workspace/salgados-api/tests/Support/ControllerArchitectureWhitelist.php).

Current exceptions exist only for controllers still pending architectural migration:

- `Admin/AdminAuthController`: login/session flow still inline
- `Admin/OrderController`: read-filter validation still inline
- `Admin/StoreController`: destroy still deletes route model directly
- `Api/V1/CategoryController`
- `Api/V1/CouponController`
- `Api/V1/FlavorController`
- `Api/V1/LoyaltyRewardController`
- `Api/V1/NotificationController`
- `Api/V1/OrderController`
- `Api/V1/ProductController`
- `Api/V1/PromotionController`
- `Api/V1/UserCouponAdminController`
- `Api/V1/UserCouponController`

Rule: add to the whitelist only with a concrete reason and remove the entry in the same PR that fixes the controller.

## Quick-Dev Review Checklist

- Does every mutable endpoint use a `FormRequest`?
- Does the controller delegate to a service instead of mutating models directly?
- Are transactions, ordering rules, casting, uploads and domain validation inside services?
- Does the controller avoid direct `Repository` injection unless the controller is explicitly whitelisted?
- If a controller exception was necessary, was it added to the whitelist with a reason and residual backlog?
- Did the architecture tests run and stay green?

## Automated Guardrails

Architecture tests live in:

- [tests/Unit/ControllerArchitectureTest.php](/home/oem/Workspace/salgados-api/tests/Unit/ControllerArchitectureTest.php)
- [tests/Feature/ArchitectureBoundaryRegressionTest.php](/home/oem/Workspace/salgados-api/tests/Feature/ArchitectureBoundaryRegressionTest.php)

These tests enforce:

- forbidden controller patterns outside the whitelist
- `FormRequest` usage on mutable actions outside method-level exceptions
- no direct repository injection outside the whitelist
- delegation checks for the controllers already migrated in phases 1 and 2
