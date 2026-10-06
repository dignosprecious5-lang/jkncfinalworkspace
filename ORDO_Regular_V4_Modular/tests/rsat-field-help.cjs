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
const w=page('workspace/scope-of-work.html',119);w.ORDO_STATE.workOrder.status='Completed';w.ORDO_STATE.sowWorkflow.status='draft';w.RSAT_FORM.render();w.document.querySelector('[data-edit]').click();const dialog=w.document.querySelector('.rsat-rule-dialog:not(.rsat-builder-dialog)');
function check(){for(const field of dialog.querySelectorAll('[data-path]')){const label=field.closest('label'),button=label.querySelector('.rsat-field-info'),help=label.querySelector('.rsat-field-explanation');assert.ok(button,field.dataset.path);assert.ok(help.textContent.length>40);button.click();assert.equal(help.hidden,false);assert.equal(button.getAttribute('aria-expanded'),'true');button.click();assert.equal(help.hidden,true);}}
check();for(const unit of ['week','quarter','year','event','day','month']){const select=dialog.querySelector('[data-path="frequency.unit"]');select.value=unit;select.dispatchEvent(new w.Event('change',{bubbles:true}));check();}for(const type of ['nth_weekday','period_end','event']){const select=dialog.querySelector('[data-path="deadline.type"]');select.value=type;select.dispatchEvent(new w.Event('change',{bubbles:true}));check();}w.close();console.log('Every scheduling field has working accessible help across all frequency and deadline variants.');

