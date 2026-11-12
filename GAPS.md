# Known API limitations

Places where the hihaho v2 API does not do what you might expect, and how the SDK handles each one.

| API behaviour | What the SDK does |
|---|---|
| The question import drops the question group and its order | Import input leaves them out |
| The subtitle batch store is not a transaction and returns no ids | `video($id)->subtitles()->createMany()` documents it; re-read the list to get ids |
