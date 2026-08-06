<?php
/**
 * Deterministic File 08 development/staging candidate builder.
 */

declare(strict_types=1);

final class File08_Candidate_Builder {
	private const ROOT = 'worldwide-clinic';
	private const MAX_FILE_BYTES = 5_000_000;
	private const MAX_TOTAL_BYTES = 25_000_000;

	/** @return list<string> */
	public static function payload(): array {
		return array(
			'worldwide-clinic.php',
			'uninstall.php',
			'readme.txt',
			'assets/css/admin.css',
			'assets/css/clinic.css',
			'assets/js/clinic.js',
			'includes/class-swc-activator.php',
			'includes/class-swc-admin.php',
			'includes/class-swc-appointments.php',
			'includes/class-swc-cf01-care-context.php',
			'includes/class-swc-doctor-authority.php',
			'includes/class-swc-frontend.php',
			'includes/class-swc-helpers.php',
			'includes/class-swc-plugin.php',
			'includes/class-swc-privacy.php',
			'includes/class-swc-public-clinic.php',
		);
	}

	/** @return array<string,mixed> */
	public static function build(string $root, string $output, string $commit, int $epoch): array {
		if (! class_exists('ZipArchive')) {
			throw new RuntimeException('PHP ZipArchive is required.');
		}
		$root = realpath($root) ?: '';
		if ($root === '' || ! is_dir($root) || is_link($root)) {
			throw new RuntimeException('Repository root is invalid.');
		}
		if (preg_match('/^[a-f0-9]{40}$/', $commit) !== 1) {
			throw new InvalidArgumentException('Commit SHA must contain exactly 40 lowercase hexadecimal characters.');
		}
		if ($epoch < 315532800 || $epoch > 4102444800) {
			throw new InvalidArgumentException('SOURCE_DATE_EPOCH is outside the permitted range.');
		}

		$version = self::runtime_version($root . '/worldwide-clinic.php');
		$output = self::prepare_output($root, $output);
		$files = array();
		$total = 0;
		foreach (self::payload() as $relative) {
			if (! self::safe_relative($relative)) {
				throw new RuntimeException('Unsafe payload path: ' . $relative);
			}
			$path = $root . '/' . $relative;
			$real = realpath($path);
			if (! is_string($real) || ! is_file($real) || is_link($path) || ! str_starts_with($real, $root . DIRECTORY_SEPARATOR)) {
				throw new RuntimeException('Missing or unsafe payload file: ' . $relative);
			}
			$bytes = filesize($real);
			if (! is_int($bytes) || $bytes < 0 || $bytes > self::MAX_FILE_BYTES) {
				throw new RuntimeException('Invalid payload size: ' . $relative);
			}
			$total += $bytes;
			if ($total > self::MAX_TOTAL_BYTES) {
				throw new RuntimeException('Candidate payload exceeds total size limit.');
			}
			$hash = hash_file('sha256', $real);
			if (! is_string($hash)) {
				throw new RuntimeException('Unable to hash payload file: ' . $relative);
			}
			$files[$relative] = array('sha256' => $hash, 'bytes' => $bytes);
		}

		$manifest = array(
			'schema_version' => 1,
			'package' => self::ROOT,
			'file_number' => 8,
			'version' => $version,
			'public_clinic_contract' => '1.0.0',
			'cf01_care_context_contract' => '1.0.0',
			'commit_sha' => $commit,
			'source_date_epoch' => $epoch,
			'generated_at_utc' => gmdate('Y-m-d\TH:i:s\Z', $epoch),
			'files' => $files,
			'staging_accepted' => false,
			'production_accepted' => false,
		);
		$manifest_json = self::json($manifest);
		$base = '08-worldwide-clinic-and-appointments-foundation-' . $version . '-candidate';
		$zip_path = $output . '/' . $base . '.zip';
		$manifest_path = $output . '/' . $base . '-manifest.json';
		$checksum_path = $output . '/' . $base . '.sha256';

		$zip = new ZipArchive();
		if ($zip->open($zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
			throw new RuntimeException('Unable to create candidate ZIP.');
		}
		try {
			foreach (array_keys($files) as $relative) {
				$entry = self::ROOT . '/' . $relative;
				if (! $zip->addFile($root . '/' . $relative, $entry)) {
					throw new RuntimeException('Unable to add payload file: ' . $relative);
				}
				$zip->setCompressionName($entry, ZipArchive::CM_DEFLATE, 9);
				if (method_exists($zip, 'setMtimeName')) {
					$zip->setMtimeName($entry, $epoch);
				}
			}
			$manifest_entry = self::ROOT . '/STAGING-MANIFEST.json';
			if (! $zip->addFromString($manifest_entry, $manifest_json)) {
				throw new RuntimeException('Unable to embed candidate manifest.');
			}
			$zip->setCompressionName($manifest_entry, ZipArchive::CM_DEFLATE, 9);
			if (method_exists($zip, 'setMtimeName')) {
				$zip->setMtimeName($manifest_entry, $epoch);
			}
		} finally {
			$zip->close();
		}

		file_put_contents($manifest_path, $manifest_json, LOCK_EX);
		$zip_hash = hash_file('sha256', $zip_path);
		if (! is_string($zip_hash)) {
			throw new RuntimeException('Unable to hash candidate ZIP.');
		}
		file_put_contents($checksum_path, $zip_hash . '  ' . basename($zip_path) . "\n", LOCK_EX);
		self::verify_zip($zip_path, $manifest_json, $manifest);

		return array(
			'version' => $version,
			'commit_sha' => $commit,
			'zip' => $zip_path,
			'zip_sha256' => $zip_hash,
			'manifest' => $manifest_path,
			'checksum' => $checksum_path,
			'payload_file_count' => count($files),
			'payload_bytes' => $total,
			'staging_accepted' => false,
			'production_accepted' => false,
		);
	}

	/** @param array<string,mixed> $manifest */
	private static function verify_zip(string $path, string $manifest_json, array $manifest): void {
		$zip = new ZipArchive();
		if ($zip->open($path, ZipArchive::RDONLY) !== true) {
			throw new RuntimeException('Unable to reopen candidate ZIP.');
		}
		try {
			$expected = array();
			foreach (array_keys($manifest['files']) as $relative) {
				$expected[] = self::ROOT . '/' . $relative;
			}
			$expected[] = self::ROOT . '/STAGING-MANIFEST.json';
			sort($expected, SORT_STRING);
			$actual = array();
			for ($index = 0; $index < $zip->numFiles; $index++) {
				$name = $zip->getNameIndex($index);
				if (! is_string($name) || isset($actual[$name])) {
					throw new RuntimeException('Duplicate or unreadable ZIP entry.');
				}
				$actual[$name] = true;
			}
			$actual_names = array_keys($actual);
			sort($actual_names, SORT_STRING);
			if ($actual_names !== $expected) {
				throw new RuntimeException('Candidate ZIP file set is not exact.');
			}
			if ($zip->getFromName(self::ROOT . '/STAGING-MANIFEST.json') !== $manifest_json) {
				throw new RuntimeException('Embedded and detached manifests differ.');
			}
			foreach ($manifest['files'] as $relative => $metadata) {
				$contents = $zip->getFromName(self::ROOT . '/' . $relative);
				if (! is_string($contents)
					|| strlen($contents) !== $metadata['bytes']
					|| ! hash_equals($metadata['sha256'], hash('sha256', $contents))
				) {
					throw new RuntimeException('Candidate ZIP payload mismatch: ' . $relative);
				}
			}
		} finally {
			$zip->close();
		}
	}

	private static function runtime_version(string $main): string {
		$content = file_get_contents($main);
		if (! is_string($content)
			|| preg_match('/^\s*\* Version:\s*([^\s]+)/m', $content, $header) !== 1
			|| preg_match("/define\( 'SWC_VERSION', '([^']+)' \)/", $content, $constant) !== 1
			|| $header[1] !== $constant[1]
			|| preg_match('/^\d+\.\d+\.\d+$/', $header[1]) !== 1
		) {
			throw new RuntimeException('Runtime version metadata is inconsistent.');
		}
		return $header[1];
	}

	private static function prepare_output(string $root, string $output): string {
		if ($output === '' || str_contains($output, "\0")) {
			throw new InvalidArgumentException('Output directory is invalid.');
		}
		if (! str_starts_with($output, DIRECTORY_SEPARATOR)) {
			$output = $root . '/' . $output;
		}
		if (! is_dir($output) && ! mkdir($output, 0775, true) && ! is_dir($output)) {
			throw new RuntimeException('Unable to create output directory.');
		}
		$real = realpath($output);
		if (! is_string($real) || is_link($output)) {
			throw new RuntimeException('Output directory is unsafe.');
		}
		return $real;
	}

	private static function safe_relative(string $relative): bool {
		return $relative !== ''
			&& ! str_contains($relative, '..')
			&& ! str_contains($relative, '\\')
			&& ! str_starts_with($relative, '/')
			&& preg_match('#^[A-Za-z0-9._/-]+$#', $relative) === 1;
	}

	/** @param array<string,mixed> $value */
	private static function json(array $value): string {
		$json = json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
		if (! is_string($json)) {
			throw new RuntimeException('Unable to encode candidate manifest.');
		}
		return $json . "\n";
	}
}

if (PHP_SAPI === 'cli' && realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
	$options = getopt('', array('output-dir:', 'commit:', 'source-date-epoch:'));
	$root = dirname(__DIR__);
	try {
		$result = File08_Candidate_Builder::build(
			$root,
			(string) ($options['output-dir'] ?? 'build/candidate'),
			strtolower(trim((string) ($options['commit'] ?? ''))),
			(int) ($options['source-date-epoch'] ?? 0)
		);
		echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), PHP_EOL;
	} catch (Throwable $exception) {
		fwrite(STDERR, 'FAILED: ' . $exception->getMessage() . PHP_EOL);
		exit(1);
	}
}
