---
name: add-endpoint
description: "Adds or changes an SDK method when the hihaho v2 API gains or changes a route, request field, response field or value list. Triggers: new endpoint, api change, add method, new field, new enum value, sdk sync, update route map."
---

# Add or Change an Endpoint

The SDK mirrors the public hihaho v2 API. Work from two sources: the public API documentation at https://api-docs.hihaho.com, and the SDK issue that requests the change. The issue names the change, gives the current route list, and gives sample requests and responses. Read both first.

## When to use this skill

- An SDK issue asks for a new or changed endpoint, field or value.
- `tests/Unit/RouteMapTest.php` fails after `tests/Fixtures/v2-routes.json` was refreshed.

## Read the API, never guess

For each item, find the API concept in the documentation or the issue, and change the matching SDK part:

| API concept | SDK part |
|---|---|
| Route (HTTP method and URI) | `src/Support/RouteMap.php` + a resource method in `src/Resources/` |
| Request fields of a route | method parameters or a class in `src/Input/` |
| Batch import fields (interactions, questions, subtitles) | `src/Input/Interactions/*`, `src/Input/Questions/*` or `src/Input/SubtitleInput.php` |
| Response fields | a read model in `src/Data/` |
| Value list (the values a field accepts or returns) | an enum in `src/Enums/` |

Note the wrap (`data` or bare), the status codes, whether the list is paginated, and every field's type, nullability and date format. When the documentation and the issue do not settle one of these, ask in the issue; do not guess.

## Steps

1. **Refresh the route snapshot.** Replace `tests/Fixtures/v2-routes.json` with the route list given in the issue.
2. **Map the route.** Add it to `RouteMap::ROUTES` with the chain of calls from `Hihaho` to the method, or to `RouteMap::EXCLUDED` with the reason. A route under `v2/video/{video_id}` belongs to `VideoScope`: add the method there, or add a resource and an accessor on `VideoScope`, never an accessor with a video id on `Hihaho`. An exclusion needs the user's approval and a `GAPS.md` line.
3. **Add the enum cases or enums** in `src/Enums/`. Send the form the API compares: the string value when the API takes strings, the lowercase case name when the API takes case names (see `InteractionActionType`).
4. **Add or extend the read model** in `src/Data/`. Use `Payload` for every read, so a missing or retyped field reads as null and never throws. Keep enum fields as a pair: the raw `…Value` and the nullable enum.
5. **Add the resource method** in `src/Resources/`. Use `paged()` for a paginated list, `bare()` for an unpaginated list, `getData()`/`getBare()` for one object. Guard batch maximums with `idBatch()`.
6. **Add a code sample** for the route to `resources/code-samples.json`, keyed by the route name. `tests/Unit/CodeSamplesTest.php` runs it and fails when a mapped route has no sample.
7. **For import fields**, add the parameter only to the type classes that the API accepts it for, and add the path to the allowed set in `tests/Unit/Input/*InputTest.php`. Leave a field out while the API drops it, and record it in `GAPS.md`.
8. **Tests.** Fake the response with a payload copied from the API documentation or the sample in the issue, not from what the SDK reads. Cover the unknown-value and missing-field cases for a new enum or field.
9. **Run** `composer qa` (or PHPUnit, PHPStan, Rector and Pint) and open a PR. A new optional field, method or enum case is a minor release; a removed or retyped one is major.

## Do not

- Do not type a field from its name. Read its type in the API documentation or the sample in the issue.
- Do not send an explicit null in an import body; leave the field out.
