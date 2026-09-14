# G2 Cloudflare Cache / WAF Intent Matrix

| Request class | Cache intent | Notes |
|---|---|---|
| hashed CSS/JS | long cache | immutable naming |
| img.beyourlover.com media | long cache | preserve URLs |
| anonymous public HTML | measured cache only | never leak private state |
| /cart* | BYPASS | Woo session |
| /checkout* /order-pay* | BYPASS | payment/private |
| /my-account* | BYPASS | authenticated/private |
| /wp-admin* /wp-login.php | BYPASS | admin/auth |
| /wp-json* | BYPASS by default | APIs/ERP/callbacks |
| Woo wc-ajax/add-to-cart | BYPASS | cart mutation |
| provider callbacks/webhooks | BYPASS | must reach origin |
| authenticated requests | BYPASS | private state |
| Woo cart/session cookies | BYPASS | private cart state |

Exact Cloudflare rule syntax is frozen only after current production rules are exported.
