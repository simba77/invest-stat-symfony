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
