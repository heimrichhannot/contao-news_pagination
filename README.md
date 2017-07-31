# News Pagination

This module offers a character count based pagination for the news reader.

## Features

- split news articles by an adjustable character amount considering html tags
- add a pagination for navigating between the news parts

### Technical instructions

1. Activate "addPagination" in your news reader module and configure the pagination to your needs.
2. Add `<?= $this->newsPagination ?>` to your details template (e.g. "news_full").

### Known limitations

- currently only ce_text is supported for splitting, other elements are removed properly according to current page number though (completely)