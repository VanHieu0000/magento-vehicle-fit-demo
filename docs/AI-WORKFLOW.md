# How AI was used on this module

Tools: Claude (code drafting, review, docs), ChatGPT (second opinion).
Rule: AI drafts, I decide, I test. No credentials or customer data go into any AI tool.

## Decisions changed during review

| # | First idea | Problem found | Final version |
|---|---|---|---|
| 1 | Keep the chosen vehicle in customer session | Starts a session for every guest -> Set-Cookie on category pages -> FPC/CDN miss | Vehicle in URL `?vehicle=ID` + localStorage for the badge |
| 2 | Show "fits / not fits" in product page HTML | Page would differ per visitor, can't be cached | Same HTML for all, badge filled by small ajax call |
| 3 | `addIdFilter($ids)` | Empty array -> no filter at all, customer sees every part | `$ids ?: [0]` -> empty list when nothing fits |
| 4 | Admin grid with `filterSearch` | Needs a fulltext index the table doesn't have -> grid error | Removed, column filters only |
| 5 | CLI command gets importer directly | `bin/magento list` would build DB deps for every command | Proxy in `etc/di.xml` |
| 6 | Clean only app cache after import | Category pages stay old in Varnish/CDN | `clean_cache_by_tags` + block `IdentityInterface` |
| 7 | One SKU lookup per CSV row | 10k rows = 10k queries | One `getProductsIdsBySkus()` call, links inserted in chunks of 1000 |

## Checks before calling it done

- [ ] `setup:upgrade` + `setup:di:compile` with no errors on 2.4.7
- [ ] Unit tests green
- [ ] Import sample CSV twice -> same counts, no duplicates
- [ ] Category page: second request is HIT, no Set-Cookie for guest
- [ ] Badge shows correct text for fit / not fit
- [ ] Admin user without `Hieu_VehicleFit::import` cannot open import page
