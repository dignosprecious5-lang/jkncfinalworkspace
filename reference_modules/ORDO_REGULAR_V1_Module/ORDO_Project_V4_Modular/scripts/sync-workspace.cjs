const fs = require("node:fs"),
  path = require("node:path");
const root = path.resolve(__dirname, "..", "workspace");
const sources = path.join(root, "_sources");
const canonicalPages = [
  "project-dashboard.html",
  "work-order.html",
  "scope-of-work.html",
  "review.html",
  "ntp.html",
  "execution.html",
  "attachment.html",
  "sow-report.html",
  "delivery-completion.html",
  "history.html",
];
for (const [source, target] of [
  [
    "scope-of-work-builder-v10-functional-modern-fixed.html",
    "scope-of-work.html",
  ],
  ["project-review-v2-percentage-lifecycle.html", "review.html"],
  ["project-ntp-v2-change-package-sop-functional.html", "ntp.html"],
]) {
    let content = fs
      .readFileSync(path.join(sources, source), "utf8")
      .replaceAll("../../", "../");
    for (const page of canonicalPages)
      content = content.replaceAll(`href="../${page}"`, `href="${page}"`);
    fs.writeFileSync(path.join(root, target), content);
  console.log(source + " → " + target);
}
