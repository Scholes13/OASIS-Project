# DocsHelp article data

This folder splits help articles by category.

## Add an article

1. Pick matching category file, for example `purchase-request.ts`.
2. Add one `Article` object to that file.
3. Keep `category` equal to category key in `../categories.ts`.
4. Keep object formatting multi-line.
5. Do not edit `../articles.ts` unless adding a brand-new category file.

## Add a new category

1. Add category metadata in `../categories.ts`.
2. Create `articles/<category-key>.ts`.
3. Export `<PascalCaseCategory>Articles`.
4. Import and spread that array in `../articles.ts`.

## Changelog notes

Large changelog articles live under `articles/changelog/`.
`changelog.ts` aggregates those single-article files so each file stays small.

## Contract

`../articles.ts` still exports `articles: Article[]`.
Consumers should keep importing from `./data` or `./data/articles`.
