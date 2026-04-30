
$url = "https://tracking-godcoder6999.aws-us-west-2.turso.io"
$token = (Get-Content token.txt).Trim()

$body = "{`"requests`": [{`"type`": `"execute`", `"stmt`": {`"sql`": `"SELECT name FROM sqlite_master WHERE type='table';`"}}, {`"type`": `"close`"}]}"

Invoke-RestMethod -Uri "$url/v2/pipeline" -Method Post -Headers @{ "Authorization" = "Bearer $token"; "Content-Type" = "application/json" } -Body $body | ConvertTo-Json -Depth 10

