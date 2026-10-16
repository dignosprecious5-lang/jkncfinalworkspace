/**
 * Guards the Alpine components in resources/js/app.js.
 *
 * These exist because a page can be completely blank while every PHP test
 * passes. Every PHPUnit assertion here reads the server-rendered HTML, and a
 * row hidden by x-show is still present in that HTML — so a component whose
 * expression throws is invisible to the whole PHP suite. That is not
 * hypothetical: `classificationBrowser` dropped its `scheme` argument, which
 * made `textFor()` throw, which made x-show hide all thirty-one scheme rows on
 * the Classifications page. Four hundred and fifty-six green tests, blank page.
 *
 * So these tests execute the real component factories instead of asserting on
 * markup. `node --test` is built into Node, which the build already requires, so
 * this adds no dependency.
 *
 * Run with: npm run test:js
 */

import { test, describe, before } from "node:test";
import assert from "node:assert/strict";
import { readFileSync } from "node:fs";
import { fileURLToPath } from "node:url";
import { dirname, resolve } from "node:path";

const root = resolve(dirname(fileURLToPath(import.meta.url)), "../..");

/** The component factories, captured from the real app.js. */
let components = {};

/**
 * Loads resources/js/app.js and captures what it registers.
 *
 * Only the browser bootstrap is removed: the module import, the window
 * assignment, and the start call. Everything else runs untouched, so the
 * factories under test are the ones the browser actually receives.
 */
function loadComponents() {
    let source = readFileSync(resolve(root, "resources/js/app.js"), "utf8");

    source = source
        .replace(/^\s*import\s+Alpine.*$/m, "")
        .replace(/window\.Alpine\s*=\s*Alpine;?/m, "")
        .replace(/Alpine\.start\(\);?/g, "");

    const registered = {};

    const Alpine = {
        data: (name, factory) => {
            registered[name] = factory;
        },
        // Real stores are registered at load time; an empty object satisfies them.
        store: () => ({}),
        start: () => {},
    };

    // eslint-disable-next-line no-new-func
    new Function("Alpine", "window", source)(Alpine, {});

    return registered;
}

/** The scheme payload the Classifications controller sends. */
const scheme = [
    { code: "301", name: "Entity & Ownership", text: "301 Entity & Ownership Legal identity, ownership and governance records." },
    { code: "401", name: "BIR & Tax", text: "401 BIR & Tax Tax records." },
    { code: "801", name: "Banking", text: "801 Banking Bank records." },
];

before(() => {
    components = loadComponents();
});

describe("classificationBrowser", () => {
    /**
     * The payload must reach the component as a property.
     *
     * It arrives as a factory argument, which is captured by the closure. Every
     * method here reads it back as `this.scheme`, so without the property the
     * argument is unreachable and each of these throws.
     */
    test("binds the scheme it was given", () => {
        const browser = components.classificationBrowser(scheme);

        assert.equal(browser.scheme, scheme);
    });

    test("reads a classification block back by code", () => {
        const browser = components.classificationBrowser(scheme);

        assert.equal(browser.textFor("301"), scheme[0].text);
        assert.equal(browser.parentName("401"), "BIR & Tax");
    });

    test("an unknown code is an empty block, not an error", () => {
        const browser = components.classificationBrowser(scheme);

        assert.equal(browser.textFor("999"), "");
        assert.equal(browser.parentName("999"), "");
    });

    /**
     * The search filters the whole repository, so a change that reads outside it
     * would silently narrow or widen every screen that uses it.
     */
    test("counts the blocks a search term matches", () => {
        const browser = components.classificationBrowser(scheme);

        assert.equal(browser.visibleCount, 3);

        browser.query = 'tax';
        assert.equal(browser.visibleCount, 1);

        browser.query = 'nothing matches this';
        assert.equal(browser.visibleCount, 0);
    });

    /**
     * This is the exact expression every scheme row is wrapped in:
     * x-show="matches(textFor('<code>'))".
     *
     * While the expression threw, Alpine read the failure as falsy and hid every
     * row on the page. Asserting it per code is what proves the rows are shown.
     */
    test("every block is visible with no search term", () => {
        const browser = components.classificationBrowser(scheme);

        for (const entry of scheme) {
            assert.equal(
                browser.matches(browser.textFor(entry.code)),
                true,
                `block ${entry.code} would be hidden`,
            );
        }
    });

    test("a block with no scheme on file still shows rather than blanking the page", () => {
        // The fail-open guarantee: a component that cannot search should fall
        // back to showing everything, never to hiding it.
        const browser = components.classificationBrowser();

        assert.equal(browser.textFor("301"), "");
        assert.equal(browser.matches(browser.textFor("301")), true);
    });
});

