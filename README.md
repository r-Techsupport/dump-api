# dump-api

Uses WinDBG/CDB to analyze files and paste them.

## Installation
Point your web server to the `public` directory, because the main directory contains sensitive files.

**The `curl` and `openssl` extensions must be enabled to run this, as well as `allow_url_fopen`.**

## API specification
Input:
```json
{
    "key": "string",
    "url": "string starting with $URL_PREFIX"
}
```
Output (success):
```json
{
    "success": true,
    "url": "https://example.com/url.txt"
}
```
Output (failure):

```json
{
	"success": false,
	"error": "Error message"
}
```
