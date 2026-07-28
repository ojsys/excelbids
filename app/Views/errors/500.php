<?php
/**
 * Deliberately plain: this page has to render even when the database is down,
 * so it uses no settings lookups and inlines its own styling.
 */
?>
<!DOCTYPE html>
<html lang="en-GB">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Something went wrong</title>
<meta name="robots" content="noindex">
<style>
  body{margin:0;background:#FBFAF5;color:#1B1B17;font-family:'Public Sans',system-ui,sans-serif;
       display:flex;min-height:100vh;align-items:center;justify-content:center;text-align:center;padding:32px;}
  .code{font-family:Georgia,serif;font-size:96px;line-height:1;color:#B23A2E;opacity:.85;}
  h1{font-family:Georgia,serif;font-size:26px;margin:12px 0 0;font-weight:600;}
  p{color:#5B584C;margin-top:12px;max-width:420px;}
  a{display:inline-block;margin-top:26px;background:#B23A2E;color:#fff;padding:13px 24px;
    border-radius:3px;text-decoration:none;font-weight:600;}
</style>
</head>
<body>
<div>
  <div class="code">500</div>
  <h1>Something went wrong at our end.</h1>
  <p>The error has been logged. Please try again in a moment — if it keeps happening, get in touch and quote the time you saw this.</p>
  <a href="/">Back to the homepage</a>
</div>
</body>
</html>
