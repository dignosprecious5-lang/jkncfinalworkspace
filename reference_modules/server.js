const http = require("node:http");
const fs = require("node:fs");
const path = require("node:path");

const mimeTypes = {
  ".html": "text/html; charset=utf-8",
  ".js": "text/javascript; charset=utf-8",
  ".css": "text/css; charset=utf-8",
  ".json": "application/json",
  ".svg": "image/svg+xml",
  ".png": "image/png",
  ".jpg": "image/jpeg",
  ".ico": "image/x-icon"
};

function createStaticServer(rootDir, port, name) {
  if (!fs.existsSync(rootDir)) {
    console.warn(`[${name}] Warning: Directory does not exist: ${rootDir}`);
    return null;
  }

  const server = http.createServer((req, res) => {
    let pathname;
    try {
      pathname = decodeURIComponent(new URL(req.url, `http://localhost:${port}`).pathname);
    } catch {
      res.writeHead(400).end();
      return;
    }

    let filePath = path.resolve(rootDir, "." + pathname + (pathname.endsWith("/") ? "index.html" : ""));

    if (!filePath.startsWith(rootDir) || filePath.includes(path.sep + "laravel-app" + path.sep)) {
      res.writeHead(403).end("Forbidden");
      return;
    }

    fs.stat(filePath, (err, stats) => {
      if (err) {
        res.writeHead(404, { "Content-Type": "text/plain; charset=utf-8" }).end("404 Not Found");
        return;
      }

      if (stats.isDirectory()) {
        filePath = path.join(filePath, "index.html");
      }

      fs.readFile(filePath, (error, body) => {
        if (error) {
          res.writeHead(404, { "Content-Type": "text/plain; charset=utf-8" }).end("Not found");
          return;
        }
        const ext = path.extname(filePath).toLowerCase();
        res.writeHead(200, {
          "Content-Type": mimeTypes[ext] || "application/octet-stream",
          "Cache-Control": "no-cache"
        });
        res.end(body);
      });
    });
  });

  server.listen(port, "127.0.0.1", () => {
    console.log(`[${name}] UI/UX Live Server running at: http://127.0.0.1:${port}`);
  });

  return server;
}

// Support running from root, send to precious, or reference_modules
function findModuleDir(candidates) {
  for (const candidate of candidates) {
    if (fs.existsSync(candidate)) return candidate;
  }
  return candidates[0];
}

const projectRoot = findModuleDir([
  path.join(__dirname, "ORDO_Project_V1_Module", "ORDO_Project_V4_Modular"),
  path.join(__dirname, "send to precious", "ORDO_Project_V1_Module", "ORDO_Project_V4_Modular"),
  path.join(__dirname, "reference_modules", "ORDO_Project_V1_Module", "ORDO_Project_V4_Modular")
]);

const regularRoot = findModuleDir([
  path.join(__dirname, "ORDO_REGULAR_V1_Module", "ORDO_Regular_V4_Modular"),
  path.join(__dirname, "send to precious", "ORDO_REGULAR_V1_Module", "ORDO_Regular_V4_Modular"),
  path.join(__dirname, "reference_modules", "ORDO_REGULAR_V1_Module", "ORDO_Regular_V4_Modular")
]);

const mainRoot = __dirname;

// Start servers
createStaticServer(projectRoot, 8080, "ORDO Project");
createStaticServer(regularRoot, 8081, "ORDO Regular");
createStaticServer(mainRoot, 3000, "ORDO Portal / Hub");
