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
const w=page('workspace/scope-of-work.html',119);w.ORDO_STATE.workOrder.status='Completed';w.ORDO_STATE.sowWorkflow.status='draft';w.RSAT_FORM.render();
const click=(i,a)=>w.document.querySelectorAll('.rsat-actions-cell')[i].querySelector('[data-action="'+a+'"]').click();
assert.equal(w.document.querySelector('.rsat-actions-cell').querySelectorAll('button').length,5);assert.equal(w.document.querySelectorAll('[data-kind="service"],[data-kind="activity"]').length,0);
const n=w.RSAT_FORM.rows().length,first=w.RSAT_FORM.rows()[0].id;click(0,'duplicate');assert.equal(w.RSAT_FORM.rows().length,n+1);assert.notEqual(w.RSAT_FORM.rows()[1].id,first);click(0,'down');assert.equal(w.RSAT_FORM.rows()[1].id,first);click(1,'up');assert.equal(w.RSAT_FORM.rows()[0].id,first);click(0,'add');assert.equal(w.RSAT_FORM.rows()[1].activity,'');click(1,'remove');assert.equal(w.RSAT_FORM.rows().length,n+1);
w.document.querySelector('#rsatAdd').click();const last=w.RSAT_FORM.rows().length-1,id=w.RSAT_FORM.rows()[last].id;click(last,'up');assert.equal(w.RSAT_FORM.rows()[last-1].id,id);assert.deepEqual(w.RSAT_ADAPTER.read().map(r=>r.id),w.RSAT_FORM.rows().map(r=>r.id));w.document.getElementById('rsatBuilderOpen').click();const builder=w.document.querySelector('.rsat-builder-dialog');builder.querySelector('#builderService').value='Compliance';builder.querySelector('#builderActivities').value=['Prepare report','Submit report'].join(String.fromCharCode(10));builder.querySelector('form').dispatchEvent(new w.Event('submit',{bubbles:true,cancelable:true}));assert.equal(w.RSAT_FORM.rows().filter(r=>r.service==='Compliance').length,2);assert.equal(w.ORDO_STATE.streams.find(r=>r.name==='Compliance').tasks.length,2);w.close();console.log('Five row actions, single-row changes, and saved ordering across services passed.');


