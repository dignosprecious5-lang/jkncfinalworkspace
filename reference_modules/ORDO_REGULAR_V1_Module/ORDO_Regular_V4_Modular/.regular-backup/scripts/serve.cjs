const http = require("node:http"),
  fs = require("node:fs"),
  path = require("node:path");
const root = path.resolve(__dirname, "..");
http
  .createServer((req, res) => {
    let pathname;
    try {
      pathname = decodeURIComponent(
        new URL(req.url, "http://localhost").pathname,
      );
    } catch {
      res.writeHead(400).end();
      return;
    }
    const file = path.resolve(
      root,
      "." + pathname + (pathname.endsWith("/") ? "index.html" : ""),
    );
    if (
      !file.startsWith(root + path.sep) ||
      file.includes(path.sep + "laravel-app" + path.sep) ||
      ![".html", ".js", ".css", ".json"].includes(path.extname(file))
    ) {
      res.writeHead(403).end();
      return;
    }
    fs.readFile(file, (error, body) => {
      if (error) {
        res.writeHead(404).end("Not found");
        return;
      }
      res.setHeader(
        "Content-Type",
        {
          ".html": "text/html; charset=utf-8",
          ".js": "text/javascript; charset=utf-8",
          ".css": "text/css; charset=utf-8",
          ".json": "application/json",
        }[path.extname(file)],
      );
      res.end(body);
    });
  })
  .listen(8080, "127.0.0.1", () =>
    console.log("ORDO prototype: http://127.0.0.1:8080"),
  );
