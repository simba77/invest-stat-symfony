# HTTP API

All application routes are JSON endpoints under the `/api` prefix (`config/routes.yaml`),
consumed by the Vue SPA.

## Controllers

* Location: `<Context>/Application/Controller/*Controller`.
* Attribute routing: `#[Route('/deposits/accounts', name: 'app_deposit_accounts', methods: ['GET'])]`.
* Private endpoints: `#[IsGranted('IS_AUTHENTICATED', statusCode: 403)]` on the class.
* Current user: `#[CurrentUser] ?User $user`; always scope lookups to the user
  (e.g. `getByIdAndUser($id, $user)`).
* Missing resource → `throw $this->createNotFoundException('No deposits found for id ' . $id);`.
* Respond with `$this->json(...)` / `JsonResponse`; mutations usually return `['success' => true]`.
* Controllers only map HTTP: business logic goes to handlers, use cases, or domain services.

## Authentication

* `json_login` on `POST /api/login` (`username`, `password`, `remember_me`); `GET /api/login`
  returns the current user or 401. Session storage is native PHP files and gets garbage-collected.
* Remember-me is a signed cookie (`config/packages/security.yaml`): one year, renewed whenever it
  restores a session, invalidated for all devices by a password change. No database tokens.
* Do not add `LoginSuccessEvent` listeners that touch `RememberMeBadge`: the event also fires
  for remember-me logins, and a badge there makes Symfony clear the device's cookie.
* Covered by `tests/Shared/Application/Controller/AuthControllerTest.php`.

## Requests

* Body mapping: `#[MapRequestPayload] CreateDepositRequestDTO $dto`.
* Request DTOs: `Application/Request/DTO/*RequestDTO`, typed properties with
  `#[Assert\...]` constraints.
* Never pass raw request arrays into domain code.

## Responses

* Response DTOs: `Application/Response/DTO/*DTO`.
* Built from entities by compilers: `Application/Response/Compiler/*Compiler` with a
  `compile()` method.
* Changing a response shape means changing the matching TypeScript type in `assets/types/`.

## Pagination

Use `App\Shared\Application\Pagination`: `PageRequestFactory` + `PaginationMetaFactory`
produce a `PaginatedResponseDTO<T>`. See `Deposits/Application/UseCases/GetDepositsPageUseCase`
for the reference implementation; the frontend counterpart is `PaginatedResponse<T>` in
`assets/types/pagination.ts`.
