const fs = require("node:fs"),
  path = require("node:path"),
  vm = require("node:vm"),
  assert = require("node:assert/strict");
const root = path.resolve(__dirname, "..");
process.chdir(root);
const walk = (d) =>
  fs
    .readdirSync(d, { withFileTypes: true })
    .flatMap((e) =>
      e.name.startsWith(".") || e.name === "vendor"
        ? []
        : e.isDirectory()
          ? walk(path.join(d, e.name))
          : [path.join(d, e.name)],
    );
const files = walk(".");
let scripts = 0;
for (const file of files.filter((f) => f.endsWith(".html"))) {
  const html = fs.readFileSync(file, "utf8");
  for (const m of html.matchAll(/(?:href|src)=["']([^"']+)["']/g)) {
    const url = m[1].split(/[?#]/)[0];
    if (!url || /^(?:[a-z]+:|\/|\{)/i.test(url)) continue;
    assert.ok(
      fs.existsSync(path.resolve(path.dirname(file), url)),
      file + " has missing target " + url,
    );
  }
  for (const m of html.matchAll(/<script\b[^>]*>([\s\S]*?)<\/script>/g)) {
    if (m[1].trim()) {
      new vm.Script(m[1], { filename: file });
      scripts++;
    }
  }
}
for (const file of files.filter((f) => f.endsWith(".js"))) {
  new vm.Script(fs.readFileSync(file, "utf8"), { filename: file });
  scripts++;
}
console.log("Local links and " + scripts + " JavaScript blocks/files passed.");
let JSDOM, Parser;
try {
  ({ JSDOM } = require("../.qa/node_modules/jsdom"));
  Parser = require("../.qa/node_modules/php-parser");
} catch {
  console.log(
    "Optional DOM/PHP checks: install jsdom and php-parser under .qa.",
  );
  process.exit(0);
}
function page(file, id = 120, saved = {}) {
  const html = fs.readFileSync(file, "utf8");
  const dom = new JSDOM(html, {
      url: "http://localhost/" + file + "?project=" + id,
      runScripts: "outside-only",
    }),
    w = dom.window;
  Object.entries(saved).forEach(([k, v]) => w.localStorage.setItem(k, v));
  w.alert = () => {};
  w.confirm = () => true;
  w.setInterval = () => 0;
  w.setTimeout = () => 0;
  w.scrollTo = () => {};
  if (w.HTMLDialogElement) {
    w.HTMLDialogElement.prototype.showModal = function () {
      this.setAttribute("open", "");
    };
    w.HTMLDialogElement.prototype.close = function () {
      this.removeAttribute("open");
    };
  }
  for (const script of [...w.document.scripts]) {
    const source = script.getAttribute("src");
    w.eval(
      source
        ? fs.readFileSync(path.resolve(path.dirname(file), source), "utf8")
        : script.textContent,
    );
  }
  return w;
}
const w=page('workspace/scope-of-work.html',119);w.ORDO_STATE.workOrder.status='Completed';w.ORDO_STATE.sowWorkflow.status='draft';w.RSAT_FORM.render();assert.ok(w.document.querySelector('.sidecard #rsatBuilderOpen'));assert.ok(w.document.querySelector('.sidecard #rsatAdd'));assert.ok(w.document.querySelector('.sidecard #rsatPrint'));assert.equal(w.document.querySelector('#rsatForm .rsat-toolbar'),null);w.document.querySelector('#rsatBuilderOpen').click();const b=w.document.querySelector('.rsat-builder-dialog');assert.ok(b.querySelector('.rsat-table'));const n=w.RSAT_FORM.rows().length;const field=b.querySelector('[data-field="activity"]');field.value='Template activity';field.dispatchEvent(new w.Event('change',{bubbles:true}));b.querySelector('#builderTemplateName').value='Test template';b.querySelector('#builderSaveTemplate').click();const saved=JSON.parse(w.localStorage.getItem('ordoRsatTemplatesV1'));assert.equal(saved[0].rows[0].activity,'Template activity');assert.equal(saved[0].rows[0].id,undefined);b.querySelector('#builderTemplate').value=saved[0].id;b.querySelector('#builderLoad').click();assert.equal(w.RSAT_FORM.rows().length,n*2);assert.equal(new Set(w.RSAT_FORM.rows().map(r=>r.id)).size,n*2);w.close();console.log('Full builder edits and reusable template save/load passed.');

