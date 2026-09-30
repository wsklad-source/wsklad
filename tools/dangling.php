<?php
/**
 * Do all the Wsklad classes the release tree *references* actually exist in it?
 *
 * This is the shape of bug that ships: a call into a namespace that arrived with a later
 * release. It is caught if the caller wraps it, which is why it can sit in a tagged
 * release for a while - the request survives, it just quietly does nothing. Found twice
 * already in this audit by accident; this finds the rest on purpose.
 *
 * Resolution is by hand, from the PSR-4 map (src/ -> Wsklad\), because the project has no
 * classmap to lean on.
 */
// The root defaults to the checkout this file lives in, so the gate means the same thing in
// CI and on any machine. The argument stays so the check can be pointed at a tree that is
// known to have a dangling reference - hardcoded, the only thing a run could prove is that
// this project happens to be clean, which looks identical to a check that no longer looks.
$root = rtrim(str_replace('\\', '/', $argv[1] ?? ''), '/');
if ($root === '') { $root = str_replace('\\', '/', dirname(__DIR__)); }
$srcRoot = $root . '/src';
if (!is_dir($srcRoot)) { fwrite(STDERR, "no src/ in: $root\n"); exit(2); }

$declared = [];
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($srcRoot, FilesystemIterator::SKIP_DOTS));
foreach ($it as $f) {
	if (strtolower($f->getExtension()) !== 'php') { continue; }
	$rel = substr($f->getPathname(), strlen($srcRoot) + 1);
	$rel = str_replace('\\', '/', $rel);
	$rel = preg_replace('~\.php$~', '', $rel);
	$declared[] = 'Wsklad\\' . str_replace('/', '\\', $rel);
}
$declared = array_flip($declared);

// Every fully-qualified Wsklad symbol the source mentions.
$refs = [];
foreach ($it as $f) {
	if (strtolower($f->getExtension()) !== 'php') { continue; }
	$t = (string) file_get_contents($f->getPathname());
	$rel = str_replace('\\', '/', substr($f->getPathname(), strlen($root) + 1));

	// Skip the file's own namespace declaration and its own class name.
	preg_match_all('~(?<![\\\\\w])\\\\?(Wsklad\\\\[A-Za-z0-9_\\\\]+)~', $t, $m);
	foreach ($m[1] as $sym) {
		$sym = trim($sym, '\\');
		if (!isset($refs[$sym])) { $refs[$sym] = []; }
		$refs[$sym][] = $rel;
	}
}

$missing = [];
foreach ($refs as $sym => $files) {
	if (isset($declared[$sym])) { continue; }
	// A parent namespace used as a prefix (`Wsklad\Contract\Capabilities` vs a call to
	// `Wsklad\Contract\...`) - only flag the leaf, and only if nothing under it exists.
	$isPrefix = false;
	foreach ($declared as $d => $_) { if (strpos($d, $sym . '\\') === 0) { $isPrefix = true; break; } }
	if ($isPrefix) { continue; }

	// A function, not a type. `Wsklad\core()` is called in wsklad.php; the lower-case last
	// segment is the giveaway, since class names in this tree are StudlyCase. Loading the
	// autoloader would confirm it, but a function reference is not a missing class, and
	// reporting it as one is the kind of noise that gets a check switched off.
	$leaf = substr($sym, strrpos($sym, '\\') + 1);
	if ($leaf !== '' && $leaf[0] === strtolower($leaf[0])) { continue; }

	$missing[$sym] = array_values(array_unique($files));
}

ksort($missing);
echo "=== Wsklad symbols referenced but not present in this tree ===\n\n";
if (!$missing) { echo "  (none)\n"; }
foreach ($missing as $sym => $files) {
	echo "  $sym\n";
	foreach (array_slice($files, 0, 4) as $f) { echo "      used in $f\n"; }
	if (count($files) > 4) { echo '      … and ' . (count($files) - 4) . " more\n"; }
	echo "\n";
}
echo 'missing symbols: ' . count($missing) . "\n";
