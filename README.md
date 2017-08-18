# News Pagination

This module offers manual content pagination for the news reader.

## Features

- split news content by wrapping the content elements in special start and stop content elements
- add a pagination for navigating between the news parts

### Technical instructions

1. Activate "addPagination" in your news reader module.
2. Add `<?= $this->newsPagination ?>` to your details template (e.g. "news_full").