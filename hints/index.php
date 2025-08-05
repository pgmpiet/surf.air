<?php
// index.php
header('Content-Type: text/html; charset=utf-8');

// sanitize & pick slashy file
$file = basename($_GET['file'] ?? 'slashy.txt');
$path = __DIR__ . "/{$file}";
if (!is_readable($path)) {
    http_response_code(404);
    exit("Slashy file not found.");
}

// read slashy
$slashy = file_get_contents($path);
?>
<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8">
    <title>Dynamic Slashy Quine: <?= htmlspecialchars($file) ?></title>
    <style> body { font-family: monospace; white-space: pre-wrap } </style>
  </head>
  <body>

    <!-- raw slashy definition -->
    <script id="slashy" type="text/plain"><?= htmlspecialchars($slashy) ?></script>

    <!-- parser + quine -->
    <script>
    (function(){
      // 1. Grab lines
      var lines = document
        .getElementById('slashy')
        .textContent
        .split(/\r?\n/)
        .filter(function(l){ return !!l.trim(); });

      // 2. Prepare stack and root
      var root = document.createDocumentFragment();
      var stack = [{node: root, indent: -1}];

      // 3. Process slashy lines
      lines.forEach(function(line){
        var parts = line.match(/^(\s*)(.*)$/);
        var indent = parts[1].length;
        var text   = parts[2];

        // raw text block
        if (text.indexOf('| ') === 0) {
          var txt = document.createTextNode(text.slice(2) + "\n");
          stack[stack.length - 1].node.appendChild(txt);
          return;
        }

        // pop until correct level
        while (stack[stack.length - 1].indent >= indent) {
          stack.pop();
        }

        // parse tag + attrs
        var segs = text.split(/\s+/);
        var tag  = segs[0].replace(/^\//, '');
        var attr = segs.slice(1).join(' ');
        var el   = document.createElement(tag);

        // set attributes via regex
        attr.replace(/(\w+)=(["'])(.*?)\2/g, function(_,k,_,v){
          el.setAttribute(k, v);
        });

        // append and push
        stack[stack.length - 1].node.appendChild(el);
        stack.push({ node: el, indent: indent });
      });

      // 4. Mount the built tree
      document.body.appendChild(root);

      // 5. Quine: show full HTML
      var pre = document.createElement('pre');
      pre.textContent = document.documentElement.outerHTML;
      document.body.appendChild(pre);
    })();
    </script>

  </body>
</html>
