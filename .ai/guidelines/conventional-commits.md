## Conventional Commits

Every PR title is a [Conventional Commit](https://www.conventionalcommits.org): `type(scope)!: summary`.
release-please reads the commits on `main` to choose the next version and to write `CHANGELOG.md`.
A title it cannot parse never reaches a release or the changelog.

- **The PR title is what lands.** A PR is squash-merged with its title as the commit subject, and
  the `conventional-title` check fails a title that does not parse. Branch commit subjects do not
  land on `main`. Write the title for the integrator who reads the changelog.
- **Type.** `feat`: a new resource method, parameter, input field, read-model property, enum case
  or exception an integrator can use. `fix`: a defect an integrator can see. `feat`, `fix`, `perf`
  and `revert` make a release; `refactor`, `docs`, `style`, `test`, `build`, `ci` and `chore` do
  not (`release-please-config.json` hides them). A change to the tests, the workbench or the
  tooling only does not ship in the package, so it is never `feat`, `fix` or `perf`.
- **Scope** is optional: the resource or area in kebab-case, for example `fix(uploads): …`.
- **Breaking.** A change that makes an integrator edit their code is breaking: a removed or renamed
  method, parameter, property or enum case, a changed return or parameter type, a new required
  parameter, or a changed exception. `PUBLIC_API.md` lists what counts. Mark it with `!` in the
  title: `feat(videos)!: …`. Below 1.0 a breaking change bumps the minor version (0.1.0), and
  `feat` and `fix` bump the patch (0.0.2).
- **Commit bodies still count.** The squash body keeps the branch's commit messages, and
  release-please parses it. A `BREAKING CHANGE:` paragraph anywhere makes the release breaking. A
  last paragraph that starts with `type: …` adds a changelog entry. Do not end a commit message
  with such a line.
- **Never tag or publish a release by hand.** Merging the release-please PR
  (`chore(main): release x.y.z`) tags the release and writes the changelog.