/**
 * Both preview drawers used to declare `open` twice: once as their visibility
 * state and once as the method the page calls to open them.
 *
 * A duplicate key in an object literal means the later one wins, so the method
 * displaced the state. The first `this.open = true` then wrote a boolean over the
 * method, and every subsequent x-on:ordo:*-open call threw
 * "open is not a function".
 *
 * The symptom was a drawer that worked exactly once per page load: open a file,
 * close it, and no other file would open again. Every PHPUnit test passed,
 * because the markup is correct and only the runtime expression fails — which is
 * precisely the class of defect this file exists to catch.
 */
describe("a preview drawer survives being reopened", () => {
    /**
     * @param {string} name   the component under test
     * @param {Array}  items  two records, so there is a second one to open
     */
    const reopenCycle = (name, items) => {
        const drawer = components[name](items, "", "", "");

        // The root cause, stated directly: `open` is the visibility state and must
        // be a boolean from the moment the component is built. While a method of
        // the same name sat in the literal, this was a function, and the first
        // assignment to `open` below replaced that method with a boolean.
        assert.equal(
            typeof drawer.open,
            "boolean",
            `${name}.open is ${typeof drawer.open}; it must be the visibility state, not a method`,
        );

        drawer.show("1");
        assert.equal(drawer.open, true, "the first open did not take");
        assert.equal(drawer.current.id, "1");

        drawer.close();
        assert.equal(drawer.open, false, "the drawer did not close");

        // The regression: this threw TypeError while the method was shadowed.
        drawer.show("2");
        assert.equal(drawer.open, true, "the drawer would not open a second time");
        assert.equal(drawer.current.id, "2", "the second open kept the first document");
    };

    test("recordDrawer opens, closes and opens a different record", () => {
        reopenCycle("recordDrawer", [
            { id: "1", title: "First", files: [] },
            { id: "2", title: "Second", files: [] },
        ]);
    });

    test("classifyDrawer opens, closes and opens a different item", () => {
        reopenCycle("classifyDrawer", [
            { id: "1", title: "First", files: [{ id: "f1" }] },
            { id: "2", title: "Second", files: [{ id: "f2" }] },
        ]);
    });

    /**
     * "Classify & Next" closes the drawer from inside the submit path rather than
     * through close(), and it was one of the three ways a reader ended up locked
     * out. It still has to leave the drawer openable.
     */
    test("classifyDrawer is openable again after Classify & Next", () => {
        const items = [
            { id: "1", files: [] },
            { id: "2", files: [] },
        ];

        const drawer = components.classifyDrawer(items, "", "", "");
        drawer.show("1");

        // What submitThenOpen does to visibility once the form posts.
        drawer.open = false;

        drawer.show("2");
        assert.equal(drawer.open, true, "the drawer was left unusable by Classify & Next");
        assert.equal(drawer.current.id, "2");
    });

    /**
     * The structural guard, so the collision cannot be reintroduced in a component
     * that has no behavioural test yet.
     *
     * A factory whose returned object holds a key twice means the later definition
     * silently won. Here that always means a method shadowed by state.
     */
    test("no component declares the same key twice", () => {
        const offenders = [];

        for (const [name, factory] of Object.entries(components)) {
            // Read from the literal rather than from a built instance: a duplicate
            // is invisible in the object, because by then the later definition has
            // already won. Only the factory source can still show both.
            const declared = [...factory.toString().matchAll(/^[ \t]{4}([A-Za-z_$][\w$]*)[ \t]*[:(]/gm)]
                .map((match) => match[1]);

            const seen = new Set();
            const duplicates = declared.filter((key) => (seen.has(key) ? true : (seen.add(key), false)));

            if (duplicates.length > 0) {
                offenders.push(`${name}: declares ${duplicates.join(", ")} more than once`);
            }
        }

        assert.deepEqual(offenders, [], `\n  ${offenders.join("\n  ")}\n`);
    });
});

/**
 * The Unclassified Records dropzone's camera action.
 *
 * A captured photo never touches the file input: queueUpload builds its FormData
 * by hand, unlike the dedicated intake form which seeds an input and lets the
 * browser post it. So acceptPhoto() is the only thing that gets a photograph into
 * the queue, and its one job is to declare the capture source.
 *
 * That matters because a photograph is a single file. Left to the count-based
 * inference the picker and the drop share, it would be recorded as a plain
 * `upload` — indistinguishable from a dragged JPEG, which is exactly the
 * distinction CaptureSource exists to keep.
 *
 * None of this is reachable from a PHPUnit assertion: the handler only runs on a
 * real shutter press on a real device.
 */
describe("queueUpload accepts a captured photo", () => {
    /**
     * Builds an instance with the one thing Alpine injects that send() touches.
     *
     * `$refs` only exists on a live Alpine root, and send()'s finally block reads
     * `$refs.input` to clear the picker. Without it the post-assertion cleanup
     * throws after the test has already finished.
     */
    const buildQueue = (url = "/records/intake") =>
        Object.assign(components.queueUpload(url), { $refs: {} });

    /**
     * Runs a callback with the two browser globals send() reaches for stubbed.
     *
     * Node has a fetch but no document, and the real fetch would need a server.
     * The stub answers 500 on purpose: that path sets `error` and returns without
     * reaching window.location.reload(), which does not exist here.
     *
     * The callback is awaited and then the event loop is drained, because send()
     * is async and acceptPhoto() deliberately does not await it. Restoring the
     * globals before send() settles would leave its cleanup running against a
     * half-stubbed world.
     *
     * @param {(sent: Array<{url: string, body: FormData}>) => void} callback
     */
    const withStubs = async (callback) => {
        const previousDocument = globalThis.document;
        const previousFetch = globalThis.fetch;
        const sent = [];

        globalThis.document = { querySelector: () => ({ content: "test-token" }) };
        globalThis.fetch = async (url, options) => {
            sent.push({ url, body: options.body });

            return { ok: false, status: 500, json: async () => ({ errors: {} }) };
        };

        try {
            await callback(sent);

            // Let every send() started by the callback run to completion.
            await new Promise((resolve) => setImmediate(resolve));
        } finally {
            globalThis.document = previousDocument;
            globalThis.fetch = previousFetch;
        }
    };

    test("a captured photo is posted as a camera capture", async () => {
        await withStubs((sent) => {
            const queue = buildQueue();
            const photo = new Blob(["jpeg bytes"], { type: "image/jpeg" });

            queue.acceptPhoto(photo);

            assert.equal(sent.length, 1, "the photo was not posted");
            assert.equal(sent[0].url, "/records/intake");

            const body = sent[0].body;
            assert.equal(body.get("capture_source"), "camera");
            assert.equal(body.getAll("files[]").length, 1, "the photo was not sent as a file");
        });
    });

    test("a photo is shown as pending while it uploads", async () => {
        await withStubs(() => {
            const queue = buildQueue();
            const photo = new Blob(["jpeg bytes"], { type: "image/jpeg" });

            queue.acceptPhoto(photo);

            // By identity: the dropzone lists whatever it was handed, and a
            // camera photo has no name to show, unlike a picked file.
            assert.equal(queue.pending.length, 1, "the pending list did not show the captured photo");
            assert.equal(queue.pending[0], photo);
            assert.equal(queue.busy, true, "the upload was not marked in flight");
            assert.equal(queue.error, "", "a stale error was left on the component");
        });
    });

    test("a payload with no photo is ignored rather than posted empty", async () => {
        await withStubs((sent) => {
            const queue = buildQueue();

            queue.acceptPhoto(undefined);

            assert.equal(sent.length, 0, "an empty capture was posted");
            assert.deepEqual(queue.pending, []);
        });
    });

    /**
     * The source parameter is new, and it defaults to null. If that default ever
     * changes, the picker and the drop silently start reporting themselves as
     * camera captures.
     */
    test("the picker and the drop still infer their own capture source", async () => {
        await withStubs((sent) => {
            const queue = buildQueue();

            queue.read([new Blob(["a"]), new Blob(["b"])]);
            assert.equal(sent[0].body.get("capture_source"), "multi_upload");

            queue.read([new Blob(["a"])]);
            assert.equal(sent[1].body.get("capture_source"), "upload");
        });
    });

    /**
     * A failed post has to clear the busy flag and the picker, or the dropzone is
     * stuck showing "Uploading…" with no way to try again.
     */
    test("a rejected photo leaves the dropzone usable", async () => {
        const queue = buildQueue();
        const input = { value: "stale" };
        queue.$refs = { input };

        await withStubs(() => {
            queue.acceptPhoto(new Blob(["jpeg bytes"], { type: "image/jpeg" }));
        });

        // Asserted after the drain, because send() only reaches its error branch
        // once it has resumed from the await on the fetch.
        assert.match(queue.error, /could not be uploaded/);
        assert.equal(queue.busy, false, "the dropzone is stuck showing Uploading…");
        assert.deepEqual(queue.pending, [], "the rejected photo stayed in the pending list");
        assert.equal(input.value, "", "the picker kept its old value, so a retry would be a no-op");
    });
});

/**
 * currentMaster() — the browser's half of "which file does this preview show".
 *
 * The server has its own copy (RecordFile::scopeCurrentMasterFirst). While the
 * two disagreed, a record with any attachment rendered a pane labelled with the
 * master's name, pointed at a URL streaming a supporting file, and showed nothing.
 *
 * These assert the ordering rule itself rather than any one payload, because the
 * damage was in the choice and not in how it was rendered.
 */
describe("currentMaster", () => {
    /** Exposes the module-scope helper through a factory that reads it. */
    const build = (files) => {
        const drawer = components.recordDrawer(
            [{ id: "r1", files }],
            "", "", "", "", "", "",
        );

        drawer.current = { id: "r1", files };

        return drawer.master;
    };

    test("prefers the master over a supporting file, whatever the order", () => {
        const master = { id: "m1", fileRole: "master", version: 1 };

        assert.equal(build([master, { id: "s1", fileRole: "supporting", version: 9 }]), master);
        assert.equal(build([{ id: "s1", fileRole: "supporting", version: 9 }, master]), master);
    });

    /**
     * The trap the server fell into, restated for the browser: a supporting file
     * with the highest version number on the record must still lose.
     */
    test("a supporting file with the highest version never wins", () => {
        const current = { id: "m2", fileRole: "master", version: 3 };

        assert.equal(build([
            current,
            { id: "s1", fileRole: "supporting", version: 4 },
        ]), current);
    });

    test("prefers the current version of the master", () => {
        const current = { id: "m3", fileRole: "master", version: 5 };

        assert.equal(build([
            { id: "m1", fileRole: "master", version: 1 },
            current,
            { id: "m2", fileRole: "master", version: 2 },
        ]), current);
    });

    test("ties break on the oldest id, so the choice is stable", () => {
        const first = { id: "aaa", fileRole: "master", version: 1 };

        assert.equal(build([
            { id: "zzz", fileRole: "master", version: 1 },
            first,
        ]), first);
    });

    test("a record with no master still previews something", () => {
        const only = { id: "s1", fileRole: "supporting", version: 1 };

        assert.equal(build([only]), only);
    });

    test("a record with no files at all has no preview", () => {
        assert.equal(build([]), null);
    });
});

/**
 * A factory argument read as `this.<name>` must also be a property, or the
 * expression throws wherever it is used. Read as a bare `<name>` it is captured
 * by the closure at factory time, which is fine.
 *
 * This is the rule that separates the one real defect from the three components
 * that drop an argument harmlessly.
 */
describe("every component binds the arguments it reads off `this`", () => {
    test("no argument is read through `this` without being a property", () => {
        const offenders = [];

        for (const [name, factory] of Object.entries(components)) {
            const source = factory.toString();
            const declared = (source.match(/\(([^)]*)\)\s*=>/) ?? [, ""])[1]
                .split(",")
                .map((part) => part.trim())
                .filter(Boolean);

            if (declared.length === 0) {
                continue;
            }

            // Arrays satisfy the .map/.filter some factories call on their
            // argument while building, so the factory still constructs.
            let instance;
            try {
                instance = factory(...declared.map(() => []));
            } catch (error) {
                offenders.push(`${name}: factory threw with a test payload (${error.message})`);
                continue;
            }

            for (const argument of declared) {
                const readThroughThis = new RegExp(`this\\.${argument}\\b`).test(source);

                if (readThroughThis && !(argument in instance)) {
                    offenders.push(`${name}: reads this.${argument} but never binds ${argument}`);
                }
            }
        }

        assert.deepEqual(offenders, [], `\n  ${offenders.join("\n  ")}\n`);
    });
});
